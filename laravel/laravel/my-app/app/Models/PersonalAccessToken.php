<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    protected $fillable = ['tokenable_type', 'tokenable_id', 'name', 'token', 'abilities', 'expires_at', 'last_used_at'];
    protected $casts = ['expires_at' => 'datetime', 'last_used_at' => 'datetime'];

    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
