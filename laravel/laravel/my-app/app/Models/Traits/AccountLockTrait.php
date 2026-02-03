<?php

namespace App\Models\Traits;

use App\Models\AccountLock;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trait AccountLockTrait
 * Gère le verrouillage et le déverrouillage des comptes utilisateurs
 * Encapsule toute la logique métier liée au verrouillage de compte
 */
trait AccountLockTrait
{
    /**
     * Relation vers le verrou du compte
     *
     * @return HasOne
     */
    public function accountLock(): HasOne
    {
        return $this->hasOne(AccountLock::class);
    }

    /**
     * Vérifie si le compte est verrouillé
     * Inclut la gestion automatique de l'expiration du verrouillage
     *
     * @return bool True si le compte est actuellement verrouillé
     */
    public function isLocked(): bool
    {
        $lock = $this->accountLock;

        // Aucun verrou enregistré
        if (!$lock) {
            return false;
        }

        // Vérifier si le verrouillage a expiré
        if ($lock->unlock_at && now()->greaterThan($lock->unlock_at)) {
            // Supprimer automatiquement le verrou expiré
            $lock->delete();
            return false;
        }

        // Le compte est toujours verrouillé
        return true;
    }

    /**
     * Verrouille le compte pour une durée configurée
     * Crée ou met à jour l'enregistrement AccountLock
     *
     * @param string $reason Raison du verrouillage (défaut: "Too many failed login attempts")
     * @return AccountLock L'enregistrement de verrouillage créé/mis à jour
     */
    public function lockAccount(string $reason = 'Too many failed login attempts'): AccountLock
    {
        $lockDuration = config('auth.lock_duration', 3600); // 1 heure par défaut

        return AccountLock::updateOrCreate(
            ['user_id' => $this->id],
            [
                'locked_at' => now(),
                'unlock_at' => now()->addSeconds($lockDuration),
                'reason' => $reason,
            ]
        );
    }

    /**
     * Déverrouille le compte immédiatement
     * Supprime l'enregistrement AccountLock associé
     *
     * @return bool True si un verrou a été supprimé, false sinon
     */
    public function unlockAccount(): bool
    {
        $lock = $this->accountLock;

        if ($lock) {
            $lock->delete();
            return true;
        }

        return false;
    }

    /**
     * Obtient le nombre de secondes avant le déverrouillage automatique
     *
     * @return int|null Nombre de secondes restantes, ou null si pas verrouillé
     */
    public function getSecondsUntilUnlock(): ?int
    {
        $lock = $this->accountLock;

        if (!$lock || !$lock->unlock_at) {
            return null;
        }

        // Si le verrouillage a expiré
        if (now()->greaterThan($lock->unlock_at)) {
            $lock->delete();
            return null;
        }

        return now()->diffInSeconds($lock->unlock_at);
    }

    /**
     * Obtient la raison du verrouillage du compte
     *
     * @return string|null La raison du verrouillage, ou null si pas verrouillé
     */
    public function getLockReason(): ?string
    {
        if (!$this->isLocked()) {
            return null;
        }

        return $this->accountLock?->reason;
    }

    /**
     * Vérifie si le compte est verrouillé et retourne les détails du verrouillage
     *
     * @return array Tableau avec les détails du verrouillage ou un tableau vide
     */
    public function getLockDetails(): array
    {
        if (!$this->isLocked()) {
            return [];
        }

        $lock = $this->accountLock;
        $secondsUntilUnlock = $this->getSecondsUntilUnlock();

        return [
            'is_locked' => true,
            'locked_at' => $lock->locked_at,
            'unlock_at' => $lock->unlock_at,
            'reason' => $lock->reason,
            'seconds_remaining' => $secondsUntilUnlock,
        ];
    }
}
