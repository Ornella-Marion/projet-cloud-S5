<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncFromFirebase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:sync-from
        {--source=firestore : Source Firebase (firestore ou realtime_db)}
        {--collection= : Collection Firestore (requis si source=firestore)}
        {--document-id= : Document ID Firestore (requis si source=firestore)}
        {--path= : Path Realtime DB (requis si source=realtime_db)}
        {--batch : Mode batch pour récupérer plusieurs documents}
        {--items= : Fichier JSON contenant les items à récupérer (mode batch)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Récupérer des données manuellement depuis Firebase (Firestore ou Realtime Database)';

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
        $this->info('🔍 Récupération manuelle depuis Firebase');
        $this->line('');

        $source = $this->option('source');
        $isBatch = $this->option('batch');

        // Valider la source
        if (!in_array($source, ['firestore', 'realtime_db'])) {
            $this->error("❌ Source invalide: {$source}");
            $this->line('   Utilisez: firestore ou realtime_db');
            return 1;
        }

        // Mode batch
        if ($isBatch) {
            return $this->handleBatch($source);
        }

        // Mode simple
        return $this->handleSingle($source);
    }

    /**
     * Gérer la récupération simple
     *
     * @param string $source
     * @return int
     */
    private function handleSingle(string $source): int
    {
        $options = [];

        if ($source === 'firestore') {
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

            $this->info("📚 Firestore:");
            $this->line("   Collection: {$collection}");
            $this->line("   Document ID: {$documentId}");
        } else {
            $path = $this->option('path');

            if (empty($path)) {
                $this->error('❌ Pour Realtime Database, l\'option --path est requise');
                return 1;
            }

            $options = ['path' => $path];

            $this->info("🔗 Realtime Database:");
            $this->line("   Path: {$path}");
        }

        $this->line('');
        $this->info('🔄 Récupération des données...');
        $this->line('');

        $startTime = microtime(true);

        try {
            $result = $this->firebase->syncFromFirebase($source, $options);

            $elapsedTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success'] && $result['data'] !== null) {
                $this->newLine();
                $this->info('✅ Données récupérées avec succès!');
                $this->line("   Temps: {$elapsedTime}ms");
                $this->line("   Nombre de champs: {$result['data_count']}");
                $this->line('');
                $this->line('📊 Contenu:');
                $this->line(json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                Log::info('Firebase: Manual retrieval completed', [
                    'source' => $source,
                    'data_count' => $result['data_count'],
                ]);

                return 0;
            } else {
                $this->newLine();
                $this->warn('⚠️  ' . $result['message']);
                if ($result['error']) {
                    $this->line("   Erreur: {$result['error']}");
                }

                Log::warning('Firebase: Manual retrieval - no data found', [
                    'source' => $source,
                ]);

                return 1;
            }
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Erreur lors de la récupération');
            $this->line("   {$e->getMessage()}");

            Log::error('Firebase: Manual retrieval error', [
                'exception' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    /**
     * Gérer la récupération batch
     *
     * @param string $source
     * @return int
     */
    private function handleBatch(string $source): int
    {
        $itemsFile = $this->option('items');

        if (empty($itemsFile)) {
            $this->error('❌ Mode batch: l\'option --items est requise');
            return 1;
        }

        if (!file_exists($itemsFile)) {
            $this->error("❌ Fichier non trouvé: {$itemsFile}");
            return 1;
        }

        $fileContent = file_get_contents($itemsFile);
        $items = json_decode($fileContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('❌ Fichier JSON invalide: ' . json_last_error_msg());
            return 1;
        }

        if (!is_array($items) || empty($items)) {
            $this->error('❌ Le fichier JSON doit contenir un tableau d\'items');
            return 1;
        }

        $this->info('📚 Mode Batch:');
        $this->line("   Source: {$source}");
        $this->line("   Items à récupérer: " . count($items));
        $this->line('');

        $startTime = microtime(true);

        try {
            $result = $this->firebase->syncBatchFromFirebase($source, $items);

            $elapsedTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->newLine();
            $this->info('✅ Récupération batch complétée!');
            $this->line("   Temps: {$elapsedTime}ms");
            $this->line("   Total: {$result['total_items']}");
            $this->line("   Récupérés: {$result['retrieved']}");
            $this->line("   Non trouvés: {$result['not_found']}");
            $this->line("   Erreurs: {$result['failed']}");

            if (!empty($result['items'])) {
                $this->line('');
                $this->line('📊 Détails:');
                $this->line(json_encode($result['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            Log::info('Firebase: Manual batch retrieval completed', [
                'source' => $source,
                'total' => $result['total_items'],
                'retrieved' => $result['retrieved'],
                'not_found' => $result['not_found'],
                'failed' => $result['failed'],
            ]);

            return $result['failed'] === 0 ? 0 : 1;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Erreur lors de la récupération batch');
            $this->line("   {$e->getMessage()}");

            Log::error('Firebase: Manual batch retrieval error', [
                'exception' => $e->getMessage(),
            ]);

            return 1;
        }
    }
}
