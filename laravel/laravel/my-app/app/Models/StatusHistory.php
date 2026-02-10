<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusHistory extends Model
{
    protected $table = 'status_history';

    protected $fillable = [
        'roadwork_id',
        'old_status',
        'new_status',
        'reason',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * Relation: Un changement de statut appartient à un Roadwork
     */
    public function roadwork(): BelongsTo
    {
        return $this->belongsTo(Roadwork::class);
    }

    /**
     * Relation: Un changement est effectué par un User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Obtenir le libellé du statut ancien
     */
    public function getOldStatusLabel(): string
    {
        return $this->getStatusLabel($this->old_status);
    }

    /**
     * Obtenir le libellé du nouveau statut
     */
    public function getNewStatusLabel(): string
    {
        return $this->getStatusLabel($this->new_status);
    }

    /**
     * Convertir un statut en format lisible
     */
    private function getStatusLabel(?string $status): string
    {
        return match($status) {
            'planned' => 'Planifié',
            'in_progress' => 'En cours',
            'completed' => 'Complété',
            'paused' => 'En pause',
            null => 'N/A',
            default => $status,
        };
    }
}
