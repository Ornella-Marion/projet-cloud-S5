<?php

/**
 * Configuration Firebase pour l'application
 * 
 * Contient tous les paramètres de connexion et d'intégration avec Firebase
 * Les credentials sont chargées depuis le fichier .env
 * 
 * @see https://firebase.google.com/docs
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Project Configuration
    |--------------------------------------------------------------------------
    |
    | Paramètres de base du projet Firebase
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),
    
    'api_key' => env('FIREBASE_API_KEY'),
    
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    
    'database_url' => env('FIREBASE_DATABASE_URL'),
    
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    
    'app_id' => env('FIREBASE_APP_ID'),
    
    'measurement_id' => env('FIREBASE_MEASUREMENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Admin SDK Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials pour l'SDK Admin Firebase (accès côté serveur)
    |
    */
    'credentials' => [
        'type' => 'service_account',
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'client_id' => null,
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_x509_cert_url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials JSON Path
    |--------------------------------------------------------------------------
    |
    | Chemin vers le fichier JSON de credentials Firebase (alternative)
    | Si ce chemin est défini, les credentials.php seront ignorées
    |
    */
    'credentials_json_path' => env('FIREBASE_CREDENTIALS_JSON_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Services - Feature Flags
    |--------------------------------------------------------------------------
    |
    | Activer/désactiver les services Firebase utilisés par l'application
    |
    */
    'services' => [
        'realtime_db' => env('FIREBASE_ENABLE_REALTIME_DB', false),
        'firestore' => env('FIREBASE_ENABLE_FIRESTORE', false),
        'storage' => env('FIREBASE_ENABLE_STORAGE', false),
        'push_notifications' => env('FIREBASE_ENABLE_PUSH_NOTIFICATIONS', false),
        'analytics' => env('FIREBASE_ENABLE_ANALYTICS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Realtime Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour Firebase Realtime Database
    |
    */
    'realtime_db' => [
        'enabled' => env('FIREBASE_ENABLE_REALTIME_DB', false),
        'url' => env('FIREBASE_DATABASE_URL'),
        'cache_driver' => env('FIREBASE_CACHE_DRIVER', 'redis'),
        'timeout' => 5000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Firestore Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour Cloud Firestore
    |
    */
    'firestore' => [
        'enabled' => env('FIREBASE_ENABLE_FIRESTORE', false),
        'cache_ttl' => 3600, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour Firebase Cloud Storage
    |
    */
    'storage' => [
        'enabled' => env('FIREBASE_ENABLE_STORAGE', false),
        'bucket' => env('FIREBASE_STORAGE_BUCKET'),
        'cache_ttl' => 86400, // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour les notifications push via Firebase Cloud Messaging
    |
    */
    'messaging' => [
        'enabled' => env('FIREBASE_ENABLE_PUSH_NOTIFICATIONS', false),
        'max_batch_size' => 500,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour Firebase Analytics
    |
    */
    'analytics' => [
        'enabled' => env('FIREBASE_ENABLE_ANALYTICS', false),
        'measurement_id' => env('FIREBASE_MEASUREMENT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Performance Settings
    |--------------------------------------------------------------------------
    |
    | Paramètres de sécurité et de performance
    |
    */
    'security' => [
        'verify_ssl' => env('FIREBASE_VERIFY_SSL', true),
        'timeout' => env('FIREBASE_TIMEOUT', 30),
        'connection_pool_size' => env('FIREBASE_POOL_SIZE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration du cache pour les résultats Firebase
    |
    */
    'cache' => [
        'enabled' => env('FIREBASE_ENABLE_CACHE', true),
        'driver' => env('FIREBASE_CACHE_DRIVER', 'redis'),
        'ttl' => env('FIREBASE_CACHE_TTL', 3600),
    ],
];
