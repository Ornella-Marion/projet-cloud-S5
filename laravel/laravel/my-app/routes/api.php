<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoadController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoadworkController;


Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'getCurrentUser'])->middleware('auth:sanctum');
    Route::get('/locked-accounts', [AuthController::class, 'getLockedAccounts'])->middleware('auth:sanctum');
    Route::post('/unlock-account/{userId}', [AuthController::class, 'unlockAccount'])->middleware('auth:sanctum');
    // Inscription par le manager uniquement
    Route::post('/manager-signup', [AuthController::class, 'managerSignup'])->middleware('auth:sanctum');
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
