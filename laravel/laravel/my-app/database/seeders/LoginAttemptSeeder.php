<?php

namespace Database\Seeders;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LoginAttemptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des utilisateurs de test
        $user1 = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password456'),
                'role' => 'user',
            ]
        );

        // 🔴 Scénario 1 : Tentatives échouées massives (FORCE BRUTE)
        // IP attaquante avec 8 tentatives échouées en 15 min sur admin@example.com
        for ($i = 0; $i < 8; $i++) {
            LoginAttempt::create([
                'email' => 'admin@example.com',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'success' => false,
                'failure_reason' => 'Identifiants invalides',
                'created_at' => now()->subMinutes(14 - $i),
            ]);
        }

        // 🔴 Scénario 2 : Ciblage de plusieurs comptes depuis une même IP
        // IP attaquante ciblant 5 emails différents
        $targetEmails = [
            'admin@example.com',
            'root@example.com',
            'test@example.com',
            'demo@example.com',
            'support@example.com',
        ];

        foreach ($targetEmails as $email) {
            for ($i = 0; $i < 3; $i++) {
                LoginAttempt::create([
                    'email' => $email,
                    'ip_address' => '203.0.113.50', // Différente IP attaquante
                    'user_agent' => 'curl/7.68.0',
                    'success' => false,
                    'failure_reason' => 'Identifiants invalides',
                    'created_at' => now()->subMinutes(10 - $i),
                ]);
            }
        }

        // 🟢 Scénario 3 : Connexions légitimes réussies
        // Historique de connexions réussies pour user1
        for ($i = 0; $i < 5; $i++) {
            LoginAttempt::create([
                'user_id' => $user1->id,
                'email' => 'admin@example.com',
                'ip_address' => '192.168.1.50',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'success' => true,
                'created_at' => now()->subDays(5 - $i),
            ]);
        }

        // 🟢 Scénario 4 : Connexions réussies pour user2
        for ($i = 0; $i < 3; $i++) {
            LoginAttempt::create([
                'user_id' => $user2->id,
                'email' => 'user@example.com',
                'ip_address' => '192.168.2.100',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
                'success' => true,
                'created_at' => now()->subDays(3 - $i),
            ]);
        }

        // 🟡 Scénario 5 : Tentatives échouées puis réussie (utilisateur oubliant son mot de passe)
        LoginAttempt::create([
            'email' => 'user@example.com',
            'ip_address' => '192.168.2.100',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
            'success' => false,
            'failure_reason' => 'Identifiants invalides',
            'created_at' => now()->subHours(2),
        ]);

        LoginAttempt::create([
            'email' => 'user@example.com',
            'ip_address' => '192.168.2.100',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
            'success' => false,
            'failure_reason' => 'Identifiants invalides',
            'created_at' => now()->subHours(1)->addMinutes(55),
        ]);

        LoginAttempt::create([
            'user_id' => $user2->id,
            'email' => 'user@example.com',
            'ip_address' => '192.168.2.100',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
            'success' => true,
            'created_at' => now()->subMinutes(30),
        ]);

        $this->command->info('✅ Données de test LoginAttempt créées avec succès !');
        $this->command->line('');
        $this->command->line('📊 Données générées :');
        $this->command->line('  🔴 Force brute : 8 tentatives échouées depuis 192.168.1.100');
        $this->command->line('  🔴 Ciblage multi-compte : 5 emails attaqués depuis 203.0.113.50');
        $this->command->line('  🟢 Connexions légitimes : 5 connexions réussies pour admin');
        $this->command->line('  🟢 Connexions légitimes : 3 connexions réussies pour user');
        $this->command->line('  🟡 Historique échouées→réussie : 2 tentatives échouées + 1 réussie');
    }
}
