<?php

namespace App\Services;

use App\Models\FirebaseToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    private $apiKey;
    private $baseUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->apiKey = config('services.fcm.key');
    }

    /**
     * Envoyer une notification push via Firebase Cloud Messaging
     */
    public function sendNotification(string $token, string $title, string $body, array $data = []): array
    {
        if (!$this->apiKey) {
            Log::warning('FCM API Key not configured');
            return [
                'status' => 'error',
                'message' => 'FCM API Key not configured',
            ];
        }

        try {
            $payload = [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $data,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, $payload);

            if ($response->successful()) {
                // Enregistrer l'utilisation du token
                $firebaseToken = FirebaseToken::where('token', $token)->first();
                if ($firebaseToken) {
                    $firebaseToken->recordUsage();
                }

                Log::info('FCM notification sent successfully', [
                    'token' => substr($token, 0, 20) . '...',
                    'title' => $title,
                ]);

                return [
                    'status' => 'success',
                    'message' => 'Notification envoyée avec succès',
                    'response' => $response->json(),
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Erreur lors de l\'envoi de la notification',
                'response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('FCM Error: ' . $e->getMessage());

            return [
                'status' => 'error',
                'message' => 'Erreur FCM: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer une notification à tous les tokens actifs d'un utilisateur
     */
    public function sendToUserAllDevices(int $userId, string $title, string $body, array $data = []): array
    {
        $tokens = FirebaseToken::where('user_id', $userId)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return [
                'status' => 'no_tokens',
                'message' => 'Aucun token FCM trouvé pour cet utilisateur',
            ];
        }

        $results = [];
        foreach ($tokens as $token) {
            $result = $this->sendNotification($token, $title, $body, $data);
            $results[] = $result;
        }

        return [
            'status' => 'success',
            'message' => 'Notifications envoyées à ' . count($tokens) . ' appareil(s)',
            'devices_count' => count($tokens),
            'results' => $results,
        ];
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function sendToMultipleUsers(array $userIds, string $title, string $body, array $data = []): array
    {
        $results = [];

        foreach ($userIds as $userId) {
            $result = $this->sendToUserAllDevices($userId, $title, $body, $data);
            $results[] = [
                'user_id' => $userId,
                'result' => $result,
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Notifications envoyées à ' . count($userIds) . ' utilisateur(s)',
            'users_count' => count($userIds),
            'results' => $results,
        ];
    }

    /**
     * Envoyer une notification à tous les utilisateurs
     */
    public function sendToAllUsers(string $title, string $body, array $data = []): array
    {
        $users = \App\Models\User::all();
        $userIds = $users->pluck('id')->toArray();

        return $this->sendToMultipleUsers($userIds, $title, $body, $data);
    }

    /**
     * Tester la connexion FCM
     */
    public function testConnection(): array
    {
        if (!$this->apiKey) {
            return [
                'status' => 'error',
                'message' => 'FCM API Key not configured',
            ];
        }

        return [
            'status' => 'configured',
            'message' => 'FCM est configuré et prêt à envoyer des notifications',
            'timestamp' => now(),
        ];
    }
}
