<?php

namespace App\Http\Traits;

use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Trait pour gérer les tentatives de connexion et la sécurité
 * Centralise la logique de suivi des tentatives de login et du verrouillage de compte
 */
trait LoginAttemptTrait
{
    /**
     * Nombre maximum de tentatives de connexion autorisées
     */
    protected int $maxLoginAttempts = 3;

    /**
     * Durée de la fenêtre de temps (en minutes) pour compter les tentatives
     */
    protected int $lockoutDuration = 15;

    /**
     * Enregistre une tentative de connexion échouée
     *
     * @param string $email
     * @param Request $request
     * @param string|null $reason
     * @return void
     */
    protected function recordFailedLoginAttempt(string $email, Request $request, ?string $reason = null): void
    {
        try {
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'success' => false,
                'failure_reason' => $reason ?? 'Identifiants invalides',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement de la tentative échouée: ' . $e->getMessage());
        }
    }

    /**
     * Enregistre une tentative de connexion réussie
     *
     * @param int $userId
     * @param string $email
     * @param Request $request
     * @return void
     */
    protected function recordSuccessfulLoginAttempt(int $userId, string $email, Request $request): void
    {
        try {
            LoginAttempt::create([
                'user_id' => $userId,
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement de la connexion réussie: ' . $e->getMessage());
        }
    }

    /**
     * Compte les tentatives échouées pour une adresse email
     *
     * @param string $email
     * @return int
     */
    protected function countFailedAttempts(string $email): int
    {
        return LoginAttempt::countFailedAttempts($email, $this->lockoutDuration);
    }

    /**
     * Vérifie si le nombre maximum de tentatives est atteint
     *
     * @param string $email
     * @return bool
     */
    protected function hasExceededMaxAttempts(string $email): bool
    {
        return $this->countFailedAttempts($email) >= $this->maxLoginAttempts;
    }

    /**
     * Obtient le nombre de tentatives restantes avant verrouillage
     *
     * @param string $email
     * @return int
     */
    protected function getRemainingAttempts(string $email): int
    {
        $failed = $this->countFailedAttempts($email);
        return max(0, $this->maxLoginAttempts - $failed);
    }

    /**
     * Réinitialise les tentatives échouées pour une adresse email
     *
     * @param string $email
     * @return void
     */
    protected function clearFailedAttempts(string $email): void
    {
        LoginAttempt::where('email', $email)
            ->where('success', false)
            ->delete();
    }

    /**
     * Obtient les détails des tentatives pour un email
     *
     * @param string $email
     * @return array
     */
    protected function getAttemptDetails(string $email): array
    {
        return [
            'failed_attempts' => $this->countFailedAttempts($email),
            'remaining_attempts' => $this->getRemainingAttempts($email),
            'exceeded' => $this->hasExceededMaxAttempts($email),
        ];
    }
}
