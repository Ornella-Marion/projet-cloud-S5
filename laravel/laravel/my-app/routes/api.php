<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LoginAttemptAnalyticsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DataSourceController;
use App\Http\Controllers\Api\RoadController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoadworkController;
use App\Http\Controllers\Api\RoadworkPhotoController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\FirebaseTokenController;
use App\Http\Controllers\Api\StatisticsController;


// Route Swagger UI
Route::get('/documentation', function () {
    $openapiyaml = storage_path('api-docs/openapi.yaml');
    if (!file_exists($openapiyaml)) {
        abort(404, 'OpenAPI spec not found');
    }

    $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>API Documentation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui.min.css">
  </head>
  <body>
    <div id="swagger-ui"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-bundle.min.js"></script>
    <script>
    window.onload = function() {
      SwaggerUIBundle({
        url: "/api/openapi.yaml",
        dom_id: '#swagger-ui',
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIBundle.SwaggerUIStandalonePreset
        ],
        layout: "BaseLayout",
        deepLinking: true
      })
    }
    </script>
  </body>
</html>
HTML;
    return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
});

Route::get('/openapi.yaml', function () {
    $openapiyaml = storage_path('api-docs/openapi.yaml');
    if (!file_exists($openapiyaml)) {
        abort(404, 'OpenAPI spec not found');
    }
    return response()->file($openapiyaml, [
        'Content-Type' => 'text/plain; charset=utf-8',
        'Access-Control-Allow-Origin' => '*',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup'])->middleware('throttle:5,1');
     Route::post('/register', [AuthController::class, 'signup']) // Ajout de la route conventionnelle
        ->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1'); 
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::post('/unlock-account/{userId}', [AuthController::class, 'unlockAccount'])->middleware('auth:sanctum');
    Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'getCurrentUser'])->middleware('auth:sanctum');
    Route::get('/locked-accounts', [AuthController::class, 'getLockedAccounts'])->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/unlock-account/{userId}', [AuthController::class, 'unlockAccount'])->middleware('auth:sanctum');
    // Inscription par le manager uniquement
    Route::post('/manager-signup', [AuthController::class, 'managerSignup'])->middleware('auth:sanctum');
});

// Routes Admin - Account Management
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    // Déverrouillage direct (route spécifique demandée) - Manager only
    Route::post('/unlock/{id}', [AdminController::class, 'unlock'])
        ->middleware(['role:manager,admin', 'throttle:10,1'])
        ->name('admin.unlock');

    // Gestion complète des verrous de compte
    Route::get('/users/locked', [AdminController::class, 'getLockedUsers']);
    Route::get('/users/{userId}/lock-status', [AdminController::class, 'getLockStatus']);
    Route::post('/users/{userId}/unlock', [AdminController::class, 'unlockUser']);
    Route::post('/users/{userId}/lock', [AdminController::class, 'lockUser']);
    Route::delete('/users/{userId}/failed-attempts', [AdminController::class, 'clearFailedAttempts']);

    // Historique des connexions
    Route::get('/users/{userId}/login-history', [AdminController::class, 'getLoginHistory']);

    // Statistiques de sécurité
    Route::get('/security/stats', [AdminController::class, 'getSecurityStats']);

    // Analytics - Legacy endpoints
    Route::prefix('login-attempts')->group(function () {
        Route::get('/statistics', [LoginAttemptAnalyticsController::class, 'getStatistics']);
        Route::get('/email/{email}', [LoginAttemptAnalyticsController::class, 'getEmailStatistics']);
        Route::get('/ip/{ipAddress}', [LoginAttemptAnalyticsController::class, 'getIpStatistics']);
        Route::get('/suspicious', [LoginAttemptAnalyticsController::class, 'detectSuspiciousActivity']);
        Route::get('/history', [LoginAttemptAnalyticsController::class, 'getHistory']);
        Route::delete('/cleanup', [LoginAttemptAnalyticsController::class, 'cleanup']);
        Route::delete('/{id}', [LoginAttemptAnalyticsController::class, 'deleteAttempt']);
    });
});


Route::prefix('users')->middleware(['auth:sanctum'])->group(function () {
    // Routes pour le profil de l'utilisateur connecté (doit être avant les routes paramétrées)
    Route::get('/me', [UserController::class, 'getProfile']);
    Route::put('/me', [UserController::class, 'updateProfile'])
        ->middleware('throttle:10,1');

    Route::get('/', [UserController::class, 'index'])
        ->middleware('role:manager');
    Route::post('/', [UserController::class, 'store'])
        ->middleware('role:manager')
        ->middleware('throttle:5,1');

    Route::get('/search', [UserController::class, 'search'])
        ->middleware('throttle:30,1');

    Route::get('/{id}', [UserController::class, 'show']);
    Route::get('/{id}/activity', [UserController::class, 'activity']);

    Route::put('/{id}', [UserController::class, 'update'])
        ->middleware('throttle:10,1');
    Route::delete('/{id}', [UserController::class, 'destroy'])
        ->middleware('role:manager');
});

