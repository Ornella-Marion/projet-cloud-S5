<?php

namespace App\Http\Controllers\Api;

use App\Models\Roadwork;
use App\Models\StatusHistory;
use App\Models\Traits\NotificationTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RoadworkController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/roadworks",
     *     summary="Lister tous les travaux routiers",
     *     tags={"Roadworks"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des travaux routiers",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Roadwork"))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(): JsonResponse
    {
        $roadworks = Roadwork::with(['creator', 'photos', 'statusHistory'])->paginate(15);
        return response()->json($roadworks, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/roadworks",
     *     summary="Créer un nouveau travail routier",
     *     tags={"Roadworks"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","location"},
     *             @OA\Property(property="title", type="string", example="Réparation route principale"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="location", type="string", example="Rue de la Paix, Zurich"),
     *             @OA\Property(property="latitude", type="number", format="double"),
     *             @OA\Property(property="longitude", type="number", format="double"),
     *             @OA\Property(property="planned_start_date", type="string", format="date-time"),
     *             @OA\Property(property="planned_end_date", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Travail créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Roadwork")
     *     ),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'planned_start_date' => 'nullable|date_format:Y-m-d\TH:i:s',
            'planned_end_date' => 'nullable|date_format:Y-m-d\TH:i:s',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $roadwork = Roadwork::create($validated);

        return response()->json($roadwork->load(['creator', 'photos', 'statusHistory']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/roadworks/{id}",
     *     summary="Obtenir les détails d'un travail routier",
     *     tags={"Roadworks"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du travail",
     *         @OA\JsonContent(ref="#/components/schemas/Roadwork")
     *     ),
     *     @OA\Response(response=404, description="Travail non trouvé")
     * )
     */
    public function show(Roadwork $roadwork): JsonResponse
    {
        return response()->json($roadwork->load(['creator', 'photos', 'statusHistory']), 200);
    }

    /**
     * @OA\Put(
     *     path="/api/roadworks/{id}",
     *     summary="Mettre à jour un travail routier",
     *     tags={"Roadworks"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="status", type="string", enum={"planned","in_progress","completed","paused"}),
     *             @OA\Property(property="started_at", type="string", format="date-time"),
     *             @OA\Property(property="completed_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Travail mis à jour"),
     *     @OA\Response(response=404, description="Travail non trouvé")
     * )
     */
    public function update(Request $request, Roadwork $roadwork): JsonResponse
    {
        $oldStatus = $roadwork->status;
        
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:planned,in_progress,completed,paused',
            'started_at' => 'nullable|date_format:Y-m-d\TH:i:s',
            'completed_at' => 'nullable|date_format:Y-m-d\TH:i:s',
            'notes' => 'nullable|string',
        ]);

        // Si le statut change, créer un enregistrement dans status_history
        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            // Enregistrer automatiquement started_at quand on passe à "in_progress"
            if ($validated['status'] === 'in_progress' && !isset($validated['started_at'])) {
                $validated['started_at'] = now();
            }

            // Enregistrer automatiquement completed_at quand on passe à "completed"
            if ($validated['status'] === 'completed' && !isset($validated['completed_at'])) {
                $validated['completed_at'] = now();
            }

            // Créer l'entrée status_history
            StatusHistory::create([
                'roadwork_id' => $roadwork->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);

            // 🔔 Créer une notification automatiquement
            NotificationTrait::createStatusChangeNotification(
                $roadwork->id,
                $oldStatus,
                $validated['status'],
                auth()->id()
            );
        }

        $roadwork->update($validated);

        return response()->json($roadwork->load(['creator', 'photos', 'statusHistory']), 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/roadworks/{id}",
     *     summary="Supprimer un travail routier",
     *     tags={"Roadworks"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Travail supprimé"),
     *     @OA\Response(response=404, description="Travail non trouvé")
     * )
     */
    public function destroy(Roadwork $roadwork): JsonResponse
    {
        $roadwork->delete();
        return response()->json(['message' => 'Travail supprimé avec succès'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/roadworks/{id}/status-history",
     *     summary="Obtenir l'historique des changements de statut",
     *     tags={"Roadworks"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique des changements",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     */
    public function statusHistory(Roadwork $roadwork): JsonResponse
    {
        $history = $roadwork->statusHistory()->with('user')->latest('changed_at')->get();
        return response()->json($history, 200);
    }
}
