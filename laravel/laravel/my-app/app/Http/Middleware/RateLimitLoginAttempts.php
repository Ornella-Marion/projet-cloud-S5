<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware de protection contre les attaques par force brute
 * Vérifie les tentatives de connexion échouées et gère le verrouillage des comptes
 */
class RateLimitLoginAttempts
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Appliquer uniquement aux routes de login
        if ($request->getMethod() === 'POST' && $request->is('api/auth/login')) {
            $email = $request->input('email');
            
            if ($email) {
                // Vérifier si l'utilisateur existe et est verrouillé
                $user = User::where('email', $email)->first();
                if ($user && $user->isLocked()) {
                    return response()->json([
                        'error' => 'Compte verrouillé pour raison de sécurité',
                        'remaining_time' => $user->accountLock?->unlock_at?->diffInMinutes(now()) . ' minutes'
                    ], 429);
                }
            }
        }

        return $next($request);
    }
}
