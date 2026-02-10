<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'notifiable_type',
        'notifiable_id',
        'is_read',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Relation: Une notification appartient à un User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation polymorphe: La notification peut être liée à n'importe quel modèle
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Marquer la notification comme lue
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Marquer la notification comme non lue
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Scope: Obtenir les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: Obtenir les notifications lues
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: Filtrer par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
