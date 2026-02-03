<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des limites de requêtes pour protéger les endpoints sensibles
    | contre les abus et les attaques par force brute.
    |
    */

    'limits' => [
        // Authentification
        'auth' => [
            'login' => '6,1',        // 6 requêtes par minute
            'signup' => '5,1',       // 5 requêtes par minute
            'refresh-token' => '10,1', // 10 requêtes par minute
        ],

        // Administration
        'admin' => [
            'unlock' => '10,1',      // 10 requêtes par minute
            'lock' => '10,1',        // 10 requêtes par minute
            'security-stats' => '30,1', // 30 requêtes par minute
        ],

        // API générale
        'api' => [
            'default' => '60,1',     // 60 requêtes par minute (défaut)
            'read' => '100,1',       // 100 requêtes par minute (GET)
            'write' => '50,1',       // 50 requêtes par minute (POST, PUT, DELETE)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers de Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Headers envoyés avec chaque réponse pour informer le client
    | de son statut de rate limiting.
    |
    */

    'headers' => [
        'limit' => 'X-RateLimit-Limit',
        'remaining' => 'X-RateLimit-Remaining',
        'reset' => 'X-RateLimit-Reset',
        'retry_after' => 'Retry-After',
    ],

    /*
    |--------------------------------------------------------------------------
    | Drivers de Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Laravel supporte Redis ou Cache par défaut. Redis est recommandé
    | pour les applications avec rate limiting agressif.
    |
    */

    'driver' => env('RATE_LIMIT_DRIVER', 'cache'),

];
