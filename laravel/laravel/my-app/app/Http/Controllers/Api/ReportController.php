<?php
// app/Http/Controllers/Api/ReportController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    // Créer un signalement
    public function store(Request $request)
    {
        $user = $request->user();
        \Log::info('API store report - User authentifié: ' . ($user ? $user->id . ' (' . $user->name . ')' : 'NULL'));

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $validated = $request->validate([
            'target_type' => 'required|string|max:50',
            'report_date' => 'required|date',
            'reason' => 'required|string',
            'road_id' => 'nullable|integer|exists:roads,id',
        ]);

        $report = Report::create([
            'user_id' => $user->id,
            'road_id' => $validated['road_id'] ?? null,
            'target_type' => $validated['target_type'],
            'report_date' => $validated['report_date'],
            'reason' => $validated['reason'],
        ]);

        \Log::info('Signalement créé - ID: ' . $report->id . ' pour user_id: ' . $user->id);

        return response()->json(['message' => 'Signalement créé', 'report' => $report], 201);
    }

    // Lister les signalements
    public function index()
    {
        $reports = Report::with(['user', 'road'])->orderByDesc('created_at')->get();
        return response()->json($reports);
    }

    // Lister les signalements de l'utilisateur connecté
    public function myReports()
    {
        $userId = Auth::id();
        \Log::info('API myReports appelée - User ID: ' . $userId);

        if (!$userId) {
            \Log::error('API myReports - Utilisateur non authentifié');
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $reports = Report::with(['user', 'road'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        \Log::info('API myReports - Signalements trouvés: ' . $reports->count() . ' pour user ' . $userId);

        return response()->json($reports);
    }
}
