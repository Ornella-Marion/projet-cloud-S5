<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use Illuminate\Console\Command;

class TestLoginAttemptAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:login-analytics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste toutes les analyses possibles des tentatives de connexion';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 === TEST DES ANALYSES DE TENTATIVES DE CONNEXION === 🔍');
        $this->line('');

        // Test 1 : Détection de Force Brute
        $this->testForceBruteDetection();
        $this->line('');

        // Test 2 : Profiler un attaquant (IP)
        $this->testIpProfiler();
        $this->line('');

        // Test 3 : Analyser un email ciblé
        $this->testEmailAnalysis();
        $this->line('');

        // Test 4 : Historique complet
        $this->testCompleteHistory();
        $this->line('');

        // Test 5 : Statistiques globales
        $this->testGlobalStatistics();
        $this->line('');

        $this->info('✅ Tous les tests sont terminés !');
        return 0;
    }

    /**
     * Test 1 : Détecte les tentatives de force brute
     * À quoi ça sert : Identifier les attaquants actifs
     */
    private function testForceBruteDetection(): void
    {
        $this->info('📌 TEST 1 : DÉTECTION DE FORCE BRUTE');
        $this->line('À quoi ça sert : Identifier automatiquement les IPs/emails suspects');
        $this->line('');

        $suspicious = LoginAttempt::detectSuspiciousActivity(15, 5);

        if (empty($suspicious) || (empty($suspicious['ips'] ?? []) && empty($suspicious['emails'] ?? []))) {
            $this->warn('⚠️  Aucune activité suspecte détectée');
            return;
        }

        if (!empty($suspicious['ips'] ?? [])) {
            $this->line('🚨 <fg=red>IPS SUSPECTES (Force Brute)</>');
            foreach ($suspicious['ips'] as $ip) {
                $this->line(sprintf(
                    '  IP: %s | Tentatives: %d | Échouées 15min: %d | Emails ciblés: %d',
                    $ip['ip_address'],
                    $ip['total_attempts'],
                    $ip['failed_attempts_15min'],
                    $ip['unique_emails_targeted'] ?? 0
                ));
                $this->line(sprintf('    → Dernière tentative: %s (%s)', 
                    $ip['last_attempt_at'], 
                    $ip['last_attempt_success'] ? '✅' : '❌'
                ));
            }
            $this->line('');
        }

        if (!empty($suspicious['emails'] ?? [])) {
            $this->line('🎯 <fg=yellow>EMAILS CIBLÉS (Attaque ciblée)</>');
            foreach ($suspicious['emails'] as $email) {
                $this->line(sprintf(
                    '  Email: %s | Tentatives: %d | Échouées 15min: %d',
                    $email['email'],
                    $email['total_attempts'],
                    $email['failed_attempts_15min']
                ));
                $this->line(sprintf('    → Dernière tentative: %s depuis %s', 
                    $email['last_attempt_at'], 
                    $email['last_attempt_ip']
                ));
            }
            $this->line('');
        }

        $this->info('💡 Utilisation : Déclencher une alerte automatique, bloquer l\'IP, alerter l\'admin');
    }

    /**
     * Test 2 : Profile un attaquant par son IP
     * À quoi ça sert : Comprendre le comportement d'un attaquant
     */
    private function testIpProfiler(): void
    {
        $this->info('📌 TEST 2 : PROFILER UN ATTAQUANT (PAR IP)');
        $this->line('À quoi ça sert : Comprendre les patterns d\'attaque d\'une IP');
        $this->line('');

        // Tester avec l'IP attaquante connue
        $ipAddress = '192.168.1.100';
        $this->line("Analyse de l'IP: <fg=red>{$ipAddress}</>");
        $this->line('');

        $stats = LoginAttempt::getIpStatistics($ipAddress);

        $this->line('📊 <fg=cyan>STATISTIQUES DE L\'IP</>');
        $this->line(sprintf('  Total tentatives: %d', $stats['total_attempts']));
        $this->line(sprintf('  Échouées (15 min): %d', $stats['failed_attempts_15min']));
        $this->line(sprintf('  Emails ciblés: %d', $stats['unique_emails_targeted']));
        $this->line(sprintf('  Dernière tentative: %s', $stats['last_attempt_at']));
        $this->line(sprintf('  Email cible: %s', $stats['last_attempt_email']));
        $this->line(sprintf('  Dernier résultat: %s', $stats['last_attempt_success'] ? '✅ Réussi' : '❌ Échoué'));
        $this->line('');

        $this->info('💡 Utilisation : Bloquer l\'IP, vérifier si autres tentatives ailleurs, signaler à ISP');
    }

    /**
     * Test 3 : Analyser un email ciblé
     * À quoi ça sert : Protéger un compte compromis
     */
    private function testEmailAnalysis(): void
    {
        $this->info('📌 TEST 3 : ANALYSER UN EMAIL CIBLÉ');
        $this->line('À quoi ça sert : Protéger un compte qui subit une attaque');
        $this->line('');

        $email = 'admin@example.com';
        $this->line("Analyse de l'email: <fg=yellow>{$email}</>");
        $this->line('');

        $stats = LoginAttempt::getEmailStatistics($email);

        $this->line('📊 <fg=cyan>STATISTIQUES DE L\'EMAIL</>');
        $this->line(sprintf('  Total tentatives: %d', $stats['total_attempts']));
        $this->line(sprintf('  Échouées (15 min): %d', $stats['failed_attempts_15min']));
        $this->line(sprintf('  Réussies (15 min): %d', $stats['successful_attempts_15min'] ?? 0));
        $this->line(sprintf('  Dernière tentative: %s', $stats['last_attempt_at']));
        $this->line(sprintf('  Depuis IP: %s', $stats['last_attempt_ip']));
        $this->line(sprintf('  Résultat: %s', $stats['last_attempt_success'] ? '✅ Réussi' : '❌ Échoué'));
        $this->line('');

        // Obtenir l'historique détaillé
        $history = LoginAttempt::getRecentFailedAttempts($email, 15);
        if ($history->count() > 0) {
            $this->line('🔍 <fg=cyan>DERNIÈRES TENTATIVES ÉCHOUÉES (15 MIN)</>');
            foreach ($history->take(5) as $attempt) {
                $this->line(sprintf(
                    '  %s | IP: %s | Raison: %s',
                    $attempt->created_at->format('H:i:s'),
                    $attempt->ip_address,
                    $attempt->failure_reason
                ));
            }
            $this->line('');
        }

        $this->info('💡 Utilisation : Envoyer alerte utilisateur, forcer reset password, augmenter sécurité');
    }

    /**
     * Test 4 : Historique complet d'un utilisateur
     * À quoi ça sert : Audit de sécurité, forensics
     */
    private function testCompleteHistory(): void
    {
        $this->info('📌 TEST 4 : HISTORIQUE COMPLET');
        $this->line('À quoi ça sert : Forensics, audit de sécurité, analyse post-incident');
        $this->line('');

        $email = 'user@example.com';
        $this->line("Historique complet de: <fg=yellow>{$email}</>");
        $this->line('');

        $history = LoginAttempt::forEmail($email)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        if ($history->count() === 0) {
            $this->warn('⚠️  Aucun historique trouvé');
            return;
        }

        $this->line('📋 <fg=cyan>DERNIÈRES 10 TENTATIVES</>');
        $this->line('');
        
        foreach ($history as $attempt) {
            $status = $attempt->success ? '✅ <fg=green>SUCCÈS</>' : '❌ <fg=red>ÉCHOUÉ</>';
            $this->line(sprintf(
                '%s | %s | IP: %s | Raison: %s',
                $attempt->created_at->format('Y-m-d H:i:s'),
                $status,
                $attempt->ip_address,
                $attempt->failure_reason ?? '-'
            ));
        }
        $this->line('');

        $this->info('💡 Utilisation : Forensics après incident, vérifier activité suspecte, restaurer sécurité');
    }

    /**
     * Test 5 : Statistiques globales
     * À quoi ça sert : Monitoring général de l'application
     */
    private function testGlobalStatistics(): void
    {
        $this->info('📌 TEST 5 : STATISTIQUES GLOBALES');
        $this->line('À quoi ça sert : Dashboard de santé générale, alertes de sécurité');
        $this->line('');

        $totalAttempts = LoginAttempt::count();
        $failedAttempts = LoginAttempt::failed()->count();
        $successfulAttempts = LoginAttempt::successful()->count();
        
        $failed24h = LoginAttempt::failed()
            ->withinMinutes(24 * 60)
            ->count();
        
        $successful24h = LoginAttempt::successful()
            ->withinMinutes(24 * 60)
            ->count();

        $this->line('📊 <fg=cyan>STATISTIQUES GLOBALES</>');
        $this->line(sprintf('  Total tentatives: %d', $totalAttempts));
        $this->line(sprintf('  Échouées: %d', $failedAttempts));
        $this->line(sprintf('  Réussies: %d', $successfulAttempts));
        $this->line('');
        
        $successRate = $totalAttempts > 0 
            ? round(($successfulAttempts / $totalAttempts) * 100, 2)
            : 0;
        
        $this->line('📈 <fg=cyan>STATISTIQUES 24 DERNIÈRES HEURES</>');
        $this->line(sprintf('  Échouées: %d', $failed24h));
        $this->line(sprintf('  Réussies: %d', $successful24h));
        $this->line(sprintf('  Taux de réussite global: %s%%', $successRate));
        $this->line('');

        // Analyse de tendance
        if ($failed24h > 10) {
            $this->warn('⚠️  <fg=red>ALERTE : Trop de tentatives échouées en 24h !</>');
        }

        $this->info('💡 Utilisation : Dashboard admin, alertes email, GraphQL APIs, webhooks');
    }
}
