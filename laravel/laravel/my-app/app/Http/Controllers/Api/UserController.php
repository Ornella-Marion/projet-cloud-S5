<?php

namespace App\Http\Controllers\Api;


use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;

/**
 * @mixin \Illuminate\Routing\Controller
 */
class UserController extends Controller
{
    /**
     * @OA\Tag(
     *     name="User Management",
     *     description="Gestion des utilisateurs (CRUD)"
     * )
     */
    /**
     * Middleware d'authentification pour toutes les méthodes
     * @noinspection PhpUndefinedMethodInspection
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['show']);
        $this->middleware('role:manager')->except(['show', 'update']);
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Lister tous les utilisateurs (Manager only)",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", default=15, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filtrer par rôle (visitor, user, manager)",
     *         @OA\Schema(type="string", enum={"visitor", "user", "manager"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Rechercher par nom ou email",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="locked",
     *         in="query",
     *         description="Filtrer par statut de verrou (0=déverrouillés, 1=verrouillés)",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs",
     *         @OA\JsonContent(type="object", properties={
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="pagination", type="object"),
     *             @OA\Property(property="filters", type="object")
     *         })
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Non autorisé - Manager only")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->query('per_page', 15), 100); // Max 100 par page

        $query = User::query();

        // Filtrer par rôle
        if ($request->has('role')) {
            $role = $request->query('role');
            if (in_array($role, UserRole::values())) {
                $query->where('role', $role);
            }
        }

        // Filtrer par recherche (nom ou email)
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        // Filtrer par statut de verrou
        if ($request->has('locked')) {
            $locked = $request->boolean('locked');
            if ($locked) {
                // Comptes verrouillés (avec account_lock actif)
                $query->whereHas('accountLock', function ($q) {
                    $q->where('unlock_at', '>', now())
                        ->orWhereNull('unlock_at');
                });
            } else {
                // Comptes déverrouillés
                $query->whereDoesntHave('accountLock', function ($q) {
                    $q->where('unlock_at', '>', now())
                        ->orWhereNull('unlock_at');
                });
            }
        }

        // Paginer
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'filters' => [
                'role' => $request->query('role'),
                'search' => $request->query('search'),
                'locked' => $request->query('locked'),
            ],
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Créer un nouvel utilisateur (Manager only)",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="role", type="string", enum={"visitor", "user", "manager"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Non autorisé - Manager only")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', UserRole::values()),
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'data' => $user,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/users/me",
     *     summary="Récupérer le profil de l'utilisateur authentifié",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profil de l'utilisateur authentifié",
     *         @OA\JsonContent(type="object", properties={
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="login_attempts", type="array"),
     *             @OA\Property(property="account_lock", type="object"),
     *             @OA\Property(property="is_locked", type="boolean")
     *         })
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     * @noinspection PhpUndefinedMethodInspection
     */
    public function getProfile(): JsonResponse
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Non authentifié',
            ], 401);
        }

        // Charger les relations
        $user->load(['loginAttempts' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }, 'accountLock']);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'is_locked' => $user->isLocked(),
                'recent_login_attempts' => $user->loginAttempts,
                'account_lock' => $user->accountLock ? [
                    'locked_at' => $user->accountLock->locked_at,
                    'unlock_at' => $user->accountLock->unlock_at,
                    'reason' => $user->accountLock->reason,
                    'seconds_until_unlock' => $user->getSecondsUntilUnlock(),
                ] : null,
            ],
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/users/me",
     *     summary="Mettre à jour le profil de l'utilisateur authentifié",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(type="object", properties={
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         })
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     * @noinspection PhpUndefinedMethodInspection
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Non authentifié',
            ], 401);
        }

        // La validation est effectuée automatiquement par UpdateProfileRequest
        $validated = $request->validated();

        // Mettre à jour les champs autorisés
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Recharger les relations
        $user->load(['loginAttempts' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }, 'accountLock']);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'updated_at' => $user->updated_at,
                'is_locked' => $user->isLocked(),
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Récupérer les détails d'un utilisateur",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'utilisateur",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     * @noinspection PhpUndefinedMethodInspection
     */
    public function show($id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Vérifier les permissions
        /** @noinspection PhpUndefinedMethodInspection */
        $authUser = auth()->user();
        if ($authUser && $authUser->id !== $user->id && $authUser->role !== UserRole::MANAGER->value) {
            return response()->json([
                'error' => 'Accès refusé',
            ], 403);
        }

        return response()->json([
            'data' => $user->load('loginAttempts', 'accountLock'),
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Mettre à jour un utilisateur",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="role", type="string", enum={"visitor", "user", "manager"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur mis à jour",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     * @noinspection PhpUndefinedMethodInspection
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Vérifier les permissions (l'utilisateur ne peut modifier que son propre compte, sauf manager)
        /** @noinspection PhpUndefinedMethodInspection */
        $authUser = auth()->user();
        if ($authUser->id !== $user->id && $authUser->role !== UserRole::MANAGER->value) {
            return response()->json([
                'error' => 'Vous ne pouvez modifier que votre propre compte',
            ], 403);
        }

        // Validation
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8|confirmed',
            'role' => 'sometimes|required|in:' . implode(',', UserRole::values()),
        ];

        // Les utilisateurs non-manager ne peuvent pas changer le rôle
        if ($authUser->role !== UserRole::MANAGER->value) {
            unset($rules['role']);
        }

        $validated = $request->validate($rules);

        // Mettre à jour les champs
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        if (isset($validated['role'])) {
            $user->role = $validated['role'];
        }

        $user->save();

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => $user,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Supprimer un utilisateur (Manager only)",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur à supprimer",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur supprimé",
     *         @OA\JsonContent(type="object", properties={
     *             @OA\Property(property="message", type="string")
     *         })
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé"),
     *     @OA\Response(response=403, description="Non autorisé - Manager only")
     * )
     * @noinspection PhpUndefinedMethodInspection
     */
    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Éviter la suppression du propre compte
        /** @noinspection PhpUndefinedMethodInspection */
        if (auth()->id() === $user->id) {
            return response()->json([
                'error' => 'Vous ne pouvez pas supprimer votre propre compte',
            ], 403);
        }

        // Supprimer les données associées
        $user->loginAttempts()->delete();
        $user->accountLock()->delete();
        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès',
            'deleted_user_id' => $id,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/users/search",
     *     summary="Rechercher des utilisateurs par email ou nom",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Terme de recherche",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Résultats de la recherche",
     *         @OA\JsonContent(type="object", properties={
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="count", type="integer")
     *         })
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'error' => 'Le terme de recherche doit contenir au moins 2 caractères',
            ], 400);
        }

        $users = User::where('email', 'ILIKE', "%{$query}%")
            ->orWhere('name', 'ILIKE', "%{$query}%")
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $users,
            'count' => $users->count(),
            'query' => $query,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}/activity",
     *     summary="Récupérer l'activité d'un utilisateur (tentatives de login)",
     *     tags={"User Management"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Activité de l'utilisateur"
     *     )
     * )
     * @noinspection PhpUndefinedMethodInspection
     */
    public function activity($id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Vérifier les permissions
        /** @noinspection PhpUndefinedMethodInspection */
        $authUser = auth()->user();
        if ($authUser->id !== $user->id && $authUser->role !== 'admin') {
            return response()->json([
                'error' => 'Accès refusé',
            ], 403);
        }

        $attempts = $user->loginAttempts()
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $lock = $user->accountLock;

        return response()->json([
            'user_id' => $user->id,
            'email' => $user->email,
            'login_attempts' => $attempts,
            'total_attempts' => $attempts->count(),
            'failed_attempts' => $attempts->where('success', false)->count(),
            'successful_attempts' => $attempts->where('success', true)->count(),
            'current_lock' => $lock ? [
                'locked_at' => $lock->locked_at,
                'unlock_at' => $lock->unlock_at,
                'reason' => $lock->reason,
                'is_active' => $user->isLocked(),
            ] : null,
        ], 200);
    }
}
