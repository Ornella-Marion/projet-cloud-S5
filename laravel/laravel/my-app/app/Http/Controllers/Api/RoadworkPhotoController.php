<?php

namespace App\Http\Controllers\Api;

use App\Models\Roadwork;
use App\Models\RoadworkPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RoadworkPhotoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/roadworks/{id}/photos",
     *     summary="Lister les photos d'un travail routier",
     *     tags={"Roadwork Photos"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des photos",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     */
    public function index(Roadwork $roadwork): JsonResponse
    {
        $photos = $roadwork->photos()->with('uploader')->get();
        return response()->json($photos, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/roadworks/{id}/photos",
     *     summary="Uploader une photo pour un travail routier",
     *     tags={"Roadwork Photos"},
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
     *             required={"file","photo_type"},
     *             @OA\Property(property="file", type="string", format="binary"),
     *             @OA\Property(property="photo_type", type="string", enum={"before","during","after","issue"}),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="taken_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Photo uploadée avec succès"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function store(Request $request, Roadwork $roadwork): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|image|max:5120', // 5MB max
            'photo_type' => 'required|in:before,during,after,issue',
            'description' => 'nullable|string',
            'taken_at' => 'nullable|date_format:Y-m-d\TH:i:s',
        ]);

        // Stocker le fichier
        $path = $request->file('file')->store('roadwork_photos', 'public');
        $url = asset('storage/' . $path);

        $photo = RoadworkPhoto::create([
            'roadwork_id' => $roadwork->id,
            'photo_path' => $path,
            'photo_url' => $url,
            'photo_type' => $validated['photo_type'],
            'description' => $validated['description'] ?? null,
            'taken_at' => $validated['taken_at'] ?? now(),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json($photo->load('uploader'), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/roadwork-photos/{id}",
     *     summary="Obtenir les détails d'une photo",
     *     tags={"Roadwork Photos"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de la photo"),
     *     @OA\Response(response=404, description="Photo non trouvée")
     * )
     */
    public function show(RoadworkPhoto $photo): JsonResponse
    {
        return response()->json($photo->load(['roadwork', 'uploader']), 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/roadwork-photos/{id}",
     *     summary="Supprimer une photo",
     *     tags={"Roadwork Photos"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Photo supprimée"),
     *     @OA\Response(response=404, description="Photo non trouvée")
     * )
     */
    public function destroy(RoadworkPhoto $photo): JsonResponse
    {
        // Supprimer le fichier physique
        if (\Storage::disk('public')->exists($photo->photo_path)) {
            \Storage::disk('public')->delete($photo->photo_path);
        }
        
        $photo->delete();
        return response()->json(['message' => 'Photo supprimée avec succès'], 200);
    }
}
