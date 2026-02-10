<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LoginAttempt extends Model
{
    protected $fillable = ['user_id', 'email', 'ip_address', 'success', 'user_agent', 'failure_reason'];
    protected $casts = ['success' => 'boolean', 'created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour les tentatives échouées
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('success', false);
    }

    /**
     * Scope pour les tentatives réussies
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('success', true);
    }

    /**
     * Scope pour une période donnée (en minutes)
     */
    public function scopeWithinMinutes(Builder $query, int $minutes): Builder
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }

    /**
     * Scope pour un email spécifique
     */
    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope pour une IP spécifique
     */
    public function scopeForIpAddress(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope pour un utilisateur spécifique
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Compte les tentatives échouées récentes pour un email
     */
    public static function countFailedAttempts(string $email, int $minutes = 15): int
    {
        return self::failed()
            ->forEmail($email)
            ->withinMinutes($minutes)
            ->count();
    }

    /**
     * Compte les tentatives échouées par IP
     */
    public static function countFailedAttemptsByIp(string $ipAddress, int $minutes = 15): int
    {
        return self::failed()
            ->forIpAddress($ipAddress)
            ->withinMinutes($minutes)
            ->count();
    }

    /**
     * Obtient les tentatives échouées récentes par email
     */
    public static function getRecentFailedAttempts(string $email, int $minutes = 15): \Illuminate\Database\Eloquent\Collection
    {
        return self::failed()
            ->forEmail($email)
            ->withinMinutes($minutes)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Obtient les tentatives échouées par IP (potentiellement une attaque)
     */
    public static function getSuspiciousIpAttempts(string $ipAddress, int $minutes = 15): \Illuminate\Database\Eloquent\Collection
    {
        return self::failed()
            ->forIpAddress($ipAddress)
            ->withinMinutes($minutes)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Nettoie les anciennes tentatives (plus de X jours)
     */
    public static function cleanupOldAttempts(int $daysOld = 30): int
    {
        return self::where('created_at', '<', now()->subDays($daysOld))->delete();
    }

    /**
     * Obtient les statistiques d'un email
     */
    public static function getEmailStatistics(string $email): array
    {
        $totalAttempts = self::forEmail($email)->count();
        $recentFailed = self::countFailedAttempts($email, 15);
        $recentSuccessful = self::successful()
            ->forEmail($email)
            ->withinMinutes(15)
            ->count();
        $lastAttempt = self::forEmail($email)->latest('created_at')->first();

        return [
            'email' => $email,
            'total_attempts' => $totalAttempts,
            'failed_attempts_15min' => $recentFailed,
            'successful_attempts_15min' => $recentSuccessful,
            'last_attempt_at' => $lastAttempt?->created_at,
            'last_attempt_ip' => $lastAttempt?->ip_address,
            'last_attempt_success' => $lastAttempt?->success,
        ];
    }

    /**
     * Obtient les statistiques d'une IP
     */
    public static function getIpStatistics(string $ipAddress): array
    {
        $totalAttempts = self::forIpAddress($ipAddress)->count();
        $recentFailed = self::countFailedAttemptsByIp($ipAddress, 15);
        $uniqueEmails = self::forIpAddress($ipAddress)
            ->distinct()
            ->count('email');
        $lastAttempt = self::forIpAddress($ipAddress)->latest('created_at')->first();

        return [
            'ip_address' => $ipAddress,
            'total_attempts' => $totalAttempts,
            'failed_attempts_15min' => $recentFailed,
            'unique_emails_targeted' => $uniqueEmails,
            'last_attempt_at' => $lastAttempt?->created_at,
            'last_attempt_email' => $lastAttempt?->email,
            'last_attempt_success' => $lastAttempt?->success,
        ];
    }

    /**
     * Détecte les tentatives suspectes (force brute)
     */
    public static function detectSuspiciousActivity(int $minutes = 15, int $threshold = 5): array
    {
        $suspicious = [];

        // IPs suspectes
        $suspiciousIps = self::failed()
            ->withinMinutes($minutes)
            ->select('ip_address')
            ->groupBy('ip_address')
            ->havingRaw('count(*) >= ?', [$threshold])
            ->pluck('ip_address');

        foreach ($suspiciousIps as $ip) {
            $suspicious['ips'][] = self::getIpStatistics($ip);
        }

        // Emails ciblés
        $suspiciousEmails = self::failed()
            ->withinMinutes($minutes)
            ->select('email')
            ->groupBy('email')
            ->havingRaw('count(*) >= ?', [$threshold])
            ->pluck('email');

        foreach ($suspiciousEmails as $email) {
            $suspicious['emails'][] = self::getEmailStatistics($email);
        }

        return $suspicious;
    }
}

