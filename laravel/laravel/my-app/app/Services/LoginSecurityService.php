<?php

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion de la sécurité des tentatives de connexion
 * Encapsule toute la logique métier liée aux tentatives de login
 */
class LoginSecurityService
{
    /**
     * Nombre maximum de tentatives autorisées
     */
    private int $maxAttempts = 3;

    /**
     * Durée de la fenêtre de temps (minutes) pour compter les tentatives
     */
    private int $lockoutWindow = 15;

    /**
     * Durée du verrouillage du compte (secondes)
     */
    private int $lockDuration = 3600;

    /**
     * Obtient le nombre maximum de tentatives
     */
    public function getMaxAttempts(): int
    {
        return config('auth.max_login_attempts', $this->maxAttempts);
    }

    /**
     * Obtient la durée de la fenêtre de tentatives
     */
    public function getLockoutWindow(): int
    {
        return config('auth.lockout_window', $this->lockoutWindow);
    }

    /**
     * Obtient la durée du verrouillage
     */
    public function getLockDuration(): int
    {
        return config('auth.lock_duration', $this->lockDuration);
    }

    /**
     * Enregistre une tentative échouée
     */
    public function recordFailedAttempt(string $email, string $ipAddress, string $userAgent = '', ?string $reason = null): void
    {
        try {
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'success' => false,
                'failure_reason' => $reason ?? 'Identifiants invalides',
            ]);

            Log::warning("Tentative de connexion échouée", [
                'email' => $email,
                'ip_address' => $ipAddress,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement de la tentative échouée: ' . $e->getMessage());
        }
    }

    /**
     * Enregistre une tentative réussie
     */
    public function recordSuccessfulAttempt(int $userId, string $email, string $ipAddress, string $userAgent = ''): void
    {
        try {
            LoginAttempt::create([
                'user_id' => $userId,
                'email' => $email,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'success' => true,
            ]);

            Log::info("Connexion réussie", [
                'user_id' => $userId,
                'email' => $email,
                'ip_address' => $ipAddress,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement de la tentative réussie: ' . $e->getMessage());
        }
    }

    /**
     * Compte les tentatives échouées récentes
     */
    public function countFailedAttempts(string $email): int
    {
        return LoginAttempt::countFailedAttempts($email, $this->getLockoutWindow());
    }

    /**
     * Vérifie si le seuil de tentatives est dépassé
     */
    public function hasExceededMaxAttempts(string $email): bool
    {
        return $this->countFailedAttempts($email) >= $this->getMaxAttempts();
    }

    /**
     * Obtient le nombre de tentatives restantes
     */
    public function getRemainingAttempts(string $email): int
    {
        $failed = $this->countFailedAttempts($email);
        return max(0, $this->getMaxAttempts() - $failed);
    }

    /**
     * Vérifie si un utilisateur doit être verrouillé
     */
    public function shouldLockUser(string $email, ?User $user): bool
    {
        if (!$this->hasExceededMaxAttempts($email)) {
            return false;
        }

        if ($user) {
            $user->lockAccount('Trop de tentatives de connexion échouées');
            Log::warning("Utilisateur {$email} verrouillé suite à trop de tentatives");
        }

        return true;
    }

    /**
     * Réinitialise les tentatives échouées
     */
    public function clearFailedAttempts(string $email): void
    {
        LoginAttempt::where('email', $email)
            ->where('success', false)
            ->delete();
    }

    /**
     * Obtient un résumé des tentatives pour un email
     */
    public function getAttemptsSummary(string $email): array
    {
        $failedCount = $this->countFailedAttempts($email);
        $remaining = $this->getRemainingAttempts($email);

        return [
            'failed_attempts' => $failedCount,
            'remaining_attempts' => $remaining,
            'max_attempts' => $this->getMaxAttempts(),
            'exceeded' => $failedCount >= $this->getMaxAttempts(),
            'lockout_window_minutes' => $this->getLockoutWindow(),
        ];
    }

    /**
     * Valide et traite une tentative de connexion échouée
     */
    public function handleFailedLogin(Request $request, string $email, ?User $user = null): array
    {
        $this->recordFailedAttempt($email, $request->ip());

        $summary = $this->getAttemptsSummary($email);

        if ($this->shouldLockUser($email, $user)) {
            return array_merge($summary, ['locked' => true]);
        }

        return array_merge($summary, ['locked' => false]);
    }

    /**
     * Nettoie et traite une connexion réussie
     */
    public function handleSuccessfulLogin(int $userId, string $email, Request $request): void
    {
        $this->recordSuccessfulAttempt($userId, $email, $request->ip(), $request->userAgent());
        $this->clearFailedAttempts($email);
    }

    /**
     * Obtient les statistiques d'un email
     */
    public function getEmailStatistics(string $email): array
    {
        return LoginAttempt::getEmailStatistics($email);
    }

    /**
     * Obtient les statistiques d'une IP
     */
    public function getIpStatistics(string $ipAddress): array
    {
        return LoginAttempt::getIpStatistics($ipAddress);
    }

    /**
     * Détecte les activités suspectes
     */
    public function detectSuspiciousActivity(): array
    {
        return LoginAttempt::detectSuspiciousActivity(
            $this->getLockoutWindow(),
            5 // seuil minimum
        );
    }

    /**
     * Obtient l'historique des tentatives pour un email
     */
    public function getAttemptHistory(string $email, int $limit = 10): array
    {
        $attempts = LoginAttempt::forEmail($email)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($attempt) => [
                'id' => $attempt->id,
                'email' => $attempt->email,
                'ip_address' => $attempt->ip_address,
                'user_agent' => $attempt->user_agent,
                'success' => $attempt->success,
                'failure_reason' => $attempt->failure_reason,
                'created_at' => $attempt->created_at->toIso8601String(),
            ])
            ->toArray();

        return $attempts;
    }

    /**
     * Enregistre une tentative échouée complète avec raison
     */
    public function handleFailedLoginWithReason(Request $request, string $email, string $reason, ?User $user = null): array
    {
        $this->recordFailedAttempt($email, $request->ip(), $request->userAgent(), $reason);

        $summary = $this->getAttemptsSummary($email);

        if ($this->shouldLockUser($email, $user)) {
            return array_merge($summary, [
                'locked' => true,
                'failure_reason' => $reason,
            ]);
        }

        return array_merge($summary, [
            'locked' => false,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Nettoie les anciennes tentatives
     */
    public function cleanupOldAttempts(int $daysOld = 30): int
    {
        $deleted = LoginAttempt::cleanupOldAttempts($daysOld);
        Log::info("Nettoyage des tentatives de connexion", ['deleted' => $deleted, 'days_old' => $daysOld]);
        return $deleted;
    }
}
