<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\DataSourceService;

/**
 * Middleware pour synchroniser la source de données active
 * 
 * Vérifie la connexion Internet au début de chaque requête et
 * bascule automatiquement entre Firebase et PostgreSQL si nécessaire
 * 
 * @package App\Http\Middleware
 */
class SyncDataSource
{
    /**
     * Service de gestion des sources de données
     * 
     * @var DataSourceService
     */
    private DataSourceService $dataSourceService;

    /**
     * Constructeur
     * 
     * @param DataSourceService $dataSourceService
     */
    public function __construct(DataSourceService $dataSourceService)
    {
        $this->dataSourceService = $dataSourceService;
    }

    /**
     * Traiter la requête
     * 
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Attacher la source de données active à la requête
        $request->attributes->set('active_datasource', $this->dataSourceService->getActiveDataSource());
        $request->attributes->set('firebase_active', $this->dataSourceService->isFirebaseActive());
        $request->attributes->set('postgresql_active', $this->dataSourceService->isPostgresqlActive());

        $response = $next($request);

        // Ajouter un header pour indiquer quelle source a été utilisée
        $response->header('X-Active-DataSource', $this->dataSourceService->getActiveDataSource());

        return $response;
    }
}
