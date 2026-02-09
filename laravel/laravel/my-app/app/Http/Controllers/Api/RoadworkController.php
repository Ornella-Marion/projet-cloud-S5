<?php

namespace App\Http\Controllers\Api;

use App\Models\Roadwork;
use App\Models\Road;
use App\Models\Report;
use App\Models\Status;
use App\Models\Enterprise;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RoadworkController extends Controller
{
    /**
     * Liste tous les travaux avec leurs relations
     */
    public function index()
    {
        $roadworks = Roadwork::with(['road', 'status', 'enterprise'])->get();
        return response()->json($roadworks);
    }

    /**
     * Récupérer les infos complètes d'une route (avec travaux, statut, budget, entreprise)
     */
    public function getRoadDetails($roadId)
    {
        $road = Road::with(['roadworks.status', 'roadworks.enterprise', 'reports.user'])
            ->findOrFail($roadId);

        // Formater les données
        $roadwork = $road->roadworks->first();

        $details = [
            'id' => $road->id,
            'designation' => $road->designation,
            'longitude' => $road->longitude,
            'latitude' => $road->latitude,
            'area' => $road->area,
            'created_at' => $road->created_at,
            'updated_at' => $road->updated_at,
            // Infos travaux
            'roadwork' => $roadwork ? [
                'id' => $roadwork->id,
                'budget' => $roadwork->budget,
                'finished_at' => $roadwork->finished_at,
                'status' => $roadwork->status ? [
                    'id' => $roadwork->status->id,
                    'label' => $roadwork->status->label,
                    'percentage' => $roadwork->status->percentage,
                ] : null,
                'enterprise' => $roadwork->enterprise ? [
                    'id' => $roadwork->enterprise->id,
                    'designation' => $roadwork->enterprise->designation,
                ] : null,
            ] : null,
            // Nombre de signalements
            'reports_count' => $road->reports->count(),
            'recent_reports' => $road->reports->take(3)->map(function ($report) {
                return [
                    'id' => $report->id,
                    'reason' => $report->reason,
                    'target_type' => $report->target_type,
                    'report_date' => $report->report_date,
                    'user' => $report->user ? $report->user->name : 'Anonyme',
                ];
            }),
        ];

        return response()->json($details);
    }

    /**
     * Liste toutes les routes avec leurs infos complètes
     */
    public function getAllRoadsWithDetails()
    {
        $roads = Road::with(['roadworks.status', 'roadworks.enterprise'])
            ->withCount('reports')
            ->get()
            ->map(function ($road) {
                $roadwork = $road->roadworks->first();
                return [
                    'id' => $road->id,
                    'designation' => $road->designation,
                    'longitude' => $road->longitude,
                    'latitude' => $road->latitude,
                    'area' => $road->area,
                    'created_at' => $road->created_at,
                    'updated_at' => $road->updated_at,
                    'reports_count' => $road->reports_count,
                    'roadwork' => $roadwork ? [
                        'budget' => $roadwork->budget,
                        'finished_at' => $roadwork->finished_at,
                        'status' => $roadwork->status ? $roadwork->status->label : null,
                        'status_percentage' => $roadwork->status ? $roadwork->status->percentage : null,
                        'enterprise' => $roadwork->enterprise ? $roadwork->enterprise->designation : null,
                    ] : null,
                ];
            });

        return response()->json($roads);
    }

    /**
     * Liste des statuts disponibles
     */
    public function getStatuses()
    {
        $statuses = Status::all();
        return response()->json($statuses);
    }

    /**
     * Liste des entreprises
     */
    public function getEnterprises()
    {
        $enterprises = Enterprise::all();
        return response()->json($enterprises);
    }

    /**
     * Statistiques globales
     */
    public function getStatistics()
    {
        $totalRoads = Road::count();
        $totalRoadworks = Roadwork::count();
        $totalReports = \App\Models\Report::count();

        // Répartition par statut
        $byStatus = Roadwork::with('status')
            ->get()
            ->groupBy(fn($rw) => $rw->status ? $rw->status->label : 'Non défini')
            ->map(fn($group) => $group->count());

        // Budget total
        $totalBudget = Roadwork::sum('budget');

        // Surface totale
        $totalArea = Road::sum('area');

        // #106 — Avancement global (%)
        $advancementPercentage = 0;
        if ($totalRoadworks > 0) {
            $advancementPercentage = round(
                Roadwork::join('status', 'roadworks.status_id', '=', 'status.id')
                    ->avg('status.percentage') ?? 0,
                2
            );
        }

        // Signalements par type
        $reportsByType = Report::selectRaw('target_type, COUNT(*) as count')
            ->groupBy('target_type')
            ->pluck('count', 'target_type');

        // Signalements par statut
        $reportsByStatus = Report::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'total_roads' => $totalRoads,
            'total_roadworks' => $totalRoadworks,
            'total_reports' => $totalReports,
            'total_budget' => $totalBudget,
            'total_area' => $totalArea,
            'advancement_percentage' => $advancementPercentage,
            'roadworks_by_status' => $byStatus,
            'reports_by_type' => $reportsByType,
            'reports_by_status' => $reportsByStatus,
        ]);
    }

    /**
     * #112 — PUT /api/roads/{id}/status - Modifier le statut d'un travail routier (Manager only)
     */
    public function updateRoadStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'manager') {
            return response()->json(['error' => 'Accès refusé. Réservé aux managers.'], 403);
        }

        $validated = $request->validate([
            'status_id' => 'required|integer|exists:status,id',
        ]);

        $road = Road::findOrFail($id);
        $roadwork = Roadwork::where('road_id', $road->id)->first();

        if (!$roadwork) {
            // Créer un roadwork si aucun n'existe pour cette route
            $roadwork = Roadwork::create([
                'road_id' => $road->id,
                'status_id' => $validated['status_id'],
                'budget' => 0,
            ]);
        } else {
            $roadwork->update(['status_id' => $validated['status_id']]);
        }

        $roadwork->load(['status', 'enterprise']);

        \Log::info('Statut route #' . $id . ' mis à jour par manager #' . $user->id);

        return response()->json([
            'message' => 'Statut mis à jour',
            'roadwork' => $roadwork,
        ]);
    }

    /**
     * #113 — PUT /api/roads/{id}/details - Modifier les détails (surface, budget, entreprise) (Manager only)
     */
    public function updateRoadDetails(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'manager') {
            return response()->json(['error' => 'Accès refusé. Réservé aux managers.'], 403);
        }

        $validated = $request->validate([
            'area' => 'sometimes|numeric|min:0',
            'budget' => 'sometimes|numeric|min:0',
            'enterprise_id' => 'sometimes|integer|exists:enterprises,id',
            'finished_at' => 'sometimes|nullable|date',
            'designation' => 'sometimes|string|max:255',
        ]);

        $road = Road::findOrFail($id);

        // Mettre à jour les champs de Road
        $roadFields = array_intersect_key($validated, array_flip(['area', 'designation']));
        if (!empty($roadFields)) {
            $road->update($roadFields);
        }

        // Mettre à jour les champs de Roadwork
        $roadworkFields = array_intersect_key($validated, array_flip(['budget', 'enterprise_id', 'finished_at']));
        if (!empty($roadworkFields)) {
            $roadwork = Roadwork::where('road_id', $road->id)->first();
            if (!$roadwork) {
                $roadworkFields['road_id'] = $road->id;
                $roadworkFields['status_id'] = Status::first()->id ?? 1;
                $roadwork = Roadwork::create($roadworkFields);
            } else {
                $roadwork->update($roadworkFields);
            }
        }

        \Log::info('Détails route #' . $id . ' mis à jour par manager #' . $user->id);

        // Recharger avec les relations
        $road->load(['roadworks.status', 'roadworks.enterprise']);

        return response()->json([
            'message' => 'Détails mis à jour',
            'road' => $road,
        ]);
    }
}
