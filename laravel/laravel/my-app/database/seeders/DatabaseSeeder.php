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


        User::create([
            'name' => 'Manager Default',
            'email' => 'manager@example.com',
            'password' => Hash::make('manager123'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        // Utilisateur test
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('test123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Utilisateur supplémentaire
        User::create([
            'name' => 'Alice Dupont',
            'email' => 'alice@example.com',
            'password' => Hash::make('alice123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Utilisateur pour test Postman
        User::create([
            'name' => 'User Example',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'is_active' => true,
        ]);
    }
}
