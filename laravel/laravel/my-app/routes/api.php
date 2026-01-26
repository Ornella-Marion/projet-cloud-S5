<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/register', [AuthController::class, 'signup']); // Ajout de la route conventionnelle
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::post('/unlock-account/{userId}', [AuthController::class, 'unlockAccount'])->middleware('auth:sanctum');
});

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});
