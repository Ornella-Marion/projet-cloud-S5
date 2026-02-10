<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roadwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget',
        'finished_at',
        'status_id',
        'road_id',
        'enterprise_id',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function road()
    {
        return $this->belongsTo(Road::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }
}