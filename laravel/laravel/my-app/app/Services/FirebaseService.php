<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service Firebase
 * 
 * Classe centralisée pour gérer les interactions avec Firebase
 * Version mockée pour développement/test sans credentials valides
 * 
 * @package App\Services
 */
class FirebaseService
{
    /**
     * Indique si Firebase est configuré
     * 
     * @var bool
     */
    private bool $isConfigured = false;

    /**
     * Configuration Firebase
     * 
     * @var array
     */
    private array $config;

    /**
     * Données mockées stockées en mémoire pour le test
     * 
     * @var array
     */
    private array $mockData = [];

    /**
     * Constructeur du service Firebase
     */
    public function __construct()
    {
        $this->config = config('firebase');
        $this->validateConfiguration();
    }

    /**
     * Valider la configuration Firebase
     * 
     * @return void
     */
    private function validateConfiguration(): void
    {
        // Vérifier que les credentials de base sont présentes
        $requiredFields = ['project_id', 'api_key', 'auth_domain'];

        foreach ($requiredFields as $field) {
            if (empty($this->config[$field])) {
                Log::warning("Firebase configuration missing: {$field}");
                return;
            }
        }

        $this->isConfigured = true;
    }

    /**
     * Vérifier si Firebase est configuré
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Obtenir la configuration Firebase
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Obtenir le Project ID
     * 
     * @return string|null
     */
    public function getProjectId(): ?string
    {
        return $this->config['project_id'] ?? null;
    }

    /**
     * Obtenir l'API Key
     * 
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->config['api_key'] ?? null;
    }

    /**
     * Obtenir le Auth Domain
     * 
     * @return string|null
     */
    public function getAuthDomain(): ?string
    {
        return $this->config['auth_domain'] ?? null;
    }

    /**
     * Obtenir le Storage Bucket
     * 
     * @return string|null
     */
    public function getStorageBucket(): ?string
    {
        return $this->config['storage_bucket'] ?? null;
    }

    /**
     * Obtenir la Database URL (Realtime Database)
     * 
     * @return string|null
     */
    public function getDatabaseUrl(): ?string
    {
        return $this->config['database_url'] ?? null;
    }

    /**
     * Vérifier si un service est activé
     * 
     * @param string $service (realtime_db, firestore, storage, push_notifications, analytics)
     * @return bool
     */
    public function isServiceEnabled(string $service): bool
    {
        return (bool) ($this->config['services'][$service] ?? false);
    }

    /**
     * Écrire des données dans Firestore (MOCK)
     * 
     * @param string $collection
     * @param string $documentId
     * @param array $data
     * @return bool
     */
    public function writeToFirestore(string $collection, string $documentId, array $data): bool
    {
        if (!isset($this->mockData[$collection])) {
            $this->mockData[$collection] = [];
        }

        $this->mockData[$collection][$documentId] = [
            ...$data,
            '_stored_at' => now()->toIso8601String(),
        ];

        Log::info("Firebase: Firestore write (MOCK)", [
            'collection' => $collection,
            'document_id' => $documentId,
        ]);

        return true;
    }

    /**
     * Lire des données depuis Firestore (MOCK)
     * 
     * @param string $collection
     * @param string $documentId
     * @return array|null
     */
    public function readFromFirestore(string $collection, string $documentId): ?array
    {
        $data = $this->mockData[$collection][$documentId] ?? null;

        Log::info("Firebase: Firestore read (MOCK)", [
            'collection' => $collection,
            'document_id' => $documentId,
            'found' => $data !== null,
        ]);

        return $data;
    }

    /**
     * Écrire des données dans Realtime Database (MOCK)
     * 
     * @param string $path
     * @param array $data
     * @return bool
     */
    public function writeToRealtimeDb(string $path, array $data): bool
    {
        $this->mockData["_rtdb:{$path}"] = $data;

        Log::info("Firebase: Realtime DB write (MOCK)", [
            'path' => $path,
        ]);

        return true;
    }

    /**
     * Lire des données depuis Realtime Database (MOCK)
     * 
     * @param string $path
     * @return array|null
     */
    public function readFromRealtimeDb(string $path): ?array
    {
        return $this->mockData["_rtdb:{$path}"] ?? null;
    }

    /**
     * Obtenir le client Admin SDK (MOCK)
     * 
     * @return mixed
     * @throws Exception
     */
    public function getAdminClient()
    {
        if (!$this->isConfigured) {
            throw new Exception('Firebase is not properly configured');
        }

        return new class {
            public function getFirestore()
            {
                return new class {
                    public function collection($name)
                    {
                        return $this;
                    }
                    public function document($id)
                    {
                        return $this;
                    }
                    public function set($data)
                    {
                        return true;
                    }
                    public function snapshot()
                    {
                        return new class {
                            public function exists()
                            {
                                return false;
                            }
                            public function data()
                            {
                                return [];
                            }
                        };
                    }
                };
            }
            public function getDatabase()
            {
                return new class {
                    public function getReference($path = '')
                    {
                        return $this;
                    }
                    public function set($value)
                    {
                        return true;
                    }
                    public function getValue()
                    {
                        return null;
                    }
                };
            }
            public function getStorage()
            {
                return new class {
                    public function bucket($name = '')
                    {
                        return $this;
                    }
                    public function object($path)
                    {
                        return $this;
                    }
                    public function uploadFromString($data)
                    {
                        return true;
                    }
                };
            }
            public function getMessaging()
            {
                return new class {
                    public function send($message)
                    {
                        return 'mock-message-id-' . uniqid();
                    }
                };
            }
        };
    }

    /**
     * Obtenir le client Realtime Database (MOCK)
     * 
     * @return mixed
     * @throws Exception
     */
    public function getRealtimeDatabaseClient()
    {
        if (!$this->isServiceEnabled('realtime_db')) {
            throw new Exception('Realtime Database service is not enabled');
        }

        return $this->getAdminClient()->getDatabase();
    }

    /**
     * Obtenir le client Firestore (MOCK)
     * 
     * @return mixed
     * @throws Exception
     */
    public function getFirestoreClient()
    {
        if (!$this->isServiceEnabled('firestore')) {
            throw new Exception('Firestore service is not enabled');
        }

        return $this->getAdminClient()->getFirestore();
    }

    /**
     * Obtenir le client Cloud Storage (MOCK)
     * 
     * @return mixed
     * @throws Exception
     */
    public function getStorageClient()
    {
        if (!$this->isServiceEnabled('storage')) {
            throw new Exception('Cloud Storage service is not enabled');
        }

        return $this->getAdminClient()->getStorage();
    }

    /**
     * Obtenir le client Cloud Messaging (MOCK)
     * 
     * @return mixed
     * @throws Exception
     */
    public function getMessagingClient()
    {
        if (!$this->isServiceEnabled('push_notifications')) {
            throw new Exception('Cloud Messaging service is not enabled');
        }

        return $this->getAdminClient()->getMessaging();
    }

    /**
     * Logger une action Firebase
     * 
     * @param string $action
     * @param array $context
     * @return void
     */
    public function log(string $action, array $context = []): void
    {
        Log::info("Firebase: {$action}", $context);
    }

    /**
     * Logger une erreur Firebase
     * 
     * @param string $action
     * @param string $error
     * @param array $context
     * @return void
     */
    public function logError(string $action, string $error, array $context = []): void
    {
        Log::error("Firebase Error - {$action}: {$error}", $context);
    }
}
