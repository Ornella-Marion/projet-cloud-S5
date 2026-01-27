<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoadController;
use App\Http\Controllers\Api\ReportController;


Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::post('/unlock-account/{userId}', [AuthController::class, 'unlockAccount'])->middleware('auth:sanctum');
});

// Liste des utilisateurs (pour le signalement)
Route::get('/users', [App\Http\Controllers\Api\AuthController::class, 'listUsers']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('roads', RoadController::class);
    // Signalements (reports)
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports', [ReportController::class, 'index']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});
