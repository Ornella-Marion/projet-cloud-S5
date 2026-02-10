<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure CORS settings for your application. CORS is
    | used to control which origins are allowed to make requests to your API.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:8000')),

    'allowed_origins_patterns' => [
        // Pattern pour localhost avec n'importe quel port
        '#^http://localhost:[0-9]+$#',
        // Pattern pour développement local
        '#^http://127\.0\.0\.1:[0-9]+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-Total-Count',      // Pour la pagination
        'X-Page-Count',       // Pour la pagination
        'X-Per-Page',         // Pour la pagination
        'X-Current-Page',     // Pour la pagination
        'X-RateLimit-Limit',  // Pour le rate limiting
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400, // 24 heures

    'supports_credentials' => true,

];
