<?php

return [

    /*
    |--------------------------------------------------------------------------
    | L5 Swagger / OpenAPI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la génération automatique de documentation Swagger/OpenAPI
    |
    */

    'api' => [
        'title' => 'Firebase Integration API',
    ],

    'routes' => [
        /*
        |--------------------------------------------------------------------------
        | Route pour la documentation Swagger UI
        |--------------------------------------------------------------------------
        */
        'api' => '/api/documentation',

        /*
        |--------------------------------------------------------------------------
        | Route pour le fichier JSON OpenAPI
        |--------------------------------------------------------------------------
        */
        'docs_json' => '/api/documentation.json',

        /*
        |--------------------------------------------------------------------------
        | Route pour le fichier YAML OpenAPI
        |--------------------------------------------------------------------------
        */
        'docs_yaml' => '/api/documentation.yaml',

        /*
        |--------------------------------------------------------------------------
        | Invalider le cache Swagger
        |--------------------------------------------------------------------------
        */
        'oauth2_callback' => '/api/oauth2-callback',
    ],

    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Répertoires à scanner pour les annotations OpenAPI
        |--------------------------------------------------------------------------
        |
        | Laravel scannera ces répertoires pour générer la documentation
        |
        */
        'annotations' => [
            base_path('app/Http/Controllers'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Répertoire d'exclusion
        |--------------------------------------------------------------------------
        */
        'excludes' => [],

        /*
        |--------------------------------------------------------------------------
        | Où stocker les fichiers OpenAPI générés
        |--------------------------------------------------------------------------
        */
        'docs' => storage_path('api-docs'),

        /*
        |--------------------------------------------------------------------------
        | Répertoire public pour les assets Swagger UI
        |--------------------------------------------------------------------------
        */
        'views' => base_path('resources/views/vendor/l5-swagger'),

        /*
        |--------------------------------------------------------------------------
        | Répertoire pour les assets CSS/JS
        |--------------------------------------------------------------------------
        */
        'assets' => '/vendor/swagger-ui',

        /*
        |--------------------------------------------------------------------------
        | Emplacement du fichier OpenAPI de base
        |--------------------------------------------------------------------------
        */
        'base' => base_path('storage/api-docs/openapi.yaml'),
    ],

    'docs_sort' => 'servers',

    'swagger_ui' => [
        'operations_sort' => 'method',
        'tagsSorter' => 'alpha',
    ],

    'constants' => [
        'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8000'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration générale
    |--------------------------------------------------------------------------
    */
    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

    'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),

    'hide_download' => env('L5_SWAGGER_HIDE_DOWNLOAD', false),

    /*
    |--------------------------------------------------------------------------
    | Mode de sécurité
    |--------------------------------------------------------------------------
    */
    'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),

    'swagger_ui_assertion_cache_enabled' => env('SWAGGER_UI_ASSERTION_CACHE_ENABLED', true),

    'models_migration' => env('L5_SWAGGER_MODELS_MIGRATION', false),

    'swagger_ui_oauth2_redirect_url' => env('SWAGGER_UI_OAUTH2_REDIRECT_URL', null),

];
