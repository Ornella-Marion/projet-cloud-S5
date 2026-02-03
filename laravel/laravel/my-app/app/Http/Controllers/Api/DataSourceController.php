<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DataSourceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur de gestion des sources de données
 * 
 * Gère le switch automatique Firebase/PostgreSQL selon la connexion Internet
 * 
 * @package App\Http\Controllers\Api
 */
class DataSourceController extends Controller
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
        $this->middleware('auth:sanctum');
    }

    /**
     * Obtenir la source de données active
     * 
     * GET /api/datasource/active
     * 
     * @return JsonResponse
     */
    public function getActiveDataSource(): JsonResponse
    {
        return response()->json([
            'active_datasource' => $this->dataSourceService->getActiveDataSource(),
            'firebase_active' => $this->dataSourceService->isFirebaseActive(),
            'postgresql_active' => $this->dataSourceService->isPostgresqlActive(),
        ], 200);
    }

    /**
     * Obtenir le statut détaillé de la connexion et des sources de données
     * 
     * GET /api/datasource/status
     * 
     * @return JsonResponse
     */
    public function getConnectionStatus(): JsonResponse
    {
        try {
            $status = $this->dataSourceService->getConnectionStatus();

            return response()->json([
                'status' => 'success',
                'data' => $status,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get connection status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Forcer la source de données (admin only)
     * 
     * POST /api/datasource/force
     * 
     * Body:
     * {
     *   "datasource": "firebase" | "postgresql"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function forceDataSource(Request $request): JsonResponse
    {
        $request->validate([
            'datasource' => 'required|string|in:firebase,postgresql',
        ]);

        try {
            $this->dataSourceService->setForcedDataSource($request->input('datasource'));

            return response()->json([
                'status' => 'success',
                'message' => 'DataSource forced successfully',
                'active_datasource' => $this->dataSourceService->getActiveDataSource(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to force datasource',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Réinitialiser la détection automatique
     * 
     * POST /api/datasource/reset-auto-detection
     * 
     * @return JsonResponse
     */
    public function resetAutoDetection(): JsonResponse
    {
        try {
            $this->dataSourceService->resetAutoDetection();

            return response()->json([
                'status' => 'success',
                'message' => 'Auto-detection reset successfully',
                'active_datasource' => $this->dataSourceService->getActiveDataSource(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset auto-detection',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tester écriture/lecture avec la source active
     * 
     * POST /api/datasource/test
     * 
     * Body:
     * {
     *   "operation": "write" | "read",
     *   "type": "firestore" | "realtime_db" | "postgresql",
     *   "collection": "users",          // pour firestore
     *   "document_id": "test123",       // pour firestore
     *   "path": "users/test123",        // pour realtime_db
     *   "data": { "name": "Test" }      // pour write
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function testDataSource(Request $request): JsonResponse
    {
        $request->validate([
            'operation' => 'required|string|in:read,write',
            'type' => 'required|string|in:firestore,realtime_db,postgresql',
        ]);

        try {
            $operation = $request->input('operation');
            $type = $request->input('type');
            $data = $request->all();
            unset($data['operation'], $data['type']);

            $activeDataSource = $this->dataSourceService->getActiveDataSource();

            if ($operation === 'write') {
                $success = $this->dataSourceService->write($type, $data);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Test write completed',
                    'operation' => 'write',
                    'datasource' => $activeDataSource,
                    'result' => $success ? 'success' : 'failed',
                ], 200);
            } else {
                $result = $this->dataSourceService->read($type, $data);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Test read completed',
                    'operation' => 'read',
                    'datasource' => $activeDataSource,
                    'result' => $result,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Test failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
