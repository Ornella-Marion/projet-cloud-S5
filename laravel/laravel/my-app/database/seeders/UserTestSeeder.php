<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTestSeeder extends Seeder
{
    /**
     * Créer les utilisateurs de test pour les tests Postman/API
     */
    public function run(): void
    {
        // Créer un utilisateur de test avec role 'user'
        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
                'role' => UserRole::USER,
                'email_verified_at' => now(),
            ]
        );

        // Créer un utilisateur manager pour les tests
        User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Test Manager',
                'password' => Hash::make('password123'),
                'role' => UserRole::MANAGER,
                'email_verified_at' => now(),
            ]
        );

        // Créer un utilisateur visitor
        User::firstOrCreate(
            ['email' => 'visitor@example.com'],
            [
                'name' => 'Test Visitor',
                'password' => Hash::make('password123'),
                'role' => UserRole::VISITOR,
                'email_verified_at' => now(),
            ]
        );
    }
}
