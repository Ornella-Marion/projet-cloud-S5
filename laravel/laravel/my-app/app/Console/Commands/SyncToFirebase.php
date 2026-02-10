<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncToFirebase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:sync-to
        {--destination=firestore : Destination Firebase (firestore ou realtime_db)}
        {--collection= : Collection Firestore (requis si destination=firestore)}
        {--document-id= : Document ID Firestore (requis si destination=firestore)}
        {--path= : Path Realtime DB (requis si destination=realtime_db)}
        {--data= : Données JSON à synchroniser}
        {--file= : Fichier JSON contenant les données}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser des données manuellement vers Firebase (Firestore ou Realtime Database)';

    /**
     * Service Firebase
     *
     * @var FirebaseService
     */
    private FirebaseService $firebase;

    /**
     * Créer une nouvelle instance de commande
     *
     * @param FirebaseService $firebase
     */
    public function __construct(FirebaseService $firebase)
    {
        parent::__construct();
        $this->firebase = $firebase;
    }

    /**
     * Exécuter la commande
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🚀 Synchronisation manuelle Firebase');
        $this->line('');

        // Récupérer les paramètres
        $destination = $this->option('destination');
        $data = $this->getData();

        if (empty($data)) {
            $this->error('❌ Aucune donnée à synchroniser');
            return 1;
        }

        // Valider la destination
        if (!in_array($destination, ['firestore', 'realtime_db'])) {
            $this->error("❌ Destination invalide: {$destination}");
            $this->line('   Utilisez: firestore ou realtime_db');
            return 1;
        }

        // Construire les options
        $options = [];

        if ($destination === 'firestore') {
            $collection = $this->option('collection');
            $documentId = $this->option('document-id');

            if (empty($collection) || empty($documentId)) {
                $this->error('❌ Pour Firestore, les options --collection et --document-id sont requises');
                return 1;
            }

            $options = [
                'collection' => $collection,
                'document_id' => $documentId,
            ];

            $this->info("📦 Firestore:");
            $this->line("   Collection: {$collection}");
            $this->line("   Document ID: {$documentId}");
        } else {
            $path = $this->option('path');

            if (empty($path)) {
                $this->error('❌ Pour Realtime Database, l\'option --path est requise');
                return 1;
            }

            $options = ['path' => $path];

            $this->info("📦 Realtime Database:");
            $this->line("   Path: {$path}");
        }

        $this->line('');
        $this->info("📊 Données à synchroniser:");
        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line('');

        // Effectuer la synchronisation
        if (!$this->confirm('Confirmer la synchronisation?')) {
            $this->info('❌ Synchronisation annulée');
            return 0;
        }

        $startTime = microtime(true);

        try {
            $result = $this->firebase->syncToFirebase($destination, $data, $options);

            $elapsedTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                $this->newLine();
                $this->info('✅ Synchronisation réussie!');
                $this->line("   Message: {$result['message']}");
                $this->line("   Temps: {$elapsedTime}ms");

                if (!empty($result['data_synced'])) {
                    $this->line('   Détails:');
                    foreach ($result['data_synced'] as $key => $value) {
                        $this->line("     - {$key}: {$value}");
                    }
                }

                Log::info('Firebase: Manual sync completed', [
                    'destination' => $destination,
                    'result' => $result,
                ]);

                return 0;
            } else {
                $this->newLine();
                $this->error('❌ Synchronisation échouée');
                $this->line("   Message: {$result['message']}");
                if ($result['error']) {
                    $this->line("   Erreur: {$result['error']}");
                }

                Log::error('Firebase: Manual sync failed', [
                    'destination' => $destination,
                    'error' => $result['error'],
                ]);

                return 1;
            }
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Erreur lors de la synchronisation');
            $this->line("   {$e->getMessage()}");

            Log::error('Firebase: Manual sync error', [
                'exception' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    /**
     * Récupérer les données à synchroniser
     *
     * @return array
     */
    private function getData(): array
    {
        $file = $this->option('file');
        $dataString = $this->option('data');

        // Récupérer depuis fichier JSON
        if (!empty($file)) {
            if (!file_exists($file)) {
                $this->error("❌ Fichier non trouvé: {$file}");
                return [];
            }

            $fileContent = file_get_contents($file);
            $data = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('❌ Fichier JSON invalide: ' . json_last_error_msg());
                return [];
            }

            return $data;
        }

        // Récupérer depuis l'option --data
        if (!empty($dataString)) {
            $data = json_decode($dataString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('❌ JSON invalide: ' . json_last_error_msg());
                return [];
            }

            return $data;
        }

        // Demander interactivement à l'utilisateur
        $this->info('📝 Entrez les données JSON (ou tapez "done" sur une nouvelle ligne pour terminer):');
        $lines = [];
        while (true) {
            $line = trim($this->ask('> '));
            if ($line === 'done') {
                break;
            }
            $lines[] = $line;
        }

        if (empty($lines)) {
            return [];
        }

        $jsonString = implode('', $lines);
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('❌ JSON invalide: ' . json_last_error_msg());
            return [];
        }

        return $data;
    }
}
