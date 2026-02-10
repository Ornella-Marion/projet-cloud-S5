<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Road;

class RoadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Routes d'Antananarivo, Madagascar
        
        Road::create([
            'id' => 1,
            'designation' => 'Avenue de l\'Indépendance - Centre-ville',
            'longitude' => 47.5227,
            'latitude' => -18.8788,
            'area' => 2.5,
        ]);

        Road::create([
            'id' => 2,
            'designation' => 'Route d\'Ilafy - Nord',
            'longitude' => 47.5100,
            'latitude' => -18.8500,
            'area' => 5.8,
        ]);

        Road::create([
            'id' => 3,
            'designation' => 'Boulevard de la Liberté - Antsirabe',
            'longitude' => 47.5350,
            'latitude' => -18.8600,
            'area' => 3.2,
        ]);

        Road::create([
            'id' => 4,
            'designation' => 'Route de Vangaindrano - Sud',
            'longitude' => 47.5400,
            'latitude' => -18.9000,
            'area' => 7.0,
        ]);

        Road::create([
            'id' => 5,
            'designation' => 'Route de Sambava - Est',
            'longitude' => 47.5600,
            'latitude' => -18.8700,
            'area' => 6.3,
        ]);

        Road::create([
            'id' => 6,
            'designation' => 'Rue Joffre - Andravoahangy',
            'longitude' => 47.5150,
            'latitude' => -18.8900,
            'area' => 2.1,
        ]);

        Road::create([
            'id' => 7,
            'designation' => 'Avenue Maniela - Analakely',
            'longitude' => 47.5280,
            'latitude' => -18.8750,
            'area' => 1.8,
        ]);

        Road::create([
            'id' => 8,
            'designation' => 'Route de Mahajanga - Ouest',
            'longitude' => 47.5000,
            'latitude' => -18.8800,
            'area' => 8.5,
        ]);

        Road::create([
            'id' => 9,
            'designation' => 'Boulevard Metz - Behoririka',
            'longitude' => 47.5320,
            'latitude' => -18.8850,
            'area' => 3.7,
        ]);

        Road::create([
            'id' => 10,
            'designation' => 'Route de Toliara - Anosy',
            'longitude' => 47.5450,
            'latitude' => -18.8950,
            'area' => 4.2,
        ]);
    }
}

