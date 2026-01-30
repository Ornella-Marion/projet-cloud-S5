<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests
{
    /**
     * Middleware de rate limiting pour endpoints sensibles
     * 
     * Usage dans les routes:
     * Route::post('/login', [...])
     *     ->middleware('throttle:6,1')  // 6 requêtes par minute
     * 
     * Route::post('/admin/unlock/{id}', [...])
     *     ->middleware('throttle:10,1') // 10 requêtes par minute
     */

    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Traiter une requête HTTP entrante.
     */
    public function handle(Request $request, Closure $next, ...$limits): Response
    {
        foreach ($limits as $limit) {
            if ($this->limiter->tooManyAttempts($key = $this->resolveRequestSignature($request), $limit)) {
                return $this->buildResponse($key, $limit);
            }

            $this->limiter->hit($key, $minutes = 1);
        }

        $response = $next($request);

        foreach ($limits as $limit) {
            $response = $this->addHeaders(
                $response,
                $limit,
                $this->calculateRemainingAttempts($key, $limit)
            );
        }

        return $response;
    }

    /**
     * Résoudre la clé de signature de la requête.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Utiliser l'adresse IP si l'utilisateur n'est pas authentifié
        if ($request->user()) {
            return sha1('throttle:' . $request->user()->id);
        }

        return sha1('throttle:' . $request->ip());
    }

    /**
     * Créer la réponse en cas de rate limit dépassé.
     */
    protected function buildResponse(string $key, int $limit): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'Trop de requêtes',
            'message' => 'Vous avez dépassé la limite de requêtes. Réessayez dans ' . $retryAfter . ' secondes.',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Ajouter les headers de rate limiting à la réponse.
     */
    protected function addHeaders(Response $response, int $limit, int $remaining): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset' => time() + $this->limiter->availableIn($this->resolveRequestSignature(app('request'))),
        ]);

        return $response;
    }

    /**
     * Calculer les tentatives restantes.
     */
    protected function calculateRemainingAttempts(string $key, int $limit): int
    {
        return max(0, $limit - $this->limiter->attempts($key));
    }
}
