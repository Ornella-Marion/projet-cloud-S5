<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;
use Carbon\Carbon;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Signalements pour différentes routes et utilisateurs
        
        // Route 1 - Signalement par user (id=2)
        Report::create([
            'user_id' => 2, // jean@test.com
            'road_id' => 1,
            'target_type' => 'road',
            'report_date' => Carbon::now()->subDays(5),
            'reason' => 'Nids de poule importants sur la voie principale, dangereux pour les véhicules.',
        ]);

        // Route 1 - Autre signalement
        Report::create([
            'user_id' => 3, // marie@test.com
            'road_id' => 1,
            'target_type' => 'signalisation',
            'report_date' => Carbon::now()->subDays(3),
            'reason' => 'Panneau stop manquant à l\'intersection.',
        ]);

        // Route 2 - Signalement éclairage
        Report::create([
            'user_id' => 2,
            'road_id' => 2,
            'target_type' => 'eclairage',
            'report_date' => Carbon::now()->subDays(10),
            'reason' => 'Lampadaires non fonctionnels sur 200m, zone très sombre la nuit.',
        ]);

        // Route 4 - Signalement route
        Report::create([
            'user_id' => 4, // velo@test.com
            'road_id' => 4,
            'target_type' => 'road',
            'report_date' => Carbon::now()->subDays(2),
            'reason' => 'Affaissement de la chaussée côté gauche, risque d\'accident.',
        ]);

        // Route 5 - Signalement autre
        Report::create([
            'user_id' => 3,
            'road_id' => 5,
            'target_type' => 'autre',
            'report_date' => Carbon::now()->subDays(7),
            'reason' => 'Déchets abandonnés sur le bord de la route, obstruction partielle.',
        ]);

        // Route 6 - Signalement signalisation
        Report::create([
            'user_id' => 2,
            'road_id' => 6,
            'target_type' => 'signalisation',
            'report_date' => Carbon::now()->subDays(1),
            'reason' => 'Marquage au sol effacé, lignes de séparation invisibles.',
        ]);

        // Route 7 - Signalement éclairage
        Report::create([
            'user_id' => 4,
            'road_id' => 7,
            'target_type' => 'eclairage',
            'report_date' => Carbon::now()->subDays(4),
            'reason' => 'Câbles électriques apparents sur un poteau, danger.',
        ]);

        // Route 8 - Signalement route
        Report::create([
            'user_id' => 3,
            'road_id' => 8,
            'target_type' => 'road',
            'report_date' => Carbon::now(),
            'reason' => 'Travaux en cours mais pas de signalisation temporaire.',
        ]);

        // Signalement sans route spécifique (position GPS)
        Report::create([
            'user_id' => 2,
            'road_id' => null,
            'target_type' => 'autre',
            'report_date' => Carbon::now()->subDays(6),
            'reason' => 'Inondation récurrente à cette position, drainage défaillant. GPS: -18.8750, 47.5200',
        ]);

        // Signalement route 3
        Report::create([
            'user_id' => 4,
            'road_id' => 3,
            'target_type' => 'road',
            'report_date' => Carbon::now()->subDays(15),
            'reason' => 'Route très glissante par temps de pluie, revêtement usé.',
        ]);
    }
}
