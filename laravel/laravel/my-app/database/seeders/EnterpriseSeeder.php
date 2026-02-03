<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Enterprise;

class EnterpriseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Enterprise::create([
            'id' => 1,
            'designation' => 'COLAS Madagascar',
        ]);

        Enterprise::create([
            'id' => 2,
            'designation' => 'SOGEA-SATOM',
        ]);

        Enterprise::create([
            'id' => 3,
            'designation' => 'RAVINALA ROADS',
        ]);

        Enterprise::create([
            'id' => 4,
            'designation' => 'ENTREPRISE MAMY BTP',
        ]);

        Enterprise::create([
            'id' => 5,
            'designation' => 'HOLCIM Madagascar',
        ]);

        Enterprise::create([
            'id' => 6,
            'designation' => 'JIRAMA Travaux',
        ]);
    }
}
