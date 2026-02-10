<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\FirebaseService;
use App\Services\FCMService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ManagerController extends Controller
{
    protected FirebaseService $firebaseService;
    protected FCMService $fcmService;

    public function __construct(FirebaseService $firebaseService, FCMService $fcmService)
    {
        $this->firebaseService = $firebaseService;
        $this->fcmService = $fcmService;
    }

    /**
     * @OA\Post(
     *     path="/api/manager/send-accounts",
     *     summary="Envoyer tous les comptes vers Firebase Auth",
     *     tags={"Manager"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Comptes envoyés avec succès vers Firebase"
     *     ),
     *     @OA\Response(response=403, description="Non autorisé - Manager only")
     * )
     */
    public function sendAccountsToFirebase(): JsonResponse
    {
        // Vérifier que l'utilisateur est manager/admin
        if (auth()->user()->role !== 'manager' && auth()->user()->role !== 'admin') {
            return response()->json([
                'error' => 'Non autorisé. Seuls les managers peuvent envoyer les comptes.',
            ], 403);
        }

        // Vérifier la connexion Firebase
        $connectionTest = $this->firebaseService->testConnection();
        if ($connectionTest['status'] === 'error') {
            return response()->json([
                'error' => 'Erreur connexion Firebase',
                'details' => $connectionTest,
            ], 500);
        }

        // Envoyer tous les comptes
        try {
            $results = $this->firebaseService->syncAllUsersToFirebase();

            return response()->json([
                'status' => 'success',
                'message' => 'Comptes synchronisés vers Firebase Auth',
                'total_users' => count($results),
                'synced_users' => count(array_filter($results, fn($r) => $r['status'] === 'synced')),
                'failed_users' => count(array_filter($results, fn($r) => $r['status'] === 'error')),
                'results' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la synchronisation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/manager/sync-user/{userId}",
     *     summary="Synchroniser un utilisateur spécifique vers Firebase",
     *     tags={"Manager"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Utilisateur synchronisé"),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function syncUserToFirebase(int $userId): JsonResponse
    {
        if (auth()->user()->role !== 'manager' && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $user = User::findOrFail($userId);

        try {
            $result = $this->firebaseService->syncUserToFirebase($user);

            return response()->json([
                'status' => 'success',
                'user' => $result,
                'message' => 'Utilisateur synchronisé vers Firebase',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la synchronisation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/manager/firebase-status",
     *     summary="Vérifier le statut de la connexion Firebase",
     *     tags={"Manager"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(response=200, description="Statut Firebase")
     * )
     */
    public function getFirebaseStatus(): JsonResponse
    {
        if (auth()->user()->role !== 'manager' && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $status = $this->firebaseService->testConnection();

        return response()->json([
            'firebase_configured' => $this->firebaseService->isConfigured(),
            'connection_status' => $status,
            'project_id' => $this->firebaseService->getProjectId(),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/manager/send-notification",
     *     summary="Envoyer une notification à des utilisateurs",
     *     tags={"Manager"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","body"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="body", type="string"),
     *             @OA\Property(property="user_ids", type="array", items={"type":"integer"}),
     *             @OA\Property(property="send_to_all", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Notifications envoyées")
     * )
     */
    public function sendNotificationToUsers(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'manager' && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:500',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'send_to_all' => 'nullable|boolean',
        ]);

        try {
            $data = [
                'sent_by' => auth()->user()->name,
                'sent_at' => now()->toIso8601String(),
            ];

            if ($validated['send_to_all'] ?? false) {
                $result = $this->fcmService->sendToAllUsers(
                    $validated['title'],
                    $validated['body'],
                    $data
                );
            } else {
                $userIds = $validated['user_ids'] ?? [];
                $result = $this->fcmService->sendToMultipleUsers(
                    $userIds,
                    $validated['title'],
                    $validated['body'],
                    $data
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Notifications envoyées avec succès',
                'result' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'envoi des notifications',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/manager/users-summary",
     *     summary="Résumé de tous les utilisateurs",
     *     tags={"Manager"},
     *     security={{"BearerAuth":{}}}
     * )
     */
    public function getUsersSummary(): JsonResponse
    {
        if (auth()->user()->role !== 'manager' && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $users = User::all();
        $usersWithTokens = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'firebase_tokens_count' => $user->firebaseTokens()->active()->count(),
                'created_at' => $user->created_at,
            ];
        });

        return response()->json([
            'total_users' => $users->count(),
            'active_users' => $users->where('is_active', true)->count(),
            'manager_count' => $users->where('role', 'manager')->count(),
            'user_count' => $users->where('role', 'user')->count(),
            'users_with_fcm_tokens' => $usersWithTokens->where('firebase_tokens_count', '>', 0)->count(),
            'users' => $usersWithTokens,
        ], 200);
    }
}
