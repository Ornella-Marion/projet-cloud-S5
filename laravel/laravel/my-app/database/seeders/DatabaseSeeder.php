<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ordre important : respecter les dépendances des clés étrangères
        $this->call([
            UserSeeder::class,       // Utilisateurs (manager, users, etc.)
            RoadSeeder::class,       // Routes
            StatusSeeder::class,     // Statuts des travaux
            EnterpriseSeeder::class, // Entreprises
            RoadworkSeeder::class,   // Travaux routiers (dépend de roads, status, enterprises)
            ReportSeeder::class,     // Signalements (dépend de users, roads)
        ]);
        
        $this->command->info('✅ Toutes les données de test ont été générées !');
    }
}