Route::middleware(['auth:sanctum'])->group(function () {
    include 'firebase-routes.php';
});

// Roadworks Routes
Route::prefix('roadworks')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [RoadworkController::class, 'index']);
    Route::post('/', [RoadworkController::class, 'store']);
    Route::get('/{roadwork}', [RoadworkController::class, 'show']);
    Route::put('/{roadwork}', [RoadworkController::class, 'update']);
    Route::delete('/{roadwork}', [RoadworkController::class, 'destroy']);
    Route::get('/{roadwork}/status-history', [RoadworkController::class, 'statusHistory']);
    
    // Photos routes
    Route::get('/{roadwork}/photos', [RoadworkPhotoController::class, 'index']);
    Route::post('/{roadwork}/photos', [RoadworkPhotoController::class, 'store']);
});

// Roadwork Photos Routes
Route::prefix('roadwork-photos')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/{photo}', [RoadworkPhotoController::class, 'show']);
    Route::delete('/{photo}', [RoadworkPhotoController::class, 'destroy']);
});

// Notifications Routes
Route::prefix('notifications')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/{notification}', [NotificationController::class, 'show']);
    Route::put('/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/{notification}/unread', [NotificationController::class, 'markAsUnread']);
    Route::put('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{notification}', [NotificationController::class, 'destroy']);
});

// Firebase Tokens Routes
Route::prefix('firebase')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/register-token', [FirebaseTokenController::class, 'registerToken']);
    Route::get('/tokens', [FirebaseTokenController::class, 'listTokens']);
    Route::get('/tokens/active', [FirebaseTokenController::class, 'listActiveTokens']);
    Route::get('/tokens/{token}', [FirebaseTokenController::class, 'showToken']);
    Route::put('/tokens/{token}/deactivate', [FirebaseTokenController::class, 'deactivateToken']);
    Route::put('/tokens/{token}/activate', [FirebaseTokenController::class, 'activateToken']);
    Route::delete('/tokens/{token}', [FirebaseTokenController::class, 'deleteToken']);
    Route::delete('/tokens/unused-cleanup', [FirebaseTokenController::class, 'cleanupUnusedTokens']);
});


// DataSource Routes (Firebase/PostgreSQL Switch)
Route::middleware(['auth:sanctum'])->prefix('datasource')->group(function () {
    Route::get('/active', [DataSourceController::class, 'getActiveDataSource'])
        ->name('datasource.active');

    Route::get('/status', [DataSourceController::class, 'getConnectionStatus'])
        ->name('datasource.status');

    Route::post('/force', [DataSourceController::class, 'forceDataSource'])
        ->name('datasource.force');

    Route::post('/reset-auto-detection', [DataSourceController::class, 'resetAutoDetection'])
        ->name('datasource.reset');

    Route::post('/test', [DataSourceController::class, 'testDataSource'])
        ->name('datasource.test');
});




// Statistics Routes
Route::prefix('statistics')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/average-delay', [StatisticsController::class, 'averageDelay']);
    Route::get('/delay-by-location', [StatisticsController::class, 'delayByLocation']);
    Route::get('/summary', [StatisticsController::class, 'summary']);
});

// Liste des utilisateurs (pour le signalement)
Route::get('/users', [App\Http\Controllers\Api\AuthController::class, 'listUsers']);

// Routes publiques (pour visiteurs aussi)
Route::get('/statistics', [RoadworkController::class, 'getStatistics']);
Route::get('/statuses', [RoadworkController::class, 'getStatuses']);
Route::get('/enterprises', [RoadworkController::class, 'getEnterprises']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('roads', RoadController::class);
    // Signalements (reports)
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/my', [ReportController::class, 'myReports']);
    Route::get('/reports/{id}', [ReportController::class, 'show']);
    Route::put('/reports/{id}', [ReportController::class, 'update']);
    Route::delete('/reports/{id}', [ReportController::class, 'destroy']);

    // Travaux routiers avec infos complètes
    Route::get('/roadworks', [RoadworkController::class, 'index']);
    Route::get('/roads-details', [RoadworkController::class, 'getAllRoadsWithDetails']);
    Route::get('/roads/{roadId}/details', [RoadworkController::class, 'getRoadDetails']);

    // #112 — Modifier statut travaux d'une route (Manager only)
    Route::put('/roads/{id}/status', [RoadworkController::class, 'updateRoadStatus']);
    // #113 — Modifier détails d'une route (surface, budget, entreprise) (Manager only)
    Route::put('/roads/{id}/road-details', [RoadworkController::class, 'updateRoadDetails']);

    // #110 — Synchronisation Firebase (Manager only)
    Route::post('/manager/sync', [AuthController::class, 'syncFirebase']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});
