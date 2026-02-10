<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Roadwork;
use Carbon\Carbon;

class RoadworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Route 1 - Avenue de l'Indépendance - En cours
        Roadwork::create([
            'budget' => 250000000, // 250 millions Ar
            'finished_at' => Carbon::now()->addMonths(3),
            'status_id' => 2, // En cours
            'road_id' => 1,
            'enterprise_id' => 1, // COLAS
        ]);

        // Route 2 - Route d'Ilafy - Planifié
        Roadwork::create([
            'budget' => 180000000, // 180 millions Ar
            'finished_at' => Carbon::now()->addMonths(6),
            'status_id' => 1, // Planifié
            'road_id' => 2,
            'enterprise_id' => 2, // SOGEA-SATOM
        ]);

        // Route 3 - Boulevard de la Liberté - Terminé
        Roadwork::create([
            'budget' => 320000000, // 320 millions Ar
            'finished_at' => Carbon::now()->subMonths(1),
            'status_id' => 4, // Terminé
            'road_id' => 3,
            'enterprise_id' => 3, // RAVINALA ROADS
        ]);

        // Route 4 - Route de Vangaindrano - Presque terminé
        Roadwork::create([
            'budget' => 450000000, // 450 millions Ar
            'finished_at' => Carbon::now()->addWeeks(2),
            'status_id' => 3, // Presque terminé
            'road_id' => 4,
            'enterprise_id' => 4, // MAMY BTP
        ]);

        // Route 5 - Route de Sambava - Suspendu
        Roadwork::create([
            'budget' => 280000000, // 280 millions Ar
            'finished_at' => Carbon::now()->addMonths(8),
            'status_id' => 5, // Suspendu
            'road_id' => 5,
            'enterprise_id' => 5, // HOLCIM
        ]);

        // Route 6 - Rue Joffre - En cours
        Roadwork::create([
            'budget' => 95000000, // 95 millions Ar
            'finished_at' => Carbon::now()->addMonths(2),
            'status_id' => 2, // En cours
            'road_id' => 6,
            'enterprise_id' => 1, // COLAS
        ]);

        // Route 7 - Route d'Ambohimanga - Planifié
        Roadwork::create([
            'budget' => 150000000, // 150 millions Ar
            'finished_at' => Carbon::now()->addMonths(5),
            'status_id' => 1, // Planifié
            'road_id' => 7,
            'enterprise_id' => 6, // JIRAMA
        ]);

        // Route 8 - Avenue de France - Presque terminé
        Roadwork::create([
            'budget' => 200000000, // 200 millions Ar
            'finished_at' => Carbon::now()->addDays(10),
            'status_id' => 3, // Presque terminé
            'road_id' => 8,
            'enterprise_id' => 2, // SOGEA-SATOM
        ]);
    }
}
