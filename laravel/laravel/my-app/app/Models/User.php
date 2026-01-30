<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Traits\AccountLockTrait;

class User extends Model
{
    use HasApiTokens, AccountLockTrait;
    protected $fillable = ['email', 'password', 'name', 'role', 'is_active'];
    protected $hidden = ['password'];

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }
}
