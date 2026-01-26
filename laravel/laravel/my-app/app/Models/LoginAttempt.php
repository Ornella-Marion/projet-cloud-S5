<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    protected $fillable = ['user_id', 'email', 'ip_address', 'success'];
    protected $casts = ['success' => 'boolean', 'created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function countFailedAttempts(string $email, int $minutes = 15): int
    {
        return self::where('email', $email)
            ->where('success', false)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->count();
    }
}
