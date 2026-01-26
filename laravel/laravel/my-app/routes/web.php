<?php

use Illuminate\Support\Facades\Route;

// API Only - Aucune route web
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
