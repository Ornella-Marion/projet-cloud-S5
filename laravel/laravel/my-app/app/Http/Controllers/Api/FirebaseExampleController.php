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

            $firestore = $this->firebase->getFirestoreClient();

            // Écrire le document
            $firestore
                ->collection($validated['collection'])
                ->document($validated['document_id'])
                ->set($validated['data']);

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

            $firestore = $this->firebase->getFirestoreClient();

            // Lire le document
            $snapshot = $firestore
                ->collection($collection)
                ->document($documentId)
                ->snapshot();

            if (!$snapshot->exists()) {
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
                'data' => $snapshot->data(),
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

            $database = $this->firebase->getRealtimeDatabaseClient();

            // Écrire les données
            $database
                ->getReference($validated['path'])
                ->set($validated['data']);

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
            $database = $this->firebase->getRealtimeDatabaseClient();

            // Lire les données
            $data = $database
                ->getReference($path)
                ->getValue();

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

            $messaging = $this->firebase->getMessagingClient();

            // Créer le message
            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget(
                'token',
                $validated['token']
            )->withNotification(
                \Kreait\Firebase\Messaging\Notification::create(
                    $validated['title'],
                    $validated['body']
                )
            );

            // Ajouter les données personnalisées si présentes
            if (isset($validated['data'])) {
                $message = $message->withData($validated['data']);
            }

            // Envoyer
            $messaging->send($message);

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
                'file' => 'required|file|max:10240', // 10MB max
                'path' => 'required|string',
            ]);

            $storage = $this->firebase->getStorageClient();
            $bucket = $storage->bucket($this->firebase->getStorageBucket());

            // Uploader le fichier
            $file = $validated['file'];
            $bucket->upload(
                fopen($file->getRealPath(), 'r'),
                [
                    'name' => $validated['path'] . '/' . $file->getClientOriginalName(),
                    'metadata' => [
                        'contentType' => $file->getMimeType(),
                    ],
                ]
            );

            $this->firebase->log('File uploaded to Storage', [
                'filename' => $file->getClientOriginalName(),
                'path' => $validated['path'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'filename' => $file->getClientOriginalName(),
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
}
