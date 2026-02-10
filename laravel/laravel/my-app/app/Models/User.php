<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = ['email', 'password', 'name', 'role', 'is_active'];
    protected $hidden = ['password'];

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

     /**
     * Relation: Un User a plusieurs Roadworks créés
     */
    public function roadworksCreated(): HasMany
    {
        return $this->hasMany(Roadwork::class, 'created_by');
    }


    /**
     * Relation: Un User a plusieurs photos uploadées
     */
    public function photosUploaded(): HasMany
    {
        return $this->hasMany(RoadworkPhoto::class, 'uploaded_by');
    }


    /**
     * Relation: Un User a plusieurs changements de statut effectués
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(StatusHistory::class, 'changed_by');
    }

    /**
     * Relation: Un User a plusieurs notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
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

    /**
     * Relation: Un User a plusieurs tokens Firebase
     */
    public function firebaseTokens(): HasMany
    {
        return $this->hasMany(FirebaseToken::class);
    }
}
