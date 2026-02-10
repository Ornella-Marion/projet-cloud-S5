<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Traits\AccountLockTrait;

class User extends Authenticatable
{
    use HasApiTokens, AccountLockTrait;
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

    /**
     * Relation: Un User a plusieurs tokens Firebase
     */
    public function firebaseTokens(): HasMany
    {
        return $this->hasMany(FirebaseToken::class);
    }
}
