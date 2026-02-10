<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoadworkPhoto extends Model
{
    protected $fillable = [
        'roadwork_id',
        'photo_url',
        'photo_path',
        'photo_type',
        'description',
        'taken_at',
        'uploaded_by',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];

    /**
     * Relation: Une photo appartient à un Roadwork
     */
    public function roadwork(): BelongsTo
    {
        return $this->belongsTo(Roadwork::class);
    }

    /**
     * Relation: Une photo est uploadée par un User
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Obtenir le type de photo en format lisible
     */
    public function getPhotoTypeLabel(): string
    {
        return match($this->photo_type) {
            'before' => 'Avant les travaux',
            'during' => 'Pendant les travaux',
            'after' => 'Après les travaux',
            'issue' => 'Problème détecté',
            default => 'Général',
        };
    }
}
