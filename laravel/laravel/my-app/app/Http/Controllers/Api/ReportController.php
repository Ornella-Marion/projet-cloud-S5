<?php
// app/Http/Controllers/Api/ReportController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * #94 - GET /api/reports - Lister tous les signalements
     * #102 - Filtrage par utilisateur (query param: user_id)
     * #103 - Filtrage par statut (query param: status)
     */
    public function index(Request $request)
    {
        $query = Report::with(['user', 'road']);

        // #102 - Filtrage par utilisateur
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // #103 - Filtrage par statut
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filtrage optionnel par road_id
        if ($request->has('road_id')) {
            $query->where('road_id', $request->input('road_id'));
        }

        $reports = $query->orderByDesc('created_at')->get();
        return response()->json($reports);
    }

    /**
     * #96 - GET /api/reports/{id} - Afficher un signalement
     */
    public function show($id)
    {
        $report = Report::with(['user', 'road'])->findOrFail($id);
        return response()->json($report);
    }

    /**
     * #95 - POST /api/reports - Créer un signalement
     * #99 - Validation Request pour création (StoreReportRequest)
     * #101 - Gestion upload photos
     */
    public function store(StoreReportRequest $request)
    {
        $user = $request->user();
        \Log::info('API store report - User authentifié: ' . ($user ? $user->id . ' (' . $user->name . ')' : 'NULL'));

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $data = [
            'user_id'     => $user->id,
            'road_id'     => $request->validated()['road_id'] ?? null,
            'target_type' => $request->validated()['target_type'],
            'report_date' => $request->validated()['report_date'],
            'reason'      => $request->validated()['reason'],
            'status'      => 'pending',
        ];

        // #101 - Upload photo
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('reports/photos', 'public');
            $data['photo_path'] = $path;
        }

        $report = Report::create($data);

        \Log::info('Signalement créé - ID: ' . $report->id . ' pour user_id: ' . $user->id);

        return response()->json([
            'message' => 'Signalement créé',
            'report'  => $report->load(['user', 'road']),
        ], 201);
    }

    /**
     * #97 - PUT /api/reports/{id} - Mettre à jour un signalement (Manager only)
     * #100 - Validation Request pour mise à jour (UpdateReportRequest)
     * #101 - Gestion upload photos (mise à jour)
     */
    public function update(UpdateReportRequest $request, $id)
    {
        $report = Report::findOrFail($id);

        $data = $request->validated();

        // #101 - Upload photo (remplacement)
        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($report->photo_path) {
                Storage::disk('public')->delete($report->photo_path);
            }
            $path = $request->file('photo')->store('reports/photos', 'public');
            $data['photo_path'] = $path;
        }

        // Retirer 'photo' des données validées (c'est un fichier, pas un champ BDD)
        unset($data['photo']);

        $report->update($data);

        \Log::info('Signalement mis à jour - ID: ' . $report->id . ' par manager: ' . $request->user()->id);

        return response()->json([
            'message' => 'Signalement mis à jour',
            'report'  => $report->load(['user', 'road']),
        ]);
    }

    /**
     * #98 - DELETE /api/reports/{id} - Supprimer un signalement (Manager only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'manager') {
            return response()->json(['error' => 'Accès refusé. Réservé aux managers.'], 403);
        }

        $report = Report::findOrFail($id);

        // Supprimer la photo associée si elle existe
        if ($report->photo_path) {
            Storage::disk('public')->delete($report->photo_path);
        }

        $report->delete();

        \Log::info('Signalement supprimé - ID: ' . $id . ' par manager: ' . $user->id);

        return response()->json(['message' => 'Signalement supprimé']);
    }

    /**
     * #102 - GET /api/reports/my - Signalements de l'utilisateur connecté
     */
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
