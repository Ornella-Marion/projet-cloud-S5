<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Road extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'longitude',
        'latitude',
        'area',
    ];

    public function roadworks(): HasMany
    {
        return $this->hasMany(Roadwork::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}