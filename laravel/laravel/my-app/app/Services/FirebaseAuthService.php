<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseAuthService
{
    private string $apiKey;
    private string $apiUrl = 'https://identitytoolkit.googleapis.com/v1/accounts';

    public function __construct()
    {
        // Clé API Firebase (même que dans le frontend)
        $this->apiKey = config('services.firebase.api_key', 'AIzaSyBZkt2K-MTItsrwGLZc4cQf9mvG9tFtLvY');
    }

    /**
     * Créer un utilisateur dans Firebase Authentication
     */
    public function createUser(string $email, string $password): array
    {
        try {
            $response = Http::post("{$this->apiUrl}:signUp?key={$this->apiKey}", [
                'email' => $email,
                'password' => $password,
                'returnSecureToken' => false,
            ]);

            if ($response->successful()) {
                Log::info("Firebase: Utilisateur créé - {$email}");
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $error = $response->json();
            Log::error("Firebase: Erreur création utilisateur - " . json_encode($error));
            
            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Erreur Firebase inconnue',
            ];
        } catch (\Exception $e) {
            Log::error("Firebase: Exception - " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier si un utilisateur existe dans Firebase
     */
    public function userExists(string $email): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}:createAuthUri?key={$this->apiKey}", [
                'identifier' => $email,
                'continueUri' => 'http://localhost',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['registered']) && $data['registered'] === true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Firebase: Exception vérification - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un utilisateur de Firebase (nécessite Admin SDK, pas disponible via REST simple)
     * Pour la suppression, il faudrait utiliser Firebase Admin SDK
     */
}
