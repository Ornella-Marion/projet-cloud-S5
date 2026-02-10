<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     summary="Obtenir les notifications de l'utilisateur",
     *     tags={"Notifications"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         @OA\Schema(type="string", enum={"all","unread","read"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des notifications",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = auth()->user()->notifications();

        if ($request->query('filter') === 'unread') {
            $query->unread();
        } elseif ($request->query('filter') === 'read') {
            $query->read();
        }

        $notifications = $query->latest()->paginate(20);
        return response()->json($notifications, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/{id}",
     *     summary="Obtenir une notification spécifique",
     *     tags={"Notifications"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Notification"),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function show(Notification $notification): JsonResponse
    {
        // Vérifier que c'est la notification de l'utilisateur
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        return response()->json($notification, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/{id}/read",
     *     summary="Marquer une notification comme lue",
     *     tags={"Notifications"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Notification marquée comme lue"),
     *     @OA\Response(response=404, description="Non trouvée")
     * )
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $notification->markAsRead();
        return response()->json($notification, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/{id}/unread",
     *     summary="Marquer une notification comme non lue",
     *     tags={"Notifications"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Notification marquée comme non lue")
     * )
     */
    public function markAsUnread(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $notification->markAsUnread();
        return response()->json($notification, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     summary="Supprimer une notification",
     *     tags={"Notifications"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Notification supprimée")
     * )
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $notification->delete();
        return response()->json(['message' => 'Notification supprimée'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     summary="Compter les notifications non lues",
     *     tags={"Notifications"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nombre de notifications non lues",
     *         @OA\JsonContent(
     *             @OA\Property(property="unread_count", type="integer")
     *         )
     *     )
     * )
     */
    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->notifications()->unread()->count();
        return response()->json(['unread_count' => $count], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/notifications/mark-all-as-read",
     *     summary="Marquer toutes les notifications comme lues",
     *     tags={"Notifications"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(response=200, description="Toutes les notifications sont lues")
     * )
     */
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->notifications()
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Toutes les notifications sont maintenant lues'], 200);
    }
}
