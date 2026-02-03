<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\FirebaseAuthService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $firebaseService = new FirebaseAuthService();
        
        $users = [
            // Manager - peut inscrire des utilisateurs et débloquer des comptes
            [
                'name' => 'Manager Admin',
                'email' => 'manager@test.com',
                'password' => 'password123',
                'role' => 'manager',
                'is_active' => true,
            ],
            // Utilisateurs normaux
            [
                'name' => 'Jean Dupont',
                'email' => 'jean@test.com',
                'password' => 'password123',
                'role' => 'user',
                'is_active' => true,
            ],
            [
                'name' => 'Marie Martin',
                'email' => 'marie@test.com',
                'password' => 'password123',
                'role' => 'user',
                'is_active' => true,
            ],
            [
                'name' => 'Velo Test',
                'email' => 'velo@test.com',
                'password' => 'password123',
                'role' => 'user',
                'is_active' => true,
            ],
            // Visiteur
            [
                'name' => 'Visiteur Demo',
                'email' => 'visiteur@test.com',
                'password' => 'password123',
                'role' => 'visitor',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            // 1. Créer dans Firebase
            $result = $firebaseService->createUser($userData['email'], $userData['password']);
            if ($result['success']) {
                $this->command->info("✅ Firebase: {$userData['email']} créé");
            } else {
                $this->command->warn("⚠️ Firebase: {$userData['email']} - {$result['error']}");
            }

            // 2. Créer dans Laravel
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => $userData['role'],
                'is_active' => $userData['is_active'],
            ]);
            $this->command->info("✅ Laravel: {$userData['email']} créé avec rôle {$userData['role']}");
        }

        $this->command->info('');
        $this->command->info('📋 Utilisateurs de test:');
        $this->command->info('   manager@test.com / password123 (manager)');
        $this->command->info('   jean@test.com / password123 (user)');
        $this->command->info('   marie@test.com / password123 (user)');
        $this->command->info('   velo@test.com / password123 (user)');
        $this->command->info('   visiteur@test.com / password123 (visitor)');
    }
}