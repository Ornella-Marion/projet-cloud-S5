<?php

namespace App\Http\Controllers\Api;

use App\Models\Roadwork;
use App\Models\StatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class StatisticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/statistics/average-delay",
     *     summary="Calculer le délai moyen de traitement des travaux",
     *     tags={"Statistics"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="filter_status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"all","planned","in_progress","completed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Délais moyens calculés"
     *     )
     * )
     */
    public function averageDelay(Request $request): JsonResponse
    {
        $filter = $request->query('filter_status', 'all');

        // Récupérer tous les roadworks
        $query = Roadwork::query();
        
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $roadworks = $query->get();

        // Calculer les délais pour chaque transition
        $delays = [
            'planned_to_in_progress' => $this->calculateDelayBetweenStatuses($roadworks, 'planned', 'in_progress'),
            'in_progress_to_completed' => $this->calculateDelayBetweenStatuses($roadworks, 'in_progress', 'completed'),
            'planned_to_completed' => $this->calculateTotalDelay($roadworks),
        ];

        // Calculer les statistiques globales
        $stats = [
            'total_roadworks' => $roadworks->count(),
            'completed_roadworks' => $roadworks->where('status', 'completed')->count(),
            'in_progress_roadworks' => $roadworks->where('status', 'in_progress')->count(),
            'planned_roadworks' => $roadworks->where('status', 'planned')->count(),
            'delays' => $delays,
        ];

        return response()->json($stats, 200);
    }

    /**
     * Calculer le délai moyen entre deux statuts
     */
    private function calculateDelayBetweenStatuses($roadworks, string $fromStatus, string $toStatus): array
    {
        $delays = [];

        foreach ($roadworks as $roadwork) {
            // Récupérer la transition entre les deux statuts
            $history = StatusHistory::where('roadwork_id', $roadwork->id)
                ->where('old_status', $fromStatus)
                ->where('new_status', $toStatus)
                ->first();

            if (!$history) {
                continue;
            }

            // Trouver le timestamp du statut précédent
            $prevHistory = StatusHistory::where('roadwork_id', $roadwork->id)
                ->where('new_status', $fromStatus)
                ->latest('changed_at')
                ->first();

            if ($prevHistory) {
                $startTime = $prevHistory->changed_at;
            } else {
                // Si pas de history précédente, utiliser created_at
                if ($fromStatus === 'planned') {
                    $startTime = $roadwork->created_at;
                } else {
                    continue;
                }
            }

            $endTime = $history->changed_at;
            $delayHours = $startTime->diffInHours($endTime);
            $delayDays = $startTime->diffInDays($endTime);

            $delays[] = [
                'roadwork_id' => $roadwork->id,
                'roadwork_title' => $roadwork->title,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'started_at' => $startTime,
                'completed_at' => $endTime,
                'delay_hours' => $delayHours,
                'delay_days' => $delayDays,
            ];
        }

        if (empty($delays)) {
            return [
                'average_hours' => 0,
                'average_days' => 0,
                'min_hours' => 0,
                'max_hours' => 0,
                'count' => 0,
            ];
        }

        $avgHours = collect($delays)->avg('delay_hours');
        $avgDays = collect($delays)->avg('delay_days');
        $minHours = collect($delays)->min('delay_hours');
        $maxHours = collect($delays)->max('delay_hours');

        return [
            'average_hours' => round($avgHours, 2),
            'average_days' => round($avgDays, 2),
            'min_hours' => $minHours,
            'max_hours' => $maxHours,
            'count' => count($delays),
            'details' => $delays,
        ];
    }

    /**
     * Calculer le délai total de "planned" à "completed"
     */
    private function calculateTotalDelay($roadworks): array
    {
        $delays = [];

        foreach ($roadworks as $roadwork) {
            // Si pas complété, passer
            if ($roadwork->status !== 'completed') {
                continue;
            }

            $startTime = $roadwork->created_at;
            $endTime = $roadwork->completed_at;

            if (!$endTime) {
                continue;
            }

            $delayHours = $startTime->diffInHours($endTime);
            $delayDays = $startTime->diffInDays($endTime);

            $delays[] = [
                'roadwork_id' => $roadwork->id,
                'roadwork_title' => $roadwork->title,
                'created_at' => $startTime,
                'completed_at' => $endTime,
                'delay_hours' => $delayHours,
                'delay_days' => $delayDays,
            ];
        }

        if (empty($delays)) {
            return [
                'average_hours' => 0,
                'average_days' => 0,
                'min_hours' => 0,
                'max_hours' => 0,
                'count' => 0,
            ];
        }

        $avgHours = collect($delays)->avg('delay_hours');
        $avgDays = collect($delays)->avg('delay_days');
        $minHours = collect($delays)->min('delay_hours');
        $maxHours = collect($delays)->max('delay_hours');

        return [
            'average_hours' => round($avgHours, 2),
            'average_days' => round($avgDays, 2),
            'min_hours' => $minHours,
            'max_hours' => $maxHours,
            'count' => count($delays),
            'details' => $delays,
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/statistics/delay-by-location",
     *     summary="Calculer les délais par localisation",
     *     tags={"Statistics"},
     *     security={{"BearerAuth":{}}}
     * )
     */
    public function delayByLocation(): JsonResponse
    {
        $roadworks = Roadwork::all();
        $locations = [];

        foreach ($roadworks->groupBy('location') as $location => $works) {
            $completed = $works->where('status', 'completed');
            $totalDelay = 0;
            $count = 0;

            foreach ($completed as $work) {
                if ($work->completed_at) {
                    $totalDelay += $work->created_at->diffInHours($work->completed_at);
                    $count++;
                }
            }

            $locations[$location] = [
                'total_roadworks' => $works->count(),
                'completed_roadworks' => $completed->count(),
                'average_delay_hours' => $count > 0 ? round($totalDelay / $count, 2) : 0,
                'average_delay_days' => $count > 0 ? round(($totalDelay / 24) / $count, 2) : 0,
            ];
        }

        return response()->json($locations, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/statistics/summary",
     *     summary="Résumé des statistiques globales",
     *     tags={"Statistics"},
     *     security={{"BearerAuth":{}}}
     * )
     */
    public function summary(): JsonResponse
    {
        $allRoadworks = Roadwork::all();

        $summary = [
            'total_roadworks' => $allRoadworks->count(),
            'status_breakdown' => [
                'planned' => $allRoadworks->where('status', 'planned')->count(),
                'in_progress' => $allRoadworks->where('status', 'in_progress')->count(),
                'completed' => $allRoadworks->where('status', 'completed')->count(),
                'paused' => $allRoadworks->where('status', 'paused')->count(),
            ],
            'total_photos' => $allRoadworks->sum(function ($roadwork) {
                return $roadwork->photos()->count();
            }),
            'average_photos_per_roadwork' => $allRoadworks->count() > 0 
                ? round($allRoadworks->sum(function ($roadwork) {
                    return $roadwork->photos()->count();
                }) / $allRoadworks->count(), 2)
                : 0,
        ];

        return response()->json($summary, 200);
    }
}
