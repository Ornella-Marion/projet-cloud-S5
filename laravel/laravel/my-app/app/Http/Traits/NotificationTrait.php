<?php

namespace App\Models\Traits;

use App\Models\Notification;
use App\Services\FCMService;

trait NotificationTrait
{
    /**
     * Créer une notification pour un changement de statut de Roadwork
     */
    public static function createStatusChangeNotification(int $roadworkId, string $oldStatus, string $newStatus, int $userId): ?Notification
    {
        $statusLabels = [
            'planned' => 'Planifié',
            'in_progress' => 'En cours',
            'completed' => 'Complété',
            'paused' => 'En pause',
        ];

        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        // Récupérer le roadwork
        $roadwork = \App\Models\Roadwork::find($roadworkId);
        if (!$roadwork) {
            return null;
        }

        // Créer la notification
        $notification = Notification::create([
            'user_id' => $userId,
            'title' => 'Changement de statut de travaux',
            'message' => "Le travail '{$roadwork->title}' est passé de {$oldLabel} à {$newLabel}",
            'type' => $newStatus === 'completed' ? 'success' : 'info',
            'notifiable_type' => \App\Models\Roadwork::class,
            'notifiable_id' => $roadworkId,
            'metadata' => [
                'roadwork_id' => $roadworkId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'location' => $roadwork->location,
            ],
        ]);

        // Envoyer une push notification si l'utilisateur a des tokens FCM
        try {
            $fcmService = new FCMService();
            $fcmService->sendToUserAllDevices(
                $userId,
                $notification->title,
                $notification->message,
                [
                    'roadwork_id' => (string)$roadworkId,
                    'type' => 'status_change',
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('FCM Error: ' . $e->getMessage());
        }

        return $notification;
    }

    /**
     * Créer une notification pour le démarrage des travaux
     */
    public static function createStartedNotification(int $roadworkId, int $userId): ?Notification
    {
        $roadwork = \App\Models\Roadwork::find($roadworkId);
        if (!$roadwork) {
            return null;
        }

        return Notification::create([
            'user_id' => $userId,
            'title' => 'Travaux démarrés',
            'message' => "Les travaux '{$roadwork->title}' à {$roadwork->location} ont commencé",
            'type' => 'info',
            'notifiable_type' => \App\Models\Roadwork::class,
            'notifiable_id' => $roadworkId,
            'metadata' => [
                'roadwork_id' => $roadworkId,
                'started_at' => $roadwork->started_at,
            ],
        ]);
    }

    /**
     * Créer une notification pour la fin des travaux
     */
    public static function createCompletedNotification(int $roadworkId, int $userId): ?Notification
    {
        $roadwork = \App\Models\Roadwork::find($roadworkId);
        if (!$roadwork) {
            return null;
        }

        return Notification::create([
            'user_id' => $userId,
            'title' => 'Travaux terminés',
            'message' => "Les travaux '{$roadwork->title}' à {$roadwork->location} sont terminés",
            'type' => 'success',
            'notifiable_type' => \App\Models\Roadwork::class,
            'notifiable_id' => $roadworkId,
            'metadata' => [
                'roadwork_id' => $roadworkId,
                'completed_at' => $roadwork->completed_at,
            ],
        ]);
    }

    /**
     * Créer une notification pour le upload de photo
     */
    public static function createPhotoUploadedNotification(int $roadworkId, string $photoType, int $userId): ?Notification
    {
        $roadwork = \App\Models\Roadwork::find($roadworkId);
        if (!$roadwork) {
            return null;
        }

        $photoTypeLabels = [
            'before' => 'avant les travaux',
            'during' => 'pendant les travaux',
            'after' => 'après les travaux',
            'issue' => 'signalant un problème',
        ];

        $typeLabel = $photoTypeLabels[$photoType] ?? $photoType;

        return Notification::create([
            'user_id' => $userId,
            'title' => 'Photo uploadée',
            'message' => "Une photo {$typeLabel} a été ajoutée au projet '{$roadwork->title}'",
            'type' => 'info',
            'notifiable_type' => \App\Models\RoadworkPhoto::class,
            'metadata' => [
                'roadwork_id' => $roadworkId,
                'photo_type' => $photoType,
            ],
        ]);
    }

    /**
     * Notifier tous les utilisateurs d'un événement
     */
    public static function notifyAll(string $title, string $message, string $type = 'info', array $metadata = []): int
    {
        $users = \App\Models\User::all();
        $count = 0;

        foreach ($users as $user) {
            try {
                Notification::create([
                    'user_id' => $user->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'metadata' => $metadata,
                ]);

                // Envoyer push notification
                try {
                    $fcmService = new FCMService();
                    $fcmService->sendToUserAllDevices($user->id, $title, $message, $metadata);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('FCM Error: ' . $e->getMessage());
                }

                $count++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erreur création notification pour user {$user->id}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
