<?php

namespace App\Http\Controllers\Api;

use App\Models\FirebaseToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FirebaseTokenController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/firebase/register-token",
     *     summary="Enregistrer un token Firebase",
     *     tags={"Firebase"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="erZF3dqSfU0:APA91bF2x1y9z0abc123def456ghi789"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 12"),
     *             @OA\Property(property="device_id", type="string", example="device_ios_123"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Token enregistré avec succès"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function registerToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'device_name' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|json',
        ]);

        // Vérifier si le token existe déjà pour cet utilisateur
        $existing = FirebaseToken::where('user_id', auth()->id())
            ->where('token', $validated['token'])
            ->first();

        if ($existing) {
            $existing->recordUsage();
            return response()->json($existing, 200);
        }

        $token = FirebaseToken::create([
            'user_id' => auth()->id(),
            'token' => $validated['token'],
            'device_name' => $validated['device_name'] ?? null,
            'device_id' => $validated['device_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'is_active' => true,
        ]);

        return response()->json($token, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/firebase/tokens",
     *     summary="Lister tous les tokens Firebase de l'utilisateur",
     *     tags={"Firebase"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des tokens",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     */
    public function listTokens(): JsonResponse
    {
        $tokens = auth()->user()->firebaseTokens()->get();
        return response()->json($tokens, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/firebase/tokens/active",
     *     summary="Obtenir les tokens Firebase actifs",
     *     tags={"Firebase"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tokens actifs",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     */
    public function listActiveTokens(): JsonResponse
    {
        $tokens = auth()->user()->firebaseTokens()->active()->get();
        return response()->json($tokens, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/firebase/tokens/{id}",
     *     summary="Obtenir les détails d'un token",
     *     tags={"Firebase"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails du token"),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function showToken(FirebaseToken $token): JsonResponse
    {
        if ($token->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        return response()->json($token, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/firebase/tokens/{id}/deactivate",
     *     summary="Désactiver un token Firebase",
     *     tags={"Firebase"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Token désactivé"),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function deactivateToken(FirebaseToken $token): JsonResponse
    {
        if ($token->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $token->deactivate();
        return response()->json($token, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/firebase/tokens/{id}/activate",
     *     summary="Réactiver un token Firebase",
     *     tags={"Firebase"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Token réactivé")
     * )
     */
    public function activateToken(FirebaseToken $token): JsonResponse
    {
        if ($token->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $token->activate();
        return response()->json($token, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/firebase/tokens/{id}",
     *     summary="Supprimer un token Firebase",
     *     tags={"Firebase"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Token supprimé"),
     *     @OA\Response(response=404, description="Non trouvé")
     * )
     */
    public function deleteToken(FirebaseToken $token): JsonResponse
    {
        if ($token->user_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $token->delete();
        return response()->json(['message' => 'Token supprimé avec succès'], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/firebase/tokens/unused-cleanup",
     *     summary="Supprimer les tokens non utilisés depuis 30 jours",
     *     tags={"Firebase"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(response=200, description="Tokens supprimés")
     * )
     */
    public function cleanupUnusedTokens(): JsonResponse
    {
        $deleted = auth()->user()->firebaseTokens()
            ->unusedSinceDays(30)
            ->delete();

        return response()->json([
            'message' => "$deleted tokens supprimés",
            'deleted_count' => $deleted
        ], 200);
    }
}
