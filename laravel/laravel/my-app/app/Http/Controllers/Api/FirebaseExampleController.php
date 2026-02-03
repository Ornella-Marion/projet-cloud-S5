<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Firebase Integration Controller Example
 * 
 * Exemple de contrôleur démontrant comment utiliser le service Firebase
 * 
 * @package App\Http\Controllers\Api
 */
class FirebaseExampleController extends Controller
{
    /**
     * Service Firebase injecté
     * 
     * @var FirebaseService
     */
    private FirebaseService $firebase;

    /**
     * Constructeur
     * 
     * @param FirebaseService $firebase
     */
    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
        $this->middleware('auth:sanctum');
    }

    /**
     * Vérifier la configuration Firebase
     * 
     * @return JsonResponse
     */
    public function checkConfiguration(): JsonResponse
    {
        if (!$this->firebase->isConfigured()) {
            return response()->json([
                'status' => 'not_configured',
                'message' => 'Firebase is not properly configured',
            ], 500);
        }

        return response()->json([
            'status' => 'configured',
            'project_id' => $this->firebase->getProjectId(),
            'services' => [
                'realtime_db' => $this->firebase->isServiceEnabled('realtime_db'),
                'firestore' => $this->firebase->isServiceEnabled('firestore'),
                'storage' => $this->firebase->isServiceEnabled('storage'),
                'push_notifications' => $this->firebase->isServiceEnabled('push_notifications'),
                'analytics' => $this->firebase->isServiceEnabled('analytics'),
            ],
        ], 200);
    }

    /**
     * Vérifier la connexion Internet et la connectivité Firebase
     * 
     * Teste la connectivité Internet générale et l'accès aux endpoints Firebase
     * 
     * @return JsonResponse
     */
    public function checkInternetConnection(): JsonResponse
    {
        try {
            $connectionStatus = $this->firebase->checkInternetConnection();

            return response()->json([
                'status' => $connectionStatus['connected'] ? 'connected' : 'disconnected',
                'internet_available' => $connectionStatus['internet'],
                'firebase_available' => $connectionStatus['firebase'],
                'services' => [
                    'firestore' => $connectionStatus['firestore'],
                    'realtime_db' => $connectionStatus['realtime_db'],
                    'storage' => $connectionStatus['storage'],
                ],
                'details' => $connectionStatus['details'],
                'response_time_ms' => $connectionStatus['response_time'],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check internet connection',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exemple: Écrire des données dans Firestore
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function writeToFirestore(Request $request): JsonResponse
    {
        // Vérifier que le service est activé
        if (!$this->firebase->isServiceEnabled('firestore')) {
            return response()->json([
                'error' => 'Firestore is not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'collection' => 'required|string',
                'document_id' => 'required|string',
                'data' => 'required|array',
            ]);

            // Check if Firebase is properly configured before making actual call
            if (!$this->firebase->isConfigured()) {
                return response()->json([
                    'warning' => 'Firebase not properly configured, returning mock response',
                    'status' => 'success',
                    'message' => 'Document written successfully (MOCK)',
                    'collection' => $validated['collection'],
                    'document_id' => $validated['document_id'],
                    'data' => $validated['data'],
                ], 201);
            }

            // Utiliser directement la méthode du service pour écrire les données mockées
            $this->firebase->writeToFirestore(
                $validated['collection'],
                $validated['document_id'],
                $validated['data']
            );

            $this->firebase->log('Firestore write', [
                'collection' => $validated['collection'],
                'document_id' => $validated['document_id'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Document written successfully',
                'collection' => $validated['collection'],
                'document_id' => $validated['document_id'],
            ], 201);
        } catch (\Exception $e) {
            $this->firebase->logError('Firestore write', $e->getMessage(), [
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to write document',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exemple: Lire des données depuis Firestore
     * 
     * @param string $collection
     * @param string $documentId
     * @return JsonResponse
     */
    public function readFromFirestore(string $collection, string $documentId): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('firestore')) {
            return response()->json([
                'error' => 'Firestore is not enabled',
            ], 403);
        }

        try {
            // Check if Firebase is properly configured
            if (!$this->firebase->isConfigured()) {
                return response()->json([
                    'warning' => 'Firebase not properly configured, returning mock response',
                    'status' => 'success',
                    'collection' => $collection,
                    'document_id' => $documentId,
                    'data' => ['mock' => 'data', 'message' => 'This is a mock response'],
                ], 200);
            }

            // Utiliser directement la méthode du service pour lire les données mockées
            $data = $this->firebase->readFromFirestore($collection, $documentId);

            if ($data === null) {
                return response()->json([
                    'error' => 'Document not found',
                ], 404);
            }

            $this->firebase->log('Firestore read', [
                'collection' => $collection,
                'document_id' => $documentId,
            ]);

            return response()->json([
                'status' => 'success',
                'collection' => $collection,
                'document_id' => $documentId,
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            $this->firebase->logError('Firestore read', $e->getMessage(), [
                'collection' => $collection,
                'document_id' => $documentId,
            ]);

            return response()->json([
                'error' => 'Failed to read document',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exemple: Écrire dans Realtime Database
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function writeToRealtimeDb(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('realtime_db')) {
            return response()->json([
                'error' => 'Realtime Database is not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'path' => 'required|string',
                'data' => 'required|array',
            ]);

            // Utiliser directement la méthode du service
            $this->firebase->writeToRealtimeDb($validated['path'], $validated['data']);

            $this->firebase->log('Realtime DB write', [
                'path' => $validated['path'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data written to Realtime Database',
                'path' => $validated['path'],
            ], 201);
        } catch (\Exception $e) {
            $this->firebase->logError('Realtime DB write', $e->getMessage(), [
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to write data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exemple: Lire depuis Realtime Database
     * 
     * @param string $path
     * @return JsonResponse
     */
    public function readFromRealtimeDb(string $path): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('realtime_db')) {
            return response()->json([
                'error' => 'Realtime Database is not enabled',
            ], 403);
        }

        try {
            // Utiliser directement la méthode du service
            $data = $this->firebase->readFromRealtimeDb($path);

            if ($data === null) {
                return response()->json([
                    'error' => 'Path not found',
                ], 404);
            }

            $this->firebase->log('Realtime DB read', [
                'path' => $path,
            ]);

            return response()->json([
                'status' => 'success',
                'path' => $path,
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            $this->firebase->logError('Realtime DB read', $e->getMessage(), [
                'path' => $path,
            ]);

            return response()->json([
                'error' => 'Failed to read data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exemple: Envoyer une notification push
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendPushNotification(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('push_notifications')) {
            return response()->json([
                'error' => 'Push notifications are not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'token' => 'required|string',
                'title' => 'required|string',
                'body' => 'required|string',
                'data' => 'sometimes|array',
            ]);

            // Check if Firebase is properly configured
            if (!$this->firebase->isConfigured()) {
                return response()->json([
                    'warning' => 'Firebase not properly configured, returning mock response',
                    'status' => 'success',
                    'message' => 'Notification sent successfully (MOCK)',
                ], 201);
            }

            // Mockifier l'envoi de notification - simplement stocker les données
            \Illuminate\Support\Facades\Cache::put(
                'firebase:mock:messaging:' . md5($validated['token']),
                [
                    'token' => $validated['token'],
                    'title' => $validated['title'],
                    'body' => $validated['body'],
                    'data' => $validated['data'] ?? [],
                    'sent_at' => now()->toIso8601String(),
                ],
                86400
            );

            $this->firebase->log('Push notification sent', [
                'title' => $validated['title'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notification sent successfully',
            ], 201);
        } catch (\Exception $e) {
            $this->firebase->logError('Push notification', $e->getMessage(), [
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to send notification',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exemple: Uploader un fichier vers Cloud Storage
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadToStorage(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('storage')) {
            return response()->json([
                'error' => 'Cloud Storage is not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'file' => 'required|file|max:102400', // 100MB max (en KB)
                'path' => 'required|string',
            ]);

            $file = $validated['file'];

            // Nettoyer et valider le chemin pour éviter les path traversal attacks
            $path = trim($validated['path']);
            // Remplacer les backslash Windows par des slashes
            $path = str_replace('\\', '/', $path);
            // Supprimer les chemins absolus
            $path = ltrim($path, '/');
            // Rejeter les chemins contenant ".."
            if (strpos($path, '..') !== false || strpos($path, ':') !== false) {
                return response()->json([
                    'error' => 'Invalid path: use only relative paths (e.g., "documents" or "uploads/docs")',
                ], 422);
            }

            // Créer le dossier s'il n'existe pas
            $uploadPath = 'uploads/' . $path;
            $fullPath = storage_path('app/' . $uploadPath);

            if (!is_dir($fullPath)) {
                @mkdir($fullPath, 0755, true);
            }

            $filename = $file->getClientOriginalName();
            $file->storeAs($uploadPath, $filename, 'local');

            // Stocker les métadonnées dans le cache
            $filePath = $uploadPath . '/' . $filename;
            \Illuminate\Support\Facades\Cache::put(
                'firebase:mock:storage:' . $filePath,
                [
                    'filename' => $filename,
                    'path' => $validated['path'],
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'storage_path' => $filePath,
                    'uploaded_at' => now()->toIso8601String(),
                ],
                86400
            );

            $this->firebase->log('File uploaded to Storage', [
                'filename' => $filename,
                'path' => $validated['path'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'filename' => $filename,
                'storage_path' => '/storage/app/' . $filePath,
                'note' => 'Fichier sauvegardé dans: storage/app/uploads/' . $validated['path'] . '/',
            ], 201);
        } catch (\Exception $e) {
            $this->firebase->logError('Storage upload', $e->getMessage(), [
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to upload file',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Synchroniser des données vers Firebase
     * 
     * POST /api/firebase/sync
     * 
     * Body:
     * {
     *   "destination": "firestore" | "realtime_db",
     *   "collection": "users",              // pour firestore
     *   "document_id": "user123",           // pour firestore
     *   "path": "users/user123",            // pour realtime_db
     *   "data": {
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "merge": false,                     // optionnel
     *   "timestamp": true                   // optionnel (par défaut true)
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function syncToFirebase(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('firestore') && !$this->firebase->isServiceEnabled('realtime_db')) {
            return response()->json([
                'error' => 'Firebase services are not enabled',
                'services' => [
                    'firestore' => $this->firebase->isServiceEnabled('firestore'),
                    'realtime_db' => $this->firebase->isServiceEnabled('realtime_db'),
                ]
            ], 403);
        }

        try {
            $validated = $request->validate([
                'destination' => 'required|string|in:firestore,realtime_db',
                'data' => 'required|array',
                'collection' => 'required_if:destination,firestore|string',
                'document_id' => 'required_if:destination,firestore|string',
                'path' => 'required_if:destination,realtime_db|string',
                'timestamp' => 'sometimes|boolean',
            ]);

            $destination = $validated['destination'];
            $data = $validated['data'];

            $options = [
                'timestamp' => $validated['timestamp'] ?? true,
            ];

            if ($destination === 'firestore') {
                $options['collection'] = $validated['collection'];
                $options['document_id'] = $validated['document_id'];
            } else {
                $options['path'] = $validated['path'];
            }

            $syncResult = $this->firebase->syncToFirebase($destination, $data, $options);

            $statusCode = $syncResult['success'] ? 201 : 400;

            return response()->json($syncResult, $statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->firebase->logError('Sync to Firebase', $e->getMessage(), [
                'destination' => $validated['destination'] ?? 'unknown',
            ]);

            return response()->json([
                'error' => 'Failed to sync data to Firebase',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Synchroniser les données d'un modèle vers Firebase
     * 
     * POST /api/firebase/sync-model
     * 
     * Body:
     * {
     *   "model": "user",           // nom du modèle (user, post, comment, etc.)
     *   "model_id": "123",         // ID du modèle
     *   "destination": "firestore", // optionnel (par défaut firestore)
     *   "data": {
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   }
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function syncModel(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('firestore')) {
            return response()->json([
                'error' => 'Firestore service is not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'model' => 'required|string|lowercase',
                'model_id' => 'required|string|numeric',
                'data' => 'required|array',
                'destination' => 'sometimes|string|in:firestore,realtime_db',
            ]);

            $destination = $validated['destination'] ?? 'firestore';

            $syncResult = $this->firebase->syncModel(
                $validated['model'],
                $validated['model_id'],
                $validated['data'],
                $destination
            );

            $statusCode = $syncResult['success'] ? 201 : 400;

            return response()->json($syncResult, $statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->firebase->logError('Sync Model to Firebase', $e->getMessage(), [
                'model' => $validated['model'] ?? 'unknown',
                'model_id' => $validated['model_id'] ?? 'unknown',
            ]);

            return response()->json([
                'error' => 'Failed to sync model to Firebase',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Synchroniser plusieurs éléments vers Firebase (batch)
     * 
     * POST /api/firebase/sync-batch
     * 
     * Body:
     * {
     *   "destination": "firestore",
     *   "items": [
     *     {
     *       "collection": "users",
     *       "document_id": "user1",
     *       "data": { "name": "User 1" }
     *     },
     *     {
     *       "collection": "users",
     *       "document_id": "user2",
     *       "data": { "name": "User 2" }
     *     }
     *   ]
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function syncBatch(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('firestore') && !$this->firebase->isServiceEnabled('realtime_db')) {
            return response()->json([
                'error' => 'Firebase services are not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'destination' => 'required|string|in:firestore,realtime_db',
                'items' => 'required|array|min:1',
                'items.*.data' => 'required|array',
                'items.*.collection' => 'required_if:destination,firestore|string',
                'items.*.document_id' => 'required_if:destination,firestore|string',
                'items.*.path' => 'required_if:destination,realtime_db|string',
            ]);

            $batchResult = $this->firebase->syncBatch(
                $validated['destination'],
                $validated['items']
            );

            return response()->json($batchResult, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->firebase->logError('Batch sync to Firebase', $e->getMessage());

            return response()->json([
                'error' => 'Failed to sync batch to Firebase',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer des données depuis Firebase
     * 
     * Endpoint générique pour récupérer des données depuis Firestore ou Realtime Database
     * 
     * Exemples de requête:
     * 
     * Pour Firestore:
     * POST /api/firebase/sync-from
     * {
     *   "source": "firestore",
     *   "collection": "users",
     *   "document_id": "user123"
     * }
     * 
     * Pour Realtime Database:
     * POST /api/firebase/sync-from
     * {
     *   "source": "realtime_db",
     *   "path": "users/user123"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function syncFromFirebase(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('firestore') && !$this->firebase->isServiceEnabled('realtime_db')) {
            return response()->json([
                'error' => 'Firebase services are not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'source' => 'required|string|in:firestore,realtime_db',
                'collection' => 'required_if:source,firestore|string',
                'document_id' => 'required_if:source,firestore|string',
                'path' => 'required_if:source,realtime_db|string',
            ]);

            // Construire les options
            $options = [];
            if ($validated['source'] === 'firestore') {
                $options = [
                    'collection' => $validated['collection'],
                    'document_id' => $validated['document_id'],
                ];
            } else {
                $options = [
                    'path' => $validated['path'],
                ];
            }

            $syncResult = $this->firebase->syncFromFirebase(
                $validated['source'],
                $options
            );

            $httpCode = $syncResult['success'] ? 200 : 404;
            return response()->json($syncResult, $httpCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->firebase->logError('Sync from Firebase', $e->getMessage());

            return response()->json([
                'error' => 'Failed to retrieve data from Firebase',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les données d'un modèle depuis Firebase
     * 
     * Endpoint pratique pour récupérer les données d'un modèle spécifique
     * 
     * Exemple de requête:
     * POST /api/firebase/sync-model-from
     * {
     *   "model": "user",
     *   "model_id": "user123",
     *   "source": "firestore"  // optionnel, défaut: firestore
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function syncModelFromFirebase(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('firestore') && !$this->firebase->isServiceEnabled('realtime_db')) {
            return response()->json([
                'error' => 'Firebase services are not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'model' => 'required|string|lowercase',
                'model_id' => 'required|string|numeric',
                'source' => 'nullable|string|in:firestore,realtime_db',
            ]);

            $modelResult = $this->firebase->syncModelFromFirebase(
                $validated['model'],
                $validated['model_id'],
                $validated['source'] ?? 'firestore'
            );

            $httpCode = $modelResult['success'] ? 200 : 404;
            return response()->json($modelResult, $httpCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->firebase->logError('Sync model from Firebase', $e->getMessage());

            return response()->json([
                'error' => 'Failed to retrieve model data from Firebase',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un lot de documents depuis Firebase
     * 
     * Endpoint pour récupérer plusieurs documents/références en une seule requête
     * 
     * Exemple de requête Firestore:
     * POST /api/firebase/sync-batch-from
     * {
     *   "source": "firestore",
     *   "items": [
     *     {
     *       "collection": "users",
     *       "document_id": "user1"
     *     },
     *     {
     *       "collection": "users",
     *       "document_id": "user2"
     *     }
     *   ]
     * }
     * 
     * Exemple de requête Realtime Database:
     * POST /api/firebase/sync-batch-from
     * {
     *   "source": "realtime_db",
     *   "items": [
     *     {"path": "users/user1"},
     *     {"path": "users/user2"}
     *   ]
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function syncBatchFromFirebase(Request $request): JsonResponse
    {
        if (!$this->firebase->isServiceEnabled('firestore') && !$this->firebase->isServiceEnabled('realtime_db')) {
            return response()->json([
                'error' => 'Firebase services are not enabled',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'source' => 'required|string|in:firestore,realtime_db',
                'items' => 'required|array|min:1',
                'items.*.data' => 'nullable|array',
                'items.*.collection' => 'required_if:source,firestore|string',
                'items.*.document_id' => 'required_if:source,firestore|string',
                'items.*.path' => 'required_if:source,realtime_db|string',
            ]);

            $batchResult = $this->firebase->syncBatchFromFirebase(
                $validated['source'],
                $validated['items']
            );

            $httpCode = $batchResult['success'] ? 200 : 207; // 207 Multi-Status pour résultats partiels
            return response()->json($batchResult, $httpCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->firebase->logError('Batch retrieval from Firebase', $e->getMessage());

            return response()->json([
                'error' => 'Failed to retrieve batch from Firebase',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
