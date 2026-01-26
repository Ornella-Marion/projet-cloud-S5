<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLock extends Model
{
    protected $fillable = ['user_id', 'locked_at', 'unlock_at', 'reason'];
    protected $casts = ['locked_at' => 'datetime', 'unlock_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return !$this->unlock_at || now()->lessThan($this->unlock_at);
    }
}
