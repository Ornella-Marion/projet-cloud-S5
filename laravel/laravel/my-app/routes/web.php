<?php

use Illuminate\Support\Facades\Route;

// API Only - Aucune route web
Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

