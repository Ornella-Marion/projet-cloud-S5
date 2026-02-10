<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PersonalAccessToken;

class TokenAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        $hashedToken = hash('sha256', $token);
        $tokenRecord = PersonalAccessToken::where('token', $hashedToken)->first();

        if (!$tokenRecord) {
            return response()->json(['error' => 'Token invalide'], 401);
        }

        if ($tokenRecord->isExpired()) {
            return response()->json(['error' => 'Token expiré'], 401);
        }

        $user = User::find($tokenRecord->tokenable_id);
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 401);
        }

        $tokenRecord->updateLastUsed();
        auth()->guard('api')->setUser($user);

        return $next($request);
    }
}
