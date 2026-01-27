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
        $validated = $request->validate([
            'target_type' => 'required|string|max:50',
            'report_date' => 'required|date',
            'reason' => 'required|string',
            'road_id' => 'nullable|integer|exists:roads,id',
        ]);

        $report = Report::create([
            'user_id' => $request->user()->id,
            'road_id' => $validated['road_id'] ?? null,
            'target_type' => $validated['target_type'],
            'report_date' => $validated['report_date'],
            'reason' => $validated['reason'],
        ]);

        return response()->json(['message' => 'Signalement créé', 'report' => $report], 201);
    }

    // Lister les signalements
    public function index()
    {
        $reports = Report::with(['user', 'road'])->orderByDesc('created_at')->get();
        return response()->json($reports);
    }
}
