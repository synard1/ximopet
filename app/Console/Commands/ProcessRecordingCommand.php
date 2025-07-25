<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Livestock;
use App\Models\Recording;
use App\Models\SupplyUsage;
use App\Services\Recording\SimpleRecordingDataAggregatorService;
use App\Services\Recording\DTOs\ServiceResult;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Process Recording Command
 * 
 * Command untuk memproses recording data menggunakan RecordingDataAggregatorService.
 * Dapat memproses recording meskipun hanya ada data supply usage.
 * 
 * @version 1.0
 * @since 2025-01-25
 */
class ProcessRecordingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recording:process 
                            {--livestock-id= : ID livestock yang akan diproses}
                            {--date= : Tanggal recording (format: Y-m-d, default: hari ini)}
                            {--force : Force processing meskipun sudah ada recording}
                            {--dry-run : Simulasi tanpa menyimpan ke database}
                            {--details : Output detail proses}
                            {--all : Proses semua livestock yang aktif}
                            {--batch-size=50 : Jumlah livestock per batch untuk --all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recording data using RecordingDataAggregatorService';

    /**
     * RecordingDataAggregatorService instance
     */
    private SimpleRecordingDataAggregatorService $aggregatorService;

    /**
     * Statistics for processing
     */
    private array $stats = [
        'total_processed' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting Recording Processing...');
        $this->info('📅 Date: ' . ($this->option('date') ?: 'today'));
        $this->info('🔧 Force: ' . ($this->option('force') ? 'Yes' : 'No'));
        $this->info('🧪 Dry Run: ' . ($this->option('dry-run') ? 'Yes' : 'No'));
        $this->info('📊 Verbose: ' . ($this->option('details') ? 'Yes' : 'No'));
        $this->info('');

        // Initialize service
        $this->aggregatorService = new SimpleRecordingDataAggregatorService([
            'enable_status_filtering' => true,
            'enable_data_validation' => true,
            'enable_performance_monitoring' => true
        ]);

        try {
            if ($this->option('all')) {
                $this->processAllLivestock();
            } else {
                $this->processSingleLivestock();
            }

            $this->displayResults();
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Fatal error: ' . $e->getMessage());
            Log::error('ProcessRecordingCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Process single livestock
     */
    private function processSingleLivestock(): void
    {
        $livestockId = $this->option('livestock-id');
        $date = $this->option('date') ?: now()->format('Y-m-d');

        if (!$livestockId) {
            $this->error('❌ Livestock ID is required when not using --all option');
            return;
        }

        $this->info("🎯 Processing livestock: {$livestockId}");
        $this->info("📅 Date: {$date}");
        $this->info('');

        $livestock = Livestock::find($livestockId);
        if (!$livestock) {
            $this->error("❌ Livestock with ID {$livestockId} not found");
            return;
        }

        $this->processLivestockRecording($livestock, $date);
    }

    /**
     * Process all active livestock
     */
    private function processAllLivestock(): void
    {
        $date = $this->option('date') ?: now()->format('Y-m-d');
        $batchSize = (int) $this->option('batch-size');

        $this->info("🎯 Processing all active livestock");
        $this->info("📅 Date: {$date}");
        $this->info("📦 Batch size: {$batchSize}");
        $this->info('');

        $query = Livestock::where('status', 'active')
            ->orWhereNull('status');

        $totalLivestock = $query->count();
        $this->info("📊 Total livestock to process: {$totalLivestock}");
        $this->info('');

        if ($totalLivestock === 0) {
            $this->warn('⚠️ No active livestock found');
            return;
        }

        $bar = $this->output->createProgressBar($totalLivestock);
        $bar->start();

        $query->chunk($batchSize, function ($livestockBatch) use ($date, $bar) {
            foreach ($livestockBatch as $livestock) {
                $this->processLivestockRecording($livestock, $date, false);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info('');
        $this->info('');
    }

    /**
     * Process recording for a single livestock
     */
    private function processLivestockRecording(Livestock $livestock, string $date, bool $showDetails = true): void
    {
        $this->stats['total_processed']++;

        try {
            // Check if recording already exists
            $existingRecording = Recording::where('livestock_id', $livestock->id)
                ->whereDate('tanggal', $date)
                ->first();

            if ($existingRecording && !$this->option('force')) {
                if ($showDetails) {
                    $this->warn("⚠️ Recording already exists for {$livestock->name} on {$date}");
                }
                $this->stats['skipped']++;
                return;
            }

            // Check if there's any data to process
            $hasData = $this->checkDataAvailability($livestock->id, $date);
            if (!$hasData) {
                if ($showDetails) {
                    $this->warn("⚠️ No data available for {$livestock->name} on {$date}");
                }
                $this->stats['skipped']++;
                return;
            }

            if ($showDetails) {
                $this->info("🔄 Processing {$livestock->name}...");
            }

            // Aggregate data using service
            $result = $this->aggregateData($livestock, $date);

            if ($result->isSuccess()) {
                $payload = $result->getData();

                if ($showDetails) {
                    $this->displayAggregationDetails($payload);
                }

                // Save recording if not dry run
                if (!$this->option('dry-run')) {
                    $this->saveRecording($livestock, $date, $payload);
                }

                if ($showDetails) {
                    $this->info("✅ Successfully processed {$livestock->name}");
                }
                $this->stats['success']++;
            } else {
                $error = $result->getMessage();
                if ($showDetails) {
                    $this->error("❌ Failed to process {$livestock->name}: {$error}");
                }
                $this->stats['failed']++;
                $this->stats['errors'][] = [
                    'livestock_id' => $livestock->id,
                    'livestock_name' => $livestock->name,
                    'date' => $date,
                    'error' => $error
                ];
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            if ($showDetails) {
                $this->error("❌ Exception processing {$livestock->name}: {$error}");
            }
            $this->stats['failed']++;
            $this->stats['errors'][] = [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'date' => $date,
                'error' => $error
            ];

            Log::error('ProcessRecordingCommand livestock processing failed', [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'date' => $date,
                'error' => $error,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if there's any data available for processing
     */
    private function checkDataAvailability(string $livestockId, string $date): bool
    {
        // Check for supply usage
        $hasSupplyUsage = SupplyUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', $date)
            ->whereIn('status', ['pending', 'in_process', 'completed', 'partially_used'])
            ->exists();

        if ($hasSupplyUsage) {
            return true;
        }

        // Check for feed usage
        $hasFeedUsage = \App\Models\FeedUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', $date)
            ->exists();

        if ($hasFeedUsage) {
            return true;
        }

        // Check for depletion
        $hasDepletion = \App\Models\LivestockDepletion::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $date)
            ->exists();

        if ($hasDepletion) {
            return true;
        }

        // Check for existing recording (for force mode)
        if ($this->option('force')) {
            $hasRecording = Recording::where('livestock_id', $livestockId)
                ->whereDate('tanggal', $date)
                ->exists();

            if ($hasRecording) {
                return true;
            }
        }

        return false;
    }

    /**
     * Aggregate data using RecordingDataAggregatorService
     */
    private function aggregateData(Livestock $livestock, string $date): ServiceResult
    {
        $options = [
            'include_calculated_metrics' => true,
            'force_recalculation' => $this->option('force'),
            'source' => 'artisan_command'
        ];

        return $this->aggregatorService->aggregateData($livestock->id, $date, $options);
    }

    /**
     * Save recording to database
     */
    private function saveRecording(Livestock $livestock, string $date, array $payload): void
    {
        // Calculate age in days
        $age = 0;
        if ($livestock->start_date) {
            $startDate = Carbon::parse($livestock->start_date);
            $recordDate = Carbon::parse($date);
            $age = $startDate->diffInDays($recordDate, false);
        }

        $recordingData = [
            'livestock_id' => $livestock->id,
            'tanggal' => $date,
            'age' => $age,
            'stock_awal' => $livestock->initial_quantity ?? 0,
            'stock_akhir' => $livestock->initial_quantity ?? 0,
            'total_deplesi' => 0,
            'total_penjualan' => 0,
            'berat_semalam' => 0,
            'berat_hari_ini' => 0,
            'kenaikan_berat' => 0,
            'payload' => $payload,
            'created_by' => 'artisan_command',
            'updated_by' => 'artisan_command'
        ];

        // Update or create recording
        Recording::updateOrCreate(
            [
                'livestock_id' => $livestock->id,
                'tanggal' => $date
            ],
            $recordingData
        );
    }

    /**
     * Display aggregation details
     */
    private function displayAggregationDetails(array $payload): void
    {
        if (!$this->option('details')) {
            return;
        }

        $this->info('📊 Aggregation Details:');
        $this->info('   - Schema Version: ' . $payload['schema']['version']);
        $this->info('   - Data Sources: ' . implode(', ', $payload['metadata']['data_sources']));

        // Feed usage details
        if (!empty($payload['consumption']['feed']['items'])) {
            $this->info('   - Feed Items: ' . count($payload['consumption']['feed']['items']));
            $this->info('   - Feed Cost: ' . number_format($payload['consumption']['feed']['total_cost']));
        }

        // Supply usage details
        if (!empty($payload['consumption']['supply']['items'])) {
            $this->info('   - Supply Items: ' . count($payload['consumption']['supply']['items']));
            $this->info('   - Supply Cost: ' . number_format($payload['consumption']['supply']['total_cost']));
        }

        // Depletion details
        if (isset($payload['production']['depletion'])) {
            $depletion = $payload['production']['depletion'];
            $this->info('   - Mortality: ' . $depletion['mortality']);
            $this->info('   - Culling: ' . $depletion['culling']);
            $this->info('   - Total Depletion: ' . $depletion['total']);
        }

        // Performance metrics
        if (isset($payload['performance'])) {
            $performance = $payload['performance'];
            $this->info('   - IP: ' . ($performance['ip'] ?? 'N/A'));
            $this->info('   - FCR: ' . ($performance['fcr'] ?? 'N/A'));
            $this->info('   - Liveability: ' . ($performance['liveability'] ?? 'N/A'));
        }

        $this->info('');
    }

    /**
     * Display final results
     */
    private function displayResults(): void
    {
        $this->info('');
        $this->info('📈 Processing Results:');
        $this->info('   - Total Processed: ' . $this->stats['total_processed']);
        $this->info('   - Success: ' . $this->stats['success']);
        $this->info('   - Failed: ' . $this->stats['failed']);
        $this->info('   - Skipped: ' . $this->stats['skipped']);

        if (!empty($this->stats['errors'])) {
            $this->error('');
            $this->error('❌ Errors:');
            foreach ($this->stats['errors'] as $error) {
                $this->error("   - {$error['livestock_name']} ({$error['date']}): {$error['error']}");
            }
        }

        if ($this->option('dry-run')) {
            $this->warn('');
            $this->warn('🧪 DRY RUN MODE: No data was saved to database');
        }

        $this->info('');
        $this->info('✅ Recording processing completed!');
    }
}
