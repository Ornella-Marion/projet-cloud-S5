<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 3;
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
     *     @OA\Response(status=401, description="Identifiants invalides ou compte verrouillé")
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
            try {
                LoginAttempt::create([
                    'email' => $validated['email'],
                    'ip_address' => $request->ip(),
                    'success' => false,
                ]);
            } catch (\Exception $e) {
                \Log::error('LoginAttempt error: ' . $e->getMessage());
            }

            // Compter les tentatives échouées
            $failedAttempts = LoginAttempt::countFailedAttempts($validated['email']);
            if ($failedAttempts >= self::MAX_LOGIN_ATTEMPTS) {
                if ($user) {
                    $user->lockAccount('Trop de tentatives de connexion');
                }
            }

            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        // Connexion réussie
        try {
            LoginAttempt::create([
                'user_id' => $user->id,
                'email' => $validated['email'],
                'ip_address' => $request->ip(),
                'success' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('LoginAttempt success record error: ' . $e->getMessage());
        }

        // Générer un token Sanctum
        $token = $user->createToken('api-token')->plainTextToken;

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
}
