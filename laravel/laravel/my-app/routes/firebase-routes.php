<?php

/**
 * Routes Firebase - Exemples d'intégration
 * 
 * Ajouter ces routes à votre fichier routes/api.php pour tester les exemples Firebase
 * 
 * Usage:
 * Route::middleware(['auth:sanctum'])->group(function () {
 *     include 'firebase-routes.php';
 * });
 * 
 * Ou copier les lignes pertinentes directement dans api.php
 */

use App\Http\Controllers\Api\FirebaseExampleController;
use Illuminate\Support\Facades\Route;

/**
 * ====================================================================
 * FIREBASE ROUTES - EXAMPLES
 * ====================================================================
 * 
 * Toutes les routes Firebase nécessitent l'authentification Sanctum
 * Groupe de routes protégé par le middleware 'auth:sanctum'
 */

Route::middleware(['auth:sanctum'])->prefix('firebase')->group(function () {

    /**
     * Vérifier la configuration Firebase
     * GET /api/firebase/check-configuration
     * 
     * Retourne le statut de configuration et les services activés
     */
    Route::get('/check-configuration', [FirebaseExampleController::class, 'checkConfiguration'])
        ->name('firebase.check-configuration');

    /**
     * Vérifier la connexion Internet et la connectivité Firebase
     * GET /api/firebase/check-internet
     * 
     * Teste la connectivité Internet et l'accès aux services Firebase
     */
    Route::get('/check-internet', [FirebaseExampleController::class, 'checkInternetConnection'])
        ->name('firebase.check-internet');

    /**
     * ====================================================================
     * FIRESTORE ROUTES
     * ====================================================================
     */

    /**
     * Écrire un document dans Firestore
     * POST /api/firebase/firestore/write
     * 
     * Body:
     * {
     *     "collection": "users",
     *     "document_id": "user123",
     *     "data": {
     *         "name": "John Doe",
     *         "email": "john@example.com"
     *     }
     * }
     */
    Route::post('/firestore/write', [FirebaseExampleController::class, 'writeToFirestore'])
        ->name('firebase.firestore.write')
        ->middleware('throttle:10,1'); // Rate limiting: 10 per minute

    /**
     * Lire un document depuis Firestore
     * GET /api/firebase/firestore/read/{collection}/{document_id}
     * 
     * Exemple:
     * GET /api/firebase/firestore/read/users/user123
     */
    Route::get('/firestore/read/{collection}/{document_id}', [FirebaseExampleController::class, 'readFromFirestore'])
        ->name('firebase.firestore.read')
        ->middleware('throttle:30,1'); // Rate limiting: 30 per minute

    /**
     * ====================================================================
     * REALTIME DATABASE ROUTES
     * ====================================================================
     */

    /**
     * Écrire des données dans Realtime Database
     * POST /api/firebase/realtime-db/write
     * 
     * Body:
     * {
     *     "path": "users/user123",
     *     "data": {
     *         "name": "John Doe",
     *         "online": true
     *     }
     * }
     */
    Route::post('/realtime-db/write', [FirebaseExampleController::class, 'writeToRealtimeDb'])
        ->name('firebase.realtime-db.write')
        ->middleware('throttle:10,1');

    /**
     * Lire des données depuis Realtime Database
     * GET /api/firebase/realtime-db/read/{path}
     * 
     * Exemple:
     * GET /api/firebase/realtime-db/read/users/user123
     * GET /api/firebase/realtime-db/read/users
     */
    Route::get('/realtime-db/read/{path}', [FirebaseExampleController::class, 'readFromRealtimeDb'])
        ->where('path', '.*') // Accepter les chemins avec des slashes
        ->name('firebase.realtime-db.read')
        ->middleware('throttle:30,1');

    /**
     * ====================================================================
     * CLOUD MESSAGING ROUTES (Push Notifications)
     * ====================================================================
     */

    /**
     * Envoyer une notification push
     * POST /api/firebase/messaging/send
     * 
     * Body:
     * {
     *     "token": "device-token-here",
     *     "title": "Notification Title",
     *     "body": "Notification message",
     *     "data": {
     *         "key": "value"
     *     }
     * }
     */
    Route::post('/messaging/send', [FirebaseExampleController::class, 'sendPushNotification'])
        ->name('firebase.messaging.send')
        ->middleware('throttle:5,1'); // Rate limiting: 5 per minute

    /**
     * ====================================================================
     * CLOUD STORAGE ROUTES
     * ====================================================================
     */

    /**
     * Uploader un fichier vers Cloud Storage
     * POST /api/firebase/storage/upload
     * 
     * Form Data:
     * - file: (file)
     * - path: uploads/documents
     */
    Route::post('/storage/upload', [FirebaseExampleController::class, 'uploadToStorage'])
        ->name('firebase.storage.upload')
        ->middleware('throttle:5,1'); // Rate limiting: 5 per minute

    /**
     * ====================================================================
     * DATA SYNC ROUTES
     * ====================================================================
     */

    /**
     * Synchroniser des données vers Firebase
     * POST /api/firebase/sync
     */
    Route::post('/sync', [FirebaseExampleController::class, 'syncToFirebase'])
        ->name('firebase.sync')
        ->middleware('throttle:30,1'); // Rate limiting: 30 per minute

    /**
     * Synchroniser un modèle vers Firebase
     * POST /api/firebase/sync-model
     */
    Route::post('/sync-model', [FirebaseExampleController::class, 'syncModel'])
        ->name('firebase.sync-model')
        ->middleware('throttle:30,1'); // Rate limiting: 30 per minute

    /**
     * Synchroniser plusieurs éléments (batch) vers Firebase
     * POST /api/firebase/sync-batch
     */
    Route::post('/sync-batch', [FirebaseExampleController::class, 'syncBatch'])
        ->name('firebase.sync-batch')
        ->middleware('throttle:20,1'); // Rate limiting: 20 per minute

    /**
     * ====================================================================
     * DATA RETRIEVAL ROUTES (Sync From Firebase)
     * ====================================================================
     */

    /**
     * Récupérer des données depuis Firebase
     * POST /api/firebase/sync-from
     * 
     * Endpoint générique pour récupérer des données depuis Firestore ou Realtime Database
     */
    Route::post('/sync-from', [FirebaseExampleController::class, 'syncFromFirebase'])
        ->name('firebase.sync-from')
        ->middleware('throttle:30,1'); // Rate limiting: 30 per minute

    /**
     * Récupérer les données d'un modèle depuis Firebase
     * POST /api/firebase/sync-model-from
     * 
     * Endpoint pratique pour récupérer les données d'un modèle spécifique
     */
    Route::post('/sync-model-from', [FirebaseExampleController::class, 'syncModelFromFirebase'])
        ->name('firebase.sync-model-from')
        ->middleware('throttle:30,1'); // Rate limiting: 30 per minute

    /**
     * Récupérer un lot de documents depuis Firebase
     * POST /api/firebase/sync-batch-from
     * 
     * Endpoint pour récupérer plusieurs documents/références en une seule requête
     */
    Route::post('/sync-batch-from', [FirebaseExampleController::class, 'syncBatchFromFirebase'])
        ->name('firebase.sync-batch-from')
        ->middleware('throttle:20,1'); // Rate limiting: 20 per minute
});

/**
 * ====================================================================
 * NOTES D'INTÉGRATION
 * ====================================================================
 * 
 * 1. ACTIVATION DES SERVICES
 *    - Les routes retournent 403 si le service n'est pas activé dans .env
 *    - Activer les services dans: FIREBASE_ENABLE_*=true
 * 
 * 2. RATE LIMITING
 *    - Les routes ont des limites de débit pour éviter les abus
 *    - Modifier les valeurs selon vos besoins
 * 
 * 3. AUTHENTIFICATION
 *    - Toutes les routes nécessitent un token Sanctum valide
     - Header requis: Authorization: Bearer {token}
 * 
 * 4. ERROR HANDLING
 *    - Les erreurs retournent des codes HTTP appropriés
 *    - Vérifier les logs: storage/logs/laravel.log
 * 
 * 5. DOCUMENTATION
 *    - Voir FIREBASE.md pour la configuration complète
 *    - Voir FirebaseExampleController pour l'implémentation
 * 
 * ====================================================================
 */
