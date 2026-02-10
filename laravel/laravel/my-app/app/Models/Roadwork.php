<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roadwork extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'latitude',
        'longitude',
        'status',
        'planned_start_date',
        'started_at',
        'planned_end_date',
        'completed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'planned_start_date' => 'datetime',
        'started_at' => 'datetime',
        'planned_end_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relation: Un Roadwork appartient à un User (créateur)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation: Un Roadwork a plusieurs photos
     */
    public function photos(): HasMany
    {
        return $this->hasMany(RoadworkPhoto::class);
    }

    /**
     * Relation: Un Roadwork a plusieurs changements de statut
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }

    /**
     * Obtenir le dernier statut
     */
    public function getLatestStatus(): ?StatusHistory
    {
        return $this->statusHistory()->latest('changed_at')->first();
    }

    /**
     * Vérifier si le travail est en cours
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Vérifier si le travail est terminé
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
