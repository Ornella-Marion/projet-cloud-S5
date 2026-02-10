<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
     * Préfixe pour les clés Redis mockées
     * 
     * @var string
     */
    private string $mockPrefix = 'firebase:mock:';

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
        $key = $this->mockPrefix . "firestore:{$collection}:{$documentId}";

        $dataToStore = [
            ...$data,
            '_stored_at' => now()->toIso8601String(),
        ];

        // Persister dans Redis avec une expiration de 24 heures
        Cache::put($key, $dataToStore, 86400);

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
        $key = $this->mockPrefix . "firestore:{$collection}:{$documentId}";
        $data = Cache::get($key);

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
        $key = $this->mockPrefix . "rtdb:{$path}";

        // Persister dans Redis avec une expiration de 24 heures
        Cache::put($key, $data, 86400);

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
        $key = $this->mockPrefix . "rtdb:{$path}";
        return Cache::get($key);
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

    /**
     * Vérifier la connexion Internet et la connectivité aux services Firebase
     * 
     * Teste la connectivité en effectuant des requêtes ping vers plusieurs endpoints
     * 
     * @return array Tableau contenant l'état de la connexion
     *              [
     *                  'connected' => bool,
     *                  'internet' => bool,
     *                  'firebase' => bool,
     *                  'firestore' => bool,
     *                  'realtime_db' => bool,
     *                  'storage' => bool,
     *                  'response_time' => float (millisecondes)
     *              ]
     */
    public function checkInternetConnection(): array
    {
        $startTime = microtime(true);
        $results = [
            'connected' => false,
            'internet' => false,
            'firebase' => false,
            'firestore' => false,
            'realtime_db' => false,
            'storage' => false,
            'response_time' => 0,
            'details' => []
        ];

        try {
            // Tester la connexion Internet générale
            $internetCheck = @fsockopen("8.8.8.8", 53, $errno, $errstr, 3);
            if ($internetCheck) {
                fclose($internetCheck);
                $results['internet'] = true;
                $results['details']['internet'] = 'Google DNS accessible';
            } else {
                $results['details']['internet'] = 'Google DNS not reachable';
            }

            // Tester la connexion aux endpoints Firebase si le projet est configuré
            if (!empty($this->config['project_id'])) {
                // Endpoint Firestore
                if (!empty($this->config['database_url'])) {
                    $firestoreUrl = str_replace('https://', '', $this->config['database_url']);
                    $firestoreUrl = explode('/', $firestoreUrl)[0];
                    if (@fsockopen($firestoreUrl, 443, $errno, $errstr, 3)) {
                        $results['firestore'] = true;
                        $results['details']['firestore'] = 'Firestore endpoint reachable';
                    } else {
                        $results['details']['firestore'] = 'Firestore endpoint unreachable';
                    }
                }

                // Endpoint Realtime Database
                if (!empty($this->config['database_url'])) {
                    $realtimeUrl = str_replace('https://', '', $this->config['database_url']);
                    $realtimeUrl = explode('/', $realtimeUrl)[0];
                    if (@fsockopen($realtimeUrl, 443, $errno, $errstr, 3)) {
                        $results['realtime_db'] = true;
                        $results['details']['realtime_db'] = 'Realtime DB endpoint reachable';
                    } else {
                        $results['details']['realtime_db'] = 'Realtime DB endpoint unreachable';
                    }
                }

                // Endpoint Cloud Storage
                if (!empty($this->config['storage_bucket'])) {
                    $storageUrl = "storage.googleapis.com";
                    if (@fsockopen($storageUrl, 443, $errno, $errstr, 3)) {
                        $results['storage'] = true;
                        $results['details']['storage'] = 'Cloud Storage endpoint reachable';
                    } else {
                        $results['details']['storage'] = 'Cloud Storage endpoint unreachable';
                    }
                }

                // Si au moins un service Firebase est accessible
                $results['firebase'] = $results['firestore'] || $results['realtime_db'] || $results['storage'];
            } else {
                $results['details']['firebase'] = 'Firebase project not configured';
            }

            // Statut global: connecté si Internet + Firebase
            $results['connected'] = $results['internet'] && $results['firebase'];
        } catch (Exception $e) {
            $results['details']['error'] = $e->getMessage();
            Log::error("Internet connection check failed", ['exception' => $e->getMessage()]);
        }

        $results['response_time'] = round((microtime(true) - $startTime) * 1000, 2);

        return $results;
    }

    /**
     * Synchroniser des données vers Firebase
     * 
     * Méthode générique pour synchroniser des données vers Firestore ou Realtime Database
     * Gère automatiquement la sérialisation, la gestion d'erreurs et le logging
     * 
     * @param string $destination 'firestore' ou 'realtime_db'
     * @param array $data Les données à synchroniser
     * @param array $options Options de configuration:
     *                       - 'collection' (pour firestore): nom de la collection
     *                       - 'document_id' (pour firestore): ID du document
     *                       - 'path' (pour realtime_db): chemin dans la DB
     *                       - 'merge' (bool): fusionner avec les données existantes
     *                       - 'timestamp' (bool): ajouter un timestamp
     * 
     * @return array Résultat de la synchronisation
     *              [
     *                  'success' => bool,
     *                  'destination' => 'firestore' | 'realtime_db',
     *                  'message' => string,
     *                  'data_synced' => array,
     *                  'timestamp' => ISO8601,
     *                  'error' => string|null,
     *              ]
     */
    public function syncToFirebase(string $destination, array $data, array $options = []): array
    {
        $startTime = microtime(true);
        $result = [
            'success' => false,
            'destination' => $destination,
            'message' => '',
            'data_synced' => [],
            'timestamp' => now()->toIso8601String(),
            'error' => null,
            'response_time_ms' => 0,
        ];

        try {
            // Valider les paramètres
            if (!in_array($destination, ['firestore', 'realtime_db'])) {
                throw new Exception("Invalid destination: {$destination}. Must be 'firestore' or 'realtime_db'");
            }

            // Vérifier que le service est activé
            if (!$this->isServiceEnabled($destination)) {
                throw new Exception("Service {$destination} is not enabled");
            }

            // Ajouter le timestamp si demandé
            $dataToSync = $data;
            if ($options['timestamp'] ?? true) {
                $dataToSync['_synced_at'] = now()->toIso8601String();
            }

            // Synchroniser vers Firestore
            if ($destination === 'firestore') {
                $collection = $options['collection'] ?? null;
                $documentId = $options['document_id'] ?? null;

                if (empty($collection) || empty($documentId)) {
                    throw new Exception("Missing required options for firestore: 'collection' and 'document_id'");
                }

                $this->writeToFirestore($collection, $documentId, $dataToSync);

                $result['success'] = true;
                $result['message'] = "Data synchronized to Firestore successfully";
                $result['data_synced'] = [
                    'collection' => $collection,
                    'document_id' => $documentId,
                    'fields_count' => count($dataToSync),
                ];

                Log::info("Firebase: Data synced to Firestore", [
                    'collection' => $collection,
                    'document_id' => $documentId,
                    'data_fields' => count($dataToSync),
                ]);
            }

            // Synchroniser vers Realtime Database
            elseif ($destination === 'realtime_db') {
                $path = $options['path'] ?? null;

                if (empty($path)) {
                    throw new Exception("Missing required option for realtime_db: 'path'");
                }

                $this->writeToRealtimeDb($path, $dataToSync);

                $result['success'] = true;
                $result['message'] = "Data synchronized to Realtime Database successfully";
                $result['data_synced'] = [
                    'path' => $path,
                    'fields_count' => count($dataToSync),
                ];

                Log::info("Firebase: Data synced to Realtime Database", [
                    'path' => $path,
                    'data_fields' => count($dataToSync),
                ]);
            }
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = "Data synchronization failed";
            $result['error'] = $e->getMessage();

            Log::error("Firebase: Data sync failed", [
                'destination' => $destination,
                'error' => $e->getMessage(),
                'options' => array_keys($options),
            ]);
        }

        $result['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $result;
    }

    /**
     * Synchroniser les données d'un modèle vers Firebase
     * 
     * Cas d'usage: Après créer/modifier un utilisateur, synchroniser vers Firebase
     * 
     * @param string $modelName Nom du modèle (ex: 'user', 'post', 'comment')
     * @param int|string $modelId ID du modèle
     * @param array $data Données du modèle
     * @param string $destination 'firestore' ou 'realtime_db'
     * 
     * @return array Résultat de la synchronisation
     */
    public function syncModel(string $modelName, $modelId, array $data, string $destination = 'firestore'): array
    {
        $options = [
            'collection' => strtolower($modelName) . 's', // 'users', 'posts', 'comments'
            'document_id' => (string) $modelId,
            'path' => strtolower($modelName) . 's/' . $modelId,
            'timestamp' => true,
        ];

        return $this->syncToFirebase($destination, $data, $options);
    }

    /**
     * Synchroniser plusieurs éléments vers Firebase (batch sync)
     * 
     * @param string $destination 'firestore' ou 'realtime_db'
     * @param array $items Tableau d'items à synchroniser
     *                      Pour firestore: [['collection' => 'x', 'document_id' => 'y', 'data' => [...]]]
     *                      Pour realtime_db: [['path' => 'x', 'data' => [...]]]
     * 
     * @return array Résultats des synchronisations
     */
    public function syncBatch(string $destination, array $items): array
    {
        $results = [
            'destination' => $destination,
            'total_items' => count($items),
            'successful' => 0,
            'failed' => 0,
            'items' => [],
        ];

        foreach ($items as $item) {
            $data = $item['data'] ?? [];
            $options = $item;
            unset($options['data']);

            $syncResult = $this->syncToFirebase($destination, $data, $options);

            if ($syncResult['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }

            $results['items'][] = $syncResult;
        }

        Log::info("Firebase: Batch sync completed", [
            'destination' => $destination,
            'total' => $results['total_items'],
            'successful' => $results['successful'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Synchroniser des données depuis Firebase
     * 
     * Méthode générique pour récupérer des données depuis Firestore ou Realtime Database
     * Gère automatiquement la désérialisation et le logging
     * 
     * @param string $source 'firestore' ou 'realtime_db'
     * @param array $options Options de configuration:
     *                       - 'collection' (pour firestore): nom de la collection
     *                       - 'document_id' (pour firestore): ID du document
     *                       - 'path' (pour realtime_db): chemin dans la DB
     *                       - 'filters' (array): filtres à appliquer
     * 
     * @return array Résultat de la récupération
     *              [
     *                  'success' => bool,
     *                  'source' => 'firestore' | 'realtime_db',
     *                  'message' => string,
     *                  'data' => array|null,
     *                  'data_count' => int,
     *                  'timestamp' => ISO8601,
     *                  'error' => null,
     *                  'response_time_ms' => float
     *              ]
     */
    public function syncFromFirebase(string $source, array $options = []): array
    {
        $startTime = microtime(true);
        $result = [
            'success' => false,
            'source' => $source,
            'message' => '',
            'data' => null,
            'data_count' => 0,
            'timestamp' => now()->toIso8601String(),
            'error' => null,
            'response_time_ms' => 0,
        ];

        try {
            // Valider les paramètres
            if (!in_array($source, ['firestore', 'realtime_db'])) {
                throw new Exception("Invalid source: {$source}. Must be 'firestore' or 'realtime_db'");
            }

            // Vérifier que le service est activé
            if (!$this->isServiceEnabled($source)) {
                throw new Exception("Service {$source} is not enabled");
            }

            // Récupérer depuis Firestore
            if ($source === 'firestore') {
                $collection = $options['collection'] ?? null;
                $documentId = $options['document_id'] ?? null;

                if (empty($collection) || empty($documentId)) {
                    throw new Exception("Missing required options for firestore: 'collection' and 'document_id'");
                }

                $data = $this->readFromFirestore($collection, $documentId);

                if ($data !== null) {
                    $result['success'] = true;
                    $result['message'] = "Data retrieved from Firestore successfully";
                    $result['data'] = $data;
                    $result['data_count'] = count($data);

                    Log::info("Firebase: Data synced from Firestore", [
                        'collection' => $collection,
                        'document_id' => $documentId,
                        'data_fields' => $result['data_count'],
                    ]);
                } else {
                    $result['success'] = false;
                    $result['message'] = "Document not found in Firestore";
                    $result['data'] = null;

                    Log::warning("Firebase: Document not found in Firestore", [
                        'collection' => $collection,
                        'document_id' => $documentId,
                    ]);
                }
            }

            // Récupérer depuis Realtime Database
            elseif ($source === 'realtime_db') {
                $path = $options['path'] ?? null;

                if (empty($path)) {
                    throw new Exception("Missing required option for realtime_db: 'path'");
                }

                $data = $this->readFromRealtimeDb($path);

                if ($data !== null) {
                    $result['success'] = true;
                    $result['message'] = "Data retrieved from Realtime Database successfully";
                    $result['data'] = $data;
                    $result['data_count'] = is_array($data) ? count($data) : 1;

                    Log::info("Firebase: Data synced from Realtime Database", [
                        'path' => $path,
                        'data_count' => $result['data_count'],
                    ]);
                } else {
                    $result['success'] = false;
                    $result['message'] = "Data not found in Realtime Database";
                    $result['data'] = null;

                    Log::warning("Firebase: Data not found in Realtime Database", [
                        'path' => $path,
                    ]);
                }
            }
        } catch (Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();
            $this->logError('Sync from Firebase', $e->getMessage(), [
                'source' => $source,
                'options' => $options,
            ]);
        }

        $result['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $result;
    }

    /**
     * Récupérer les données d'un modèle depuis Firebase
     * 
     * Wrapper pratique pour les cas courants : récupération des données d'un modèle
     * Génère automatiquement le nom de la collection au singulier vers pluriel
     * 
     * @param string $modelName Nom du modèle au singulier (ex: 'user', 'post')
     * @param string|int $modelId ID du modèle
     * @param string $source 'firestore' ou 'realtime_db' (par défaut: 'firestore')
     * 
     * @return array Résultat de la récupération (même format que syncFromFirebase)
     */
    public function syncModelFromFirebase(string $modelName, $modelId, string $source = 'firestore'): array
    {
        $modelName = strtolower(trim($modelName));
        $modelId = (string) $modelId;

        if (empty($modelName) || empty($modelId)) {
            return [
                'success' => false,
                'source' => $source,
                'message' => 'Invalid model name or ID',
                'data' => null,
                'data_count' => 0,
                'timestamp' => now()->toIso8601String(),
                'error' => 'Model name and ID are required',
                'response_time_ms' => 0,
            ];
        }

        // Générer le nom de la collection au pluriel
        $collection = $modelName . 's';

        $options = [];
        if ($source === 'firestore') {
            $options = [
                'collection' => $collection,
                'document_id' => $modelId,
            ];
        } else {
            $options = [
                'path' => "{$collection}/{$modelId}",
            ];
        }

        return $this->syncFromFirebase($source, $options);
    }

    /**
     * Récupérer un lot de documents depuis Firebase
     * 
     * Synchronise plusieurs documents/références depuis Firestore ou Realtime Database
     * Agrège les résultats avec comptage des succès/échecs
     * 
     * @param string $source 'firestore' ou 'realtime_db'
     * @param array $items Tableau d'éléments à récupérer
     *                     Pour firestore: [
     *                         {'collection': 'users', 'document_id': 'user1'},
     *                         {'collection': 'users', 'document_id': 'user2'}
     *                     ]
     *                     Pour realtime_db: [
     *                         {'path': 'users/user1'},
     *                         {'path': 'users/user2'}
     *                     ]
     * 
     * @return array Résultats agrégés
     *              [
     *                  'success' => bool (tous les items récupérés),
     *                  'source' => 'firestore' | 'realtime_db',
     *                  'message' => string,
     *                  'total_items' => int,
     *                  'retrieved' => int,
     *                  'not_found' => int,
     *                  'failed' => int,
     *                  'items' => [
     *                      {
     *                          'collection'|'path': string,
     *                          'document_id'|null: string,
     *                          'success': bool,
     *                          'data': array|null,
     *                          'message': string,
     *                      },
     *                      ...
     *                  ],
     *                  'timestamp' => ISO8601,
     *                  'response_time_ms' => float
     *              ]
     */
    public function syncBatchFromFirebase(string $source, array $items): array
    {
        $startTime = microtime(true);
        $results = [
            'success' => true,
            'source' => $source,
            'message' => "Batch retrieval from {$source} completed",
            'total_items' => count($items),
            'retrieved' => 0,
            'not_found' => 0,
            'failed' => 0,
            'items' => [],
            'timestamp' => now()->toIso8601String(),
            'response_time_ms' => 0,
        ];

        if (empty($items)) {
            return $results;
        }

        try {
            // Valider la source
            if (!in_array($source, ['firestore', 'realtime_db'])) {
                throw new Exception("Invalid source: {$source}");
            }

            // Vérifier que le service est activé
            if (!$this->isServiceEnabled($source)) {
                throw new Exception("Service {$source} is not enabled");
            }

            // Traiter chaque élément
            foreach ($items as $item) {
                $itemResult = [
                    'success' => false,
                    'message' => '',
                    'data' => null,
                ];

                try {
                    if ($source === 'firestore') {
                        $collection = $item['collection'] ?? null;
                        $documentId = $item['document_id'] ?? null;

                        if (empty($collection) || empty($documentId)) {
                            throw new Exception("Missing collection or document_id");
                        }

                        $data = $this->readFromFirestore($collection, $documentId);

                        $itemResult['collection'] = $collection;
                        $itemResult['document_id'] = $documentId;

                        if ($data !== null) {
                            $itemResult['success'] = true;
                            $itemResult['data'] = $data;
                            $itemResult['message'] = "Retrieved from Firestore";
                            $results['retrieved']++;
                        } else {
                            $itemResult['success'] = false;
                            $itemResult['message'] = "Document not found";
                            $results['not_found']++;
                        }
                    } else {
                        // realtime_db
                        $path = $item['path'] ?? null;

                        if (empty($path)) {
                            throw new Exception("Missing path");
                        }

                        $data = $this->readFromRealtimeDb($path);

                        $itemResult['path'] = $path;

                        if ($data !== null) {
                            $itemResult['success'] = true;
                            $itemResult['data'] = $data;
                            $itemResult['message'] = "Retrieved from Realtime Database";
                            $results['retrieved']++;
                        } else {
                            $itemResult['success'] = false;
                            $itemResult['message'] = "Data not found";
                            $results['not_found']++;
                        }
                    }
                } catch (Exception $e) {
                    $itemResult['success'] = false;
                    $itemResult['message'] = $e->getMessage();
                    $itemResult['data'] = null;
                    $results['failed']++;
                }

                $results['items'][] = $itemResult;
            }

            // Déterminer le succès global
            $results['success'] = $results['failed'] === 0;
            $failureCount = $results['failed'] + $results['not_found'];
            $results['message'] = "Retrieved {$results['retrieved']}/{results['total_items']} items" .
                ($failureCount > 0 ? " ({$failureCount} not found/failed)" : "");
        } catch (Exception $e) {
            $results['success'] = false;
            $results['message'] = "Batch retrieval failed: " . $e->getMessage();
            $results['failed'] = count($items);

            $this->logError('Batch retrieval from Firebase', $e->getMessage(), [
                'source' => $source,
                'items_count' => count($items),
            ]);
        }

        $results['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        Log::info("Firebase: Batch retrieval completed", [
            'source' => $source,
            'total' => $results['total_items'],
            'retrieved' => $results['retrieved'],
            'not_found' => $results['not_found'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Tester la connexion Firebase
     * 
     * @return array ['status' => 'ok'|'error', 'message' => string]
     */
    public function testConnection(): array
    {
        try {
            if (!$this->isConfigured) {
                return [
                    'status' => 'error',
                    'message' => 'Firebase n\'est pas configuré',
                ];
            }

            $check = $this->checkInternetConnection();

            if ($check['connected']) {
                return [
                    'status' => 'ok',
                    'message' => 'Connexion Firebase OK',
                    'details' => $check,
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Firebase configuré (mode mock/cache)',
                'details' => $check,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur connexion Firebase: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Synchroniser un utilisateur vers Firebase Auth (mock)
     * 
     * @param \App\Models\User $user
     * @return array Résultat de la synchronisation
     */
    public function syncUserToFirebase(\App\Models\User $user): array
    {
        $userData = [
            'uid' => (string) $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role ?? 'user',
            'is_active' => $user->is_active ?? true,
            'synced_at' => now()->toIso8601String(),
        ];

        $result = $this->syncModel('user', $user->id, $userData, 'firestore');

        $this->log('User synced to Firebase', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'status' => $result['success'] ? 'synced' : 'error',
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'message' => $result['message'] ?? '',
        ];
    }

    /**
     * Synchroniser tous les utilisateurs vers Firebase Auth (mock)
     * 
     * @return array Résultats par utilisateur
     */
    public function syncAllUsersToFirebase(): array
    {
        $users = \App\Models\User::all();
        $results = [];

        foreach ($users as $user) {
            try {
                $results[] = $this->syncUserToFirebase($user);
            } catch (Exception $e) {
                $results[] = [
                    'status' => 'error',
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'message' => $e->getMessage(),
                ];
                $this->logError('Sync user to Firebase', $e->getMessage(), [
                    'user_id' => $user->id,
                ]);
            }
        }

        $this->log('All users synced to Firebase', [
            'total' => count($results),
            'synced' => count(array_filter($results, fn($r) => $r['status'] === 'synced')),
            'errors' => count(array_filter($results, fn($r) => $r['status'] === 'error')),
        ]);

        return $results;
    }

}
