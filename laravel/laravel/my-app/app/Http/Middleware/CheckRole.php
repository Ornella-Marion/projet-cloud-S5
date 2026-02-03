<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Middleware générique pour vérifier les rôles utilisateur
     * 
     * Usage dans les routes:
     * Route::post('/admin/users/{id}/unlock', [...])
     *     ->middleware('role:manager')
     *     ->middleware('role:admin,manager')  // Plusieurs rôles
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$roles Rôles autorisés (séparés par des virgules)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est authentifié
        if (!$user) {
            return response()->json([
                'error' => 'Non authentifié',
                'message' => 'Une authentification est requise pour accéder à cette ressource',
            ], 401);
        }

        // Récupérer le rôle de l'utilisateur
        $userRole = $user->role ?? 'guest';

        // Vérifier si le rôle de l'utilisateur est dans la liste des rôles autorisés
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'error' => 'Accès refusé',
                'message' => 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource',
                'current_role' => $userRole,
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
