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
}
