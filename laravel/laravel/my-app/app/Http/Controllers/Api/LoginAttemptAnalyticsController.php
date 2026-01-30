<?php

namespace App\Http\Controllers\Api;

use App\Models\LoginAttempt;
use App\Services\LoginSecurityService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur pour l'analyse et la gestion des tentatives de connexion
 * Endpoints d'administration pour monitorer la sécurité
 */
class LoginAttemptAnalyticsController extends Controller
{
    private LoginSecurityService $securityService;

    public function __construct(LoginSecurityService $securityService)
    {
        $this->securityService = $securityService;
        $this->middleware('auth:sanctum');
        $this->middleware('admin'); // À implémenter selon vos règles
    }

    /**
     * @OA\Get(
     *     path="/api/admin/login-attempts/statistics",
     *     summary="Obtenir les statistiques globales des tentatives",
     *     tags={"Admin - Security"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(status=200, description="Statistiques globales"),
     *     @OA\Response(status=401, description="Non authentifié"),
     *     @OA\Response(status=403, description="Non autorisé")
     * )
     */
    public function getStatistics()
    {
        $totalAttempts = LoginAttempt::count();
        $failedAttempts = LoginAttempt::failed()->count();
        $successfulAttempts = LoginAttempt::successful()->count();

        $failed24h = LoginAttempt::failed()
            ->withinMinutes(24 * 60)
            ->count();

        $successfulLast24h = LoginAttempt::successful()
            ->withinMinutes(24 * 60)
            ->count();

        return response()->json([
            'total_attempts' => $totalAttempts,
            'failed_attempts' => $failedAttempts,
            'successful_attempts' => $successfulAttempts,
            'failed_last_24h' => $failed24h,
            'successful_last_24h' => $successfulLast24h,
            'success_rate' => $totalAttempts > 0
                ? round(($successfulAttempts / $totalAttempts) * 100, 2) . '%'
                : 'N/A',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/login-attempts/email/{email}",
     *     summary="Obtenir les statistiques pour un email",
     *     tags={"Admin - Security"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="email", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(status=200, description="Statistiques de l'email"),
     *     @OA\Response(status=401, description="Non authentifié")
     * )
     */
    public function getEmailStatistics($email)
    {
        $statistics = $this->securityService->getEmailStatistics($email);
        $history = $this->securityService->getAttemptHistory($email, 20);

        return response()->json([
            'statistics' => $statistics,
            'history' => $history,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/login-attempts/ip/{ipAddress}",
     *     summary="Obtenir les statistiques pour une IP",
     *     tags={"Admin - Security"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="ipAddress", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(status=200, description="Statistiques de l'IP")
     * )
     */
    public function getIpStatistics($ipAddress)
    {
        $statistics = $this->securityService->getIpStatistics($ipAddress);

        // Tentatives récentes par cette IP
        $recentAttempts = LoginAttempt::forIpAddress($ipAddress)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($attempt) => [
                'id' => $attempt->id,
                'email' => $attempt->email,
                'success' => $attempt->success,
                'failure_reason' => $attempt->failure_reason,
                'user_agent' => $attempt->user_agent,
                'created_at' => $attempt->created_at->toIso8601String(),
            ])->toArray();

        return response()->json([
            'statistics' => $statistics,
            'recent_attempts' => $recentAttempts,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/login-attempts/suspicious",
     *     summary="Détecter les activités suspectes",
     *     tags={"Admin - Security"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(status=200, description="Liste des activités suspectes détectées")
     * )
     */
    public function detectSuspiciousActivity()
    {
        $suspicious = $this->securityService->detectSuspiciousActivity();

        return response()->json([
            'suspicious_ips' => $suspicious['ips'] ?? [],
            'suspicious_emails' => $suspicious['emails'] ?? [],
            'threshold_exceeded' => count($suspicious['ips'] ?? []) > 0 || count($suspicious['emails'] ?? []) > 0,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/login-attempts/history",
     *     summary="Obtenir l'historique récent des tentatives",
     *     tags={"Admin - Security"},
     *     security={{"BearerAuth":{}}},
     *     @OA\QueryParameter(name="limit", in="query", @OA\Schema(type="integer", default=50)),
     *     @OA\QueryParameter(name="filter", in="query", @OA\Schema(type="string", enum={"all", "failed", "successful"})),
     *     @OA\Response(status=200, description="Historique des tentatives")
     * )
     */
    public function getHistory(Request $request)
    {
        $limit = $request->query('limit', 50);
        $filter = $request->query('filter', 'all');

        $query = LoginAttempt::query();

        if ($filter === 'failed') {
            $query->failed();
        } elseif ($filter === 'successful') {
            $query->successful();
        }

        $attempts = $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($attempt) => [
                'id' => $attempt->id,
                'user_id' => $attempt->user_id,
                'email' => $attempt->email,
                'ip_address' => $attempt->ip_address,
                'user_agent' => $attempt->user_agent,
                'success' => $attempt->success,
                'failure_reason' => $attempt->failure_reason,
                'created_at' => $attempt->created_at->toIso8601String(),
            ])->toArray();

        return response()->json([
            'total' => count($attempts),
            'filter' => $filter,
            'attempts' => $attempts,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/login-attempts/cleanup",
     *     summary="Nettoyer les anciennes tentatives",
     *     tags={"Admin - Security"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="days_old", in="query", @OA\Schema(type="integer", default=30)),
     *     @OA\Response(status=200, description="Nettoyage complété")
     * )
     */
    public function cleanup(Request $request)
    {
        $daysOld = $request->query('days_old', 30);
        $deleted = $this->securityService->cleanupOldAttempts($daysOld);

        return response()->json([
            'message' => "Nettoyage complété",
            'deleted_records' => $deleted,
            'days_old' => $daysOld,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/login-attempts/{id}",
     *     summary="Supprimer une tentative spécifique",
     *     tags={"Admin - Security"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(status=200, description="Tentative supprimée"),
     *     @OA\Response(status=404, description="Tentative non trouvée")
     * )
     */
    public function deleteAttempt($id)
    {
        $attempt = LoginAttempt::find($id);

        if (!$attempt) {
            return response()->json(['error' => 'Tentative non trouvée'], 404);
        }

        $attempt->delete();
        Log::info("Tentative de connexion supprimée", ['id' => $id]);

        return response()->json(['message' => 'Tentative supprimée']);
    }
}
