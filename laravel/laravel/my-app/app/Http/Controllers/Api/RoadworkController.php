<?php

namespace App\Http\Controllers\Api;

use App\Models\Roadwork;
use App\Models\Road;
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
            'recent_reports' => $road->reports->take(3)->map(function($report) {
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
            ->map(function($road) {
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
        
        // Signalements par type
        $reportsByType = \App\Models\Report::selectRaw('target_type, COUNT(*) as count')
            ->groupBy('target_type')
            ->pluck('count', 'target_type');
        
        return response()->json([
            'total_roads' => $totalRoads,
            'total_roadworks' => $totalRoadworks,
            'total_reports' => $totalReports,
            'total_budget' => $totalBudget,
            'total_area' => $totalArea,
            'roadworks_by_status' => $byStatus,
            'reports_by_type' => $reportsByType,
        ]);
    }
}
