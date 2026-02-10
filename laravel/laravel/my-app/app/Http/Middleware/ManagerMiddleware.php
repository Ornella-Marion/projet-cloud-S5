<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerMiddleware
{
    /**
     * Middleware pour vérifier que l'utilisateur a le rôle "manager" ou "admin"
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est authentifié
        if (!$user) {
            return response()->json([
                'error' => 'Non authentifié',
            ], 401);
        }

        // Vérifier que l'utilisateur a le rôle 'manager'
        $role = $user->role ?? null;
        if ($role !== UserRole::MANAGER->value) {
            return response()->json([
                'error' => 'Accès refusé. Seuls les managers peuvent accéder à cette ressource.',
                'current_role' => $role,
                'required_role' => UserRole::MANAGER->value,
            ], 403);
        }

        return $next($request);
    }
}
