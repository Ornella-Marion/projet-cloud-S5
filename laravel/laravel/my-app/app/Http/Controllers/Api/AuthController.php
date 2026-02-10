<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Services\FirebaseAuthService;
use App\Http\Traits\LoginAttemptTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Lister tous les utilisateurs (pour le signalement)
    public function listUsers()
    {
        return response()->json(User::select('id', 'name', 'email')->get());
    }
    private const MAX_LOGIN_ATTEMPTS = 3;
    use LoginAttemptTrait;

    private const SESSION_DURATION = 86400; // 24 heures

    /**
     * @OA\Post(
     *     path="/api/auth/signup",
     *     summary="Créer un nouveau compte",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password","name"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Response(status=201, description="Compte créé avec succès"),
     *     @OA\Response(status=400, description="Email déjà existant ou données invalides")
     * )
     */
    public function signup(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'name' => 'required|string',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'name' => $validated['name'],
            'role' => 'user',
        ]);

        // Générer un token Sanctum
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['message' => 'Utilisateur créé', 'token' => $token, 'user' => $user], 201);
    }

    /**
     * Inscription d'un utilisateur par un Manager (Manager uniquement)
     * Crée l'utilisateur dans Laravel ET Firebase
     */
    public function managerSignup(Request $request)
    {
        // Vérifier que l'utilisateur actuel est un manager
        $currentUser = $request->user();
        if (!$currentUser || $currentUser->role !== 'manager') {
            return response()->json(['error' => 'Accès refusé. Seuls les managers peuvent inscrire des utilisateurs.'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'name' => 'required|string',
            'role' => 'required|in:user,visitor',
        ]);

        // 1. Créer l'utilisateur dans Firebase
        $firebaseService = new FirebaseAuthService();
        $firebaseResult = $firebaseService->createUser($validated['email'], $validated['password']);

        if (!$firebaseResult['success']) {
            // Si l'erreur est "EMAIL_EXISTS", l'utilisateur existe déjà dans Firebase - on continue
            if (strpos($firebaseResult['error'], 'EMAIL_EXISTS') === false) {
                return response()->json([
                    'error' => 'Erreur Firebase: ' . $firebaseResult['error']
                ], 400);
            }
            \Log::info('Firebase: Utilisateur existe déjà - ' . $validated['email']);
        }

        // 2. Créer l'utilisateur dans Laravel
        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'name' => $validated['name'],
            'role' => $validated['role'],
        ]);

        \Log::info('Manager ' . $currentUser->name . ' a créé l\'utilisateur: ' . $user->email . ' avec le rôle ' . $user->role);

        return response()->json([
            'message' => 'Utilisateur créé avec succès (Laravel + Firebase)',
            'user' => $user
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Se connecter",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(status=200, description="Connexion réussie", @OA\JsonContent(
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="user", type="object")
     *     )),
     *     @OA\Response(status=401, description="Email ou mot de passe incorrect")
     * )
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Vérifier si le compte est verrouillé
        if ($user && $user->isLocked()) {
            return response()->json(['error' => 'Compte verrouillé'], 401);
        }

        // Vérifier les identifiants
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            // Enregistrer la tentative échouée
            $this->recordFailedLoginAttempt($validated['email'], $request);

            // Vérifier si le maximum de tentatives est atteint
            if ($this->hasExceededMaxAttempts($validated['email'])) {
                if ($user) {
                    $user->lockAccount('Trop de tentatives de connexion');
                }
                return response()->json([
                    'error' => 'Compte verrouillé après trop de tentatives',
                    'remaining_attempts' => 0
                ], 401);
            }

            return response()->json([
                'error' => 'Identifiants invalides',
                'remaining_attempts' => $this->getRemainingAttempts($validated['email'])
            ], 401);
        }

        // Connexion réussie - enregistrer et réinitialiser les tentatives
        $this->recordSuccessfulLoginAttempt($user->id, $validated['email'], $request);
        $this->clearFailedAttempts($validated['email']);

        // IMPORTANT: Supprimer TOUS les anciens tokens de cet utilisateur avant d'en créer un nouveau
        $user->tokens()->delete();
        \Log::info('Login: Anciens tokens supprimés pour user ' . $user->id . ' (' . $user->name . ')');

        // Générer un nouveau token Sanctum
        $token = $user->createToken('api-token')->plainTextToken;
        \Log::info('Login: Nouveau token créé pour user ' . $user->id . ' (' . $user->name . ')');

        return response()->json(['token' => $token, 'user' => $user, 'expires_in' => self::SESSION_DURATION]);
    }

    /**
     * @OA\Put(
     *     path="/api/auth/profile",
     *     summary="Mettre à jour le profil utilisateur",
     *     tags={"Authentication"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(status=200, description="Profil mis à jour")
     * )
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Profil mis à jour', 'user' => $user]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Se déconnecter",
     *     tags={"Authentication"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(status=200, description="Déconnexion réussie"),
     *     @OA\Response(status=401, description="Non authentifié")
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // Supprimer tous les tokens de l'utilisateur
        $user->tokens()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/unlock-account/{userId}",
     *     summary="Débloquer un compte (Admin/Manager)",
     *     tags={"Authentication"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(status=200, description="Compte débloqué"),
     *     @OA\Response(status=403, description="Non autorisé"),
     *     @OA\Response(status=404, description="Utilisateur non trouvé")
     * )
     */
    public function unlockAccount($userId)
    {
        $authUser = auth()->user();
        if (!$authUser || $authUser->role !== 'manager') {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $user->unlockAccount();

        return response()->json(['message' => 'Compte débloqué']);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/locked-accounts",
     *     summary="Lister les comptes bloqués (Manager uniquement)",
     *     tags={"Authentication"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(status=200, description="Liste des comptes bloqués"),
     *     @OA\Response(status=403, description="Non autorisé")
     * )
     */
    public function getLockedAccounts(Request $request)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'manager') {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $lockedUsers = User::whereHas('accountLock', function ($query) {
            $query->where(function ($q) {
                $q->whereNull('unlock_at')
                    ->orWhere('unlock_at', '>', now());
            });
        })->get(['id', 'name', 'email']);

        return response()->json($lockedUsers);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Récupérer les infos utilisateur actuel",
     *     tags={"Authentication"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(status=200, description="Infos utilisateur", @OA\JsonContent(
     *         @OA\Property(property="user", type="object")
     *     )),
     *     @OA\Response(status=401, description="Non authentifié")
     * )
     */
    public function getCurrentUser(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh-token",
     *     summary="Renouveler le token d'authentification",
     *     tags={"Authentication"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(status=200, description="Token renouvelé"),
     *     @OA\Response(status=401, description="Non authentifié")
     * )
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // Supprimer le token actuel
        $request->user()->currentAccessToken()->delete();

        // Générer un nouveau token
        $newToken = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Token renouvelé',
            'token' => $newToken,
            'user' => $user,
        ]);
    }

    /**
     * #110 — POST /api/manager/sync — Synchronisation Firebase
     * Synchronise les utilisateurs Laravel avec Firebase Auth
     * Manager uniquement
     */
    public function syncFirebase(Request $request)
    {
        $currentUser = $request->user();
        if (!$currentUser || $currentUser->role !== 'manager') {
            return response()->json(['error' => 'Accès refusé. Réservé aux managers.'], 403);
        }

        $firebaseService = new FirebaseAuthService();
        $users = User::all();
        $synced = 0;
        $failed = 0;
        $alreadyExists = 0;
        $errors = [];

        foreach ($users as $user) {
            // Vérifier si l'utilisateur existe déjà dans Firebase
            if ($firebaseService->userExists($user->email)) {
                $alreadyExists++;
                continue;
            }

            // Créer l'utilisateur dans Firebase
            // On génère un mot de passe temporaire car on n'a pas le mot de passe en clair
            $result = $firebaseService->createUser($user->email, 'TempSync_' . bin2hex(random_bytes(4)));

            if ($result['success']) {
                $synced++;
                \Log::info('Firebase sync: utilisateur synchronisé - ' . $user->email);
            } else {
                if (strpos($result['error'], 'EMAIL_EXISTS') !== false) {
                    $alreadyExists++;
                } else {
                    $failed++;
                    $errors[] = ['email' => $user->email, 'error' => $result['error']];
                    \Log::error('Firebase sync: erreur pour ' . $user->email . ' - ' . $result['error']);
                }
            }
        }

        return response()->json([
            'message' => 'Synchronisation Firebase terminée',
            'total_users' => $users->count(),
            'synced' => $synced,
            'already_exists' => $alreadyExists,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }
}
