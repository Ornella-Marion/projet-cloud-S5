<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens;
    protected $fillable = ['email', 'password', 'name', 'role', 'is_active'];
    protected $hidden = ['password'];

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    public function accountLock(): HasOne
    {
        return $this->hasOne(AccountLock::class);
    }

    public function isLocked(): bool
    {
        $lock = $this->accountLock;
        if (!$lock) return false;
        if ($lock->unlock_at && now()->greaterThan($lock->unlock_at)) {
            $lock->delete();
            return false;
        }
        return true;
    }

    public function lockAccount($reason = 'Too many failed login attempts'): void
    {
        $lockDuration = config('auth.lock_duration', 3600);
        AccountLock::updateOrCreate(
            ['user_id' => $this->id],
            [
                'locked_at' => now(),
                'unlock_at' => now()->addSeconds($lockDuration),
                'reason' => $reason,
            ]
        );
    }

    public function unlockAccount(): void
    {
        $this->accountLock()?->delete();
    }
}
