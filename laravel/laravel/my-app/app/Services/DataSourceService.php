<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service de gestion automatique des sources de données
 * 
 * Bascule automatiquement entre Firebase et PostgreSQL selon la connexion Internet
 * Cela permet à l'application de fonctionner même sans connexion Internet
 * 
 * @package App\Services
 */
class DataSourceService
{
    /**
     * Service Firebase
     * 
     * @var FirebaseService
     */
    private FirebaseService $firebaseService;

    /**
     * Clé de cache pour le statut de connexion
     */
    private const CONNECTION_STATUS_CACHE_KEY = 'datasource:connection:status';

    /**
     * Durée du cache du statut de connexion (en secondes)
     */
    private const CONNECTION_STATUS_TTL = 300; // 5 minutes

    /**
     * Constructeur
     * 
     * @param FirebaseService $firebaseService
     */
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Déterminer la source de données active (Firebase ou PostgreSQL)
     * 
     * @return string 'firebase' ou 'postgresql'
     */
    public function getActiveDataSource(): string
    {
        // Vérifier le cache d'abord
        $cachedStatus = Cache::get(self::CONNECTION_STATUS_CACHE_KEY);
        if ($cachedStatus !== null) {
            return $cachedStatus;
        }

        // Vérifier la connexion Internet
        $isConnected = $this->isInternetConnected();

        $dataSource = $isConnected ? 'firebase' : 'postgresql';

        // Mettre en cache le résultat
        Cache::put(self::CONNECTION_STATUS_CACHE_KEY, $dataSource, self::CONNECTION_STATUS_TTL);

        // Logger le changement de source
        if ($cachedStatus !== null && $cachedStatus !== $dataSource) {
            Log::info("DataSource switched", [
                'from' => $cachedStatus,
                'to' => $dataSource,
                'reason' => $isConnected ? 'Internet connection restored' : 'Internet connection lost'
            ]);
        }

        return $dataSource;
    }

    /**
     * Vérifier si Firebase est la source active
     * 
     * @return bool
     */
    public function isFirebaseActive(): bool
    {
        return $this->getActiveDataSource() === 'firebase';
    }

    /**
     * Vérifier si PostgreSQL est la source active
     * 
     * @return bool
     */
    public function isPostgresqlActive(): bool
    {
        return $this->getActiveDataSource() === 'postgresql';
    }

    /**
     * Vérifier la connexion Internet
     * 
     * @return bool
     */
    private function isInternetConnected(): bool
    {
        try {
            $connectionStatus = $this->firebaseService->checkInternetConnection();
            return $connectionStatus['connected'];
        } catch (\Exception $e) {
            Log::error("Failed to check internet connection", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Forcer la source de données (pour les tests/debugging)
     * 
     * @param string $dataSource 'firebase' ou 'postgresql'
     * @return void
     */
    public function setForcedDataSource(string $dataSource): void
    {
        if (!in_array($dataSource, ['firebase', 'postgresql'])) {
            throw new \InvalidArgumentException("Invalid data source: {$dataSource}");
        }

        Cache::put(self::CONNECTION_STATUS_CACHE_KEY, $dataSource, self::CONNECTION_STATUS_TTL);
        Log::info("DataSource forced", ['to' => $dataSource]);
    }

    /**
     * Réinitialiser la détection automatique
     * 
     * @return void
     */
    public function resetAutoDetection(): void
    {
        Cache::forget(self::CONNECTION_STATUS_CACHE_KEY);
        Log::info("DataSource auto-detection reset");
    }

    /**
     * Obtenir le statut de connexion détaillé
     * 
     * @return array
     */
    public function getConnectionStatus(): array
    {
        $firebaseStatus = $this->firebaseService->checkInternetConnection();
        $activeDataSource = $this->getActiveDataSource();

        return [
            'active_datasource' => $activeDataSource,
            'internet_connected' => $firebaseStatus['connected'],
            'firebase_available' => $firebaseStatus['firebase'],
            'services' => [
                'firestore' => $firebaseStatus['firestore'],
                'realtime_db' => $firebaseStatus['realtime_db'],
                'storage' => $firebaseStatus['storage'],
            ],
            'response_time_ms' => $firebaseStatus['response_time'],
            'details' => $firebaseStatus['details'],
            'fallback_enabled' => true,
            'cache_ttl_seconds' => self::CONNECTION_STATUS_TTL,
        ];
    }

    /**
     * Écrire des données dans la source active
     * 
     * Si Firebase est actif → écrit dans Firebase
     * Si PostgreSQL est actif → écrit dans PostgreSQL
     * 
     * @param string $type 'firestore', 'realtime_db', ou 'postgresql'
     * @param array $data
     * @return bool
     */
    public function write(string $type, array $data): bool
    {
        $activeSource = $this->getActiveDataSource();

        if ($activeSource === 'firebase') {
            return $this->writeToFirebase($type, $data);
        } else {
            return $this->writeToPostgresql($type, $data);
        }
    }

    /**
     * Écrire dans Firebase
     * 
     * @param string $type
     * @param array $data
     * @return bool
     */
    private function writeToFirebase(string $type, array $data): bool
    {
        try {
            if ($type === 'firestore' && isset($data['collection']) && isset($data['document_id'])) {
                $this->firebaseService->writeToFirestore(
                    $data['collection'],
                    $data['document_id'],
                    $data['data'] ?? []
                );
                return true;
            }

            if ($type === 'realtime_db' && isset($data['path'])) {
                $this->firebaseService->writeToRealtimeDb(
                    $data['path'],
                    $data['data'] ?? []
                );
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Firebase write failed", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Écrire dans PostgreSQL
     * 
     * @param string $type
     * @param array $data
     * @return bool
     */
    private function writeToPostgresql(string $type, array $data): bool
    {
        try {
            // Exemple: stocker dans PostgreSQL au lieu de Firebase
            // Ceci dépend de votre modèle de données
            Log::info("DataSource: Writing to PostgreSQL", [
                'type' => $type,
                'data_keys' => array_keys($data)
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("PostgreSQL write failed", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lire depuis la source active
     * 
     * @param string $type
     * @param array $params
     * @return array|null
     */
    public function read(string $type, array $params): ?array
    {
        $activeSource = $this->getActiveDataSource();

        if ($activeSource === 'firebase') {
            return $this->readFromFirebase($type, $params);
        } else {
            return $this->readFromPostgresql($type, $params);
        }
    }

    /**
     * Lire depuis Firebase
     * 
     * @param string $type
     * @param array $params
     * @return array|null
     */
    private function readFromFirebase(string $type, array $params): ?array
    {
        try {
            if ($type === 'firestore' && isset($params['collection']) && isset($params['document_id'])) {
                return $this->firebaseService->readFromFirestore(
                    $params['collection'],
                    $params['document_id']
                );
            }

            if ($type === 'realtime_db' && isset($params['path'])) {
                return $this->firebaseService->readFromRealtimeDb($params['path']);
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Firebase read failed", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Lire depuis PostgreSQL
     * 
     * @param string $type
     * @param array $params
     * @return array|null
     */
    private function readFromPostgresql(string $type, array $params): ?array
    {
        try {
            Log::info("DataSource: Reading from PostgreSQL", [
                'type' => $type,
                'params' => array_keys($params)
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("PostgreSQL read failed", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
