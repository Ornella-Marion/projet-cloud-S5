<?php

namespace App\Providers;

use App\Services\FirebaseService;
use App\Services\DataSourceService;
use Illuminate\Support\ServiceProvider;

/**
 * Firebase Service Provider
 * 
 * Enregistre les services Firebase et DataSource dans le conteneur de services de Laravel
 * Permet l'injection de dépendance automatique dans les contrôleurs et services
 * 
 * @package App\Providers
 */
class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Enregistrer les services dans le conteneur
     * 
     * @return void
     */
    public function register(): void
    {
        // Enregistrer FirebaseService comme singleton
        // Une seule instance sera créée et réutilisée dans toute l'application
        $this->app->singleton(FirebaseService::class, function ($app) {
            return new FirebaseService();
        });

        // Alias pour faciliter l'accès
        $this->app->alias(FirebaseService::class, 'firebase');

        // Enregistrer DataSourceService comme singleton
        // Gère le switch automatique Firebase/PostgreSQL
        $this->app->singleton(DataSourceService::class, function ($app) {
            return new DataSourceService($app->make(FirebaseService::class));
        });

        // Alias pour faciliter l'accès
        $this->app->alias(DataSourceService::class, 'datasource');
    }

    /**
     * Initialiser les services après l'enregistrement
     * 
     * @return void
     */
    public function boot(): void
    {
        // Publier la configuration (optionnel)
        $this->publishes([
            __DIR__ . '/../config/firebase.php' => config_path('firebase.php'),
        ], 'firebase-config');

        // Log si Firebase n'est pas configuré en développement
        if (config('app.debug') && !app(FirebaseService::class)->isConfigured()) {
            \Illuminate\Support\Facades\Log::warning(
                'Firebase is not properly configured. ' .
                    'Please set the required environment variables in .env'
            );
        }

        // Log l'information sur le DataSource
        \Illuminate\Support\Facades\Log::info(
            'DataSourceService initialized',
            ['active_datasource' => app(DataSourceService::class)->getActiveDataSource()]
        );
    }

    /**
     * Vérifier si le provider doit être chargé
     * 
     * @return bool
     */
    public function provides(): array
    {
        return [FirebaseService::class, DataSourceService::class, 'firebase', 'datasource'];
    }
}
