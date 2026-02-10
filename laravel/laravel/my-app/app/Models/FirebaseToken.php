<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirebaseToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_name',
        'device_id',
        'is_active',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'metadata' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Relation: Un token Firebase appartient à un User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enregistrer l'utilisation du token
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Désactiver le token
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activer le token
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Scope: Obtenir les tokens actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Obtenir les tokens inactifs
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Obtenir les tokens pour un appareil
     */
    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope: Obtenir les tokens non utilisés depuis X jours
     */
    public function scopeUnusedSinceDays($query, int $days)
    {
        $date = now()->subDays($days);
        return $query->where('last_used_at', '<', $date)->orWhereNull('last_used_at');
    }
}
