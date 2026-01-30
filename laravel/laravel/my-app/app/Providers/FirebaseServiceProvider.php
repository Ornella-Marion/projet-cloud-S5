<?php

namespace App\Providers;

use App\Services\FirebaseService;
use Illuminate\Support\ServiceProvider;

/**
 * Firebase Service Provider
 * 
 * Enregistre le service Firebase dans le conteneur de services de Laravel
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
    }

    /**
     * Vérifier si le provider doit être chargé
     * 
     * @return bool
     */
    public function provides(): array
    {
        return [FirebaseService::class, 'firebase'];
    }
}
