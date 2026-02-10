<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AccountLock;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Administration",
 *     description="Gestion des comptes utilisateurs et des verrous"
 * )
 */
class AdminController extends Controller
{
    /**
     * Middleware d'authentification pour toutes les méthodes
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('manager'); // Vérifie que l'utilisateur est manager
    }

    /**
     * Vérifie que l'utilisateur actuel est un manager
     *
     * @return bool
     */
    private function isManager(): bool
    {
        $user = auth()->user();
        return $user && $user->role === UserRole::MANAGER->value;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users/locked",
     *     summary="Lister tous les comptes verrouillés",
     *     tags={"Administration"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes verrouillés",
     *         @OA\JsonContent(type="object", properties={
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="count", type="integer")
     *         })
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Non autorisé")
     * )
     */
    public function getLockedUsers(): JsonResponse
    {
        $lockedUsers = User::whereHas('accountLock', function ($query) {
            $query->where('unlock_at', '>', now())
                ->orWhereNull('unlock_at');
        })->with(['accountLock', 'loginAttempts' => function ($query) {
            $query->where('success', false)
                ->where('created_at', '>', now()->subMinutes(15))
                ->orderBy('created_at', 'desc');
        }])->get();

        return response()->json([
            'data' => $lockedUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'locked_at' => $user->accountLock?->locked_at,
                    'unlock_at' => $user->accountLock?->unlock_at,
                    'reason' => $user->accountLock?->reason,
                    'seconds_until_unlock' => $user->getSecondsUntilUnlock(),
                    'failed_attempts_15min' => $user->loginAttempts->count(),
                ];
            }),
            'count' => $lockedUsers->count(),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users/{userId}/lock-status",
     *     summary="Récupérer le statut de verrou d'un utilisateur",
     *     tags={"Administration"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut du verrou"
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function getLockStatus($userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $lockDetails = $user->getLockDetails();

        return response()->json([
            'user_id' => $user->id,
            'email' => $user->email,
            'is_locked' => $user->isLocked(),
            'lock_details' => $lockDetails,
            'failed_attempts_15min' => $this->getFailedAttemptsInWindow($user->email),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/unlock/{id}",
     *     summary="Déverrouiller manuellement un compte (Manager only)",
     *     tags={"Administration"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur à déverrouiller",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte déverrouillé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="is_locked", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Non autorisé - Manager only"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function unlockUser($id): JsonResponse
    {
        // Vérification du rôle manager (doublement assuré par le middleware)
        if (!$this->isManager()) {
            return response()->json([
                'error' => 'Accès refusé. Seuls les managers peuvent déverrouiller les comptes.',
                'required_role' => 'manager',
            ], 403);
        }

        $user = User::findOrFail($id);

        if (!$user->isLocked()) {
            return response()->json([
                'message' => 'Le compte n\'est pas verrouillé',
                'user_id' => $user->id,
                'email' => $user->email,
                'is_locked' => false,
            ], 200);
        }

        // Déverrouiller le compte
        $user->unlockAccount();

        // Log de l'action
        Log::info("Account unlocked by manager", [
            'target_user_id' => $user->id,
            'target_email' => $user->email,
            'manager_id' => auth()->id(),
            'timestamp' => now(),
        ]);

        return response()->json([
            'message' => 'Compte déverrouillé avec succès',
            'user_id' => $user->id,
            'email' => $user->email,
            'is_locked' => false,
        ], 200);
    }

    /**
     * Alias: utilise la même logique que unlockUser()
     * @deprecated Utilisez unlockUser() à la place
     */
    public function unlock($id): JsonResponse
    {
        return $this->unlockUser($id);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users/{userId}/lock",
     *     summary="Verrouiller manuellement un compte (admin)",
     *     tags={"Administration"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", example="Compte suspect"),
     *             @OA\Property(property="duration", type="integer", example=7200, description="Durée en secondes")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Compte verrouillé")
     * )
     */
    public function lockUser($userId, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:60',
        ]);

        $user = User::findOrFail($userId);
        $reason = $request->input('reason', 'Verrouillage manuel par admin');

        $lock = $user->lockAccount($reason);

        // Si durée personnalisée fournie
        if ($request->has('duration')) {
            $lock->update([
                'unlock_at' => now()->addSeconds($request->input('duration')),
            ]);
        }

        return response()->json([
            'message' => 'Compte verrouillé avec succès',
            'user_id' => $user->id,
            'email' => $user->email,
            'locked_at' => $lock->locked_at,
            'unlock_at' => $lock->unlock_at,
            'reason' => $lock->reason,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/users/{userId}/failed-attempts",
     *     summary="Réinitialiser les tentatives échouées d'un utilisateur",
     *     tags={"Administration"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur"
     *     ),
     *     @OA\Response(response=200, description="Tentatives réinitialisées")
     * )
     */
    public function clearFailedAttempts($userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $deletedCount = LoginAttempt::where('user_id', $user->id)
            ->where('success', false)
            ->delete();

        return response()->json([
            'message' => 'Tentatives échouées réinitialisées',
            'user_id' => $user->id,
            'email' => $user->email,
            'deleted_attempts' => $deletedCount,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users/{userId}/login-history",
     *     summary="Récupérer l'historique des tentatives de connexion",
     *     tags={"Administration"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="ID de l'utilisateur"
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Nombre de tentatives à retourner",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(response=200, description="Historique des tentatives")
     * )
     */
    public function getLoginHistory($userId, Request $request): JsonResponse
    {
        $user = User::findOrFail($userId);
        $limit = $request->query('limit', 50);

        $attempts = LoginAttempt::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'email' => $attempt->email,
                    'success' => $attempt->success,
                    'ip_address' => $attempt->ip_address,
                    'user_agent' => $attempt->user_agent,
                    'created_at' => $attempt->created_at,
                ];
            });

        return response()->json([
            'user_id' => $user->id,
            'email' => $user->email,
            'total' => $attempts->count(),
            'attempts' => $attempts,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/security/stats",
     *     summary="Statistiques de sécurité globales",
     *     tags={"Administration"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques de sécurité"
     *     )
     * )
     */
    public function getSecurityStats(): JsonResponse
    {
        $totalUsers = User::count();
        $lockedAccounts = AccountLock::where('unlock_at', '>', now())
            ->orWhereNull('unlock_at')
            ->count();
        $failedAttempts24h = LoginAttempt::where('success', false)
            ->where('created_at', '>', now()->subHours(24))
            ->count();
        $successfulAttempts24h = LoginAttempt::where('success', true)
            ->where('created_at', '>', now()->subHours(24))
            ->count();

        return response()->json([
            'total_users' => $totalUsers,
            'locked_accounts' => $lockedAccounts,
            'failed_attempts_24h' => $failedAttempts24h,
            'successful_attempts_24h' => $successfulAttempts24h,
            'lock_duration_seconds' => config('auth.lock_duration', 3600),
            'max_attempts_allowed' => config('auth.max_login_attempts', 3),
        ], 200);
    }

    /**
     * Utilitaire: Compter les tentatives échouées dans la fenêtre
     *
     * @param string $email
     * @return int
     */
    private function getFailedAttemptsInWindow(string $email): int
    {
        $window = now()->subMinutes(config('auth.lockout_window', 15));

        return LoginAttempt::where('email', $email)
            ->where('success', false)
            ->where('created_at', '>', $window)
            ->count();
    }
}
