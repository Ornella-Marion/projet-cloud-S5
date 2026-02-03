<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LoginAttemptAnalyticsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DataSourceController;


Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup'])
        ->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'signup']) // Ajout de la route conventionnelle
        ->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1');  // 6 tentatives par minute
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::post('/unlock-account/{userId}', [AuthController::class, 'unlockAccount'])->middleware('auth:sanctum');
    Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum');
    Route::post('/auth/login', [AuthController::class, 'login']);
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

// Routes User Management
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

// Firebase Routes
Route::middleware(['auth:sanctum'])->group(function () {
    include 'firebase-routes.php';
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

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});
