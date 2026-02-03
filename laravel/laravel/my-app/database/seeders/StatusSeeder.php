<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::create([
            'id' => 1,
            'label' => 'Planifié',
            'percentage' => 0,
        ]);

        Status::create([
            'id' => 2,
            'label' => 'En cours',
            'percentage' => 50,
        ]);

        Status::create([
            'id' => 3,
            'label' => 'Presque terminé',
            'percentage' => 80,
        ]);

        Status::create([
            'id' => 4,
            'label' => 'Terminé',
            'percentage' => 100,
        ]);

        Status::create([
            'id' => 5,
            'label' => 'Suspendu',
            'percentage' => null,
        ]);
    }
}
