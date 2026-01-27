<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Road;

class RoadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Road::create([
            'id' => 1,
            'designation' => 'Route Nationale 1 - Genève',
            'longitude' => 6.14,
            'latitude' => 46.20,
            'area' => 150.50,
        ]);

        Road::create([
            'id' => 2,
            'designation' => 'Route Cantonale 5 - Lausanne',
            'longitude' => 6.63,
            'latitude' => 46.52,
            'area' => 200.75,
        ]);

        Road::create([
            'id' => 3,
            'designation' => 'Autoroute A1 - Zurich',
            'longitude' => 8.55,
            'latitude' => 47.38,
            'area' => 300.00,
        ]);
    }
}
