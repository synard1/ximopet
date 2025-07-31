<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

use App\Models\LivestockBatch;
use App\Models\RecordingSale;
use App\Models\RecordingSaleItem;
use App\Services\Recording\VirtualQuantityCalculationService;

use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;

class MigrateRealToVirtualQuantityCommand extends Command
{
    protected $signature = 'migrate:real-to-virtual 
                            {--livestock-id= : Specific livestock ID to migrate}
                            {--date= : Specific date to migrate (Y-m-d format)}
                            {--dry-run : Show what would be migrated without making changes}
                            {--force : Force migration even if virtual data exists}
                            {--batch-size=100 : Number of records to process per batch}';

    protected $description = 'Migrate real quantity calculations to virtual quantity calculations';

    protected VirtualQuantityCalculationService $virtualService;

    public function __construct(VirtualQuantityCalculationService $virtualService)
    {
        parent::__construct();
        $this->virtualService = $virtualService;
    }

    public function handle()
    {
        $livestockId = $this->option('livestock-id');
        $date = $this->option('date');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $batchSize = (int) $this->option('batch-size');

        $this->info("🔄 Migrating Real to Virtual Quantity Calculation");
        $this->info("================================================");
        $this->info("Livestock ID: " . ($livestockId ?: 'ALL'));
        $this->info("Date: " . ($date ?: 'ALL'));
        $this->info("Dry Run: " . ($dryRun ? 'YES' : 'NO'));
        $this->info("Force: " . ($force ? 'YES' : 'NO'));
        $this->info("Batch Size: {$batchSize}");
        $this->info("");

        // Validate configuration
        $this->validateConfiguration();

        // Get records to migrate
        $recordsToMigrate = $this->getRecordsToMigrate($livestockId, $date, $force);

        if ($recordsToMigrate->isEmpty()) {
            $this->warn("⚠️ No records found to migrate");
            return 0;
        }

        $this->info("📊 Found {$recordsToMigrate->count()} records to migrate");
        $this->info("");

        // Process migration
        $migrationResult = $this->processMigration($recordsToMigrate, $dryRun, $batchSize);

        // Show summary
        $this->showMigrationSummary($migrationResult);

        return 0;
    }

    /**
     * Validate that virtual calculation is enabled
     */
    private function validateConfiguration(): void
    {
        $salesConfig = \App\Config\CompanyConfig::getSalesConfig();
        $quantityCalculation = $salesConfig['quantity_calculation'] ?? [];
        $isVirtualMode = ($quantityCalculation['mode'] ?? 'real') === 'virtual';

        if (!$isVirtualMode) {
            $this->error("❌ Virtual quantity calculation is not enabled in configuration");
            $this->error("Please enable virtual mode in CompanyConfig::getDefaultSalesConfig()");
            exit(1);
        }

        $this->info("✅ Virtual quantity calculation is enabled");
    }

    /**
     * Get records that need migration
     */
    private function getRecordsToMigrate(?string $livestockId, ?string $date, bool $force)
    {
        $query = RecordingSale::with(['items.batch'])
            ->where('is_header', true)
            ->where('status', 'draft');

        if ($livestockId) {
            $query->where('livestock_id', $livestockId);
        }

        if ($date) {
            $query->where('date', $date);
        }

        $records = $query->get();

        // Filter records that need migration
        $recordsToMigrate = $records->filter(function ($record) use ($force) {
            // Check if virtual data already exists
            $hasVirtualData = $this->checkVirtualDataExists($record->livestock_id, $record->date);

            if ($hasVirtualData && !$force) {
                return false; // Skip if virtual data exists and not forcing
            }

            // Check if this record has real stock impact
            $hasRealStockImpact = $this->checkRealStockImpact($record);

            return $hasRealStockImpact;
        });

        return $recordsToMigrate;
    }

    /**
     * Check if virtual data already exists for a livestock and date
     */
    private function checkVirtualDataExists(string $livestockId, string $date): bool
    {
        $batches = LivestockBatch::where('livestock_id', $livestockId)
            ->where('status', 'active')
            ->get();

        foreach ($batches as $batch) {
            $batchData = $batch->data ?? [];
            if (isset($batchData['virtual_sales'][$date])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a recording sale has real stock impact
     */
    private function checkRealStockImpact(RecordingSale $record): bool
    {
        // Check if any batches have been updated with real stock impact
        $batches = LivestockBatch::where('livestock_id', $record->livestock_id)
            ->where('status', 'active')
            ->get();

        foreach ($batches as $batch) {
            // If batch has sales quantity, it means real stock was impacted
            if ($batch->quantity_sales > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process migration
     */
    private function processMigration($records, bool $dryRun, int $batchSize): array
    {
        $totalRecords = $records->count();
        $processedRecords = 0;
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $errors = [];

        $this->info("🔄 Starting migration process...");
        $this->info("");

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        foreach ($records->chunk($batchSize) as $batch) {
            foreach ($batch as $record) {
                try {
                    $migrationResult = $this->migrateRecord($record, $dryRun);

                    if ($migrationResult['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = [
                            'record_id' => $record->id,
                            'livestock_id' => $record->livestock_id,
                            'date' => $record->date,
                            'error' => $migrationResult['error']
                        ];
                    }

                    $processedRecords++;
                    $progressBar->advance();
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'record_id' => $record->id,
                        'livestock_id' => $record->livestock_id,
                        'date' => $record->date,
                        'error' => $e->getMessage()
                    ];
                    $processedRecords++;
                    $progressBar->advance();
                }
            }
        }

        $progressBar->finish();
        $this->info("");
        $this->info("");

        return [
            'total_records' => $totalRecords,
            'processed_records' => $processedRecords,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors
        ];
    }

    /**
     * Migrate a single record
     */
    private function migrateRecord(RecordingSale $record, bool $dryRun): array
    {
        try {
            // Get sales items
            $items = $record->items;
            if ($items->isEmpty()) {
                return ['success' => false, 'error' => 'No sales items found'];
            }

            // Prepare virtual data
            $virtualData = [];
            foreach ($items as $item) {
                $batchId = $item->livestock_batch_id;
                if (!$batchId) {
                    continue;
                }

                $virtualData[] = [
                    'batch_id' => $batchId,
                    'quantity' => $item->quantity,
                    'weight' => $item->weight,
                    'price_per_unit' => $item->price_per_unit,
                    'amount' => $item->amount,
                    'order' => $item->metadata['allocation_order'] ?? 0
                ];
            }

            if (empty($virtualData)) {
                return ['success' => false, 'error' => 'No valid batch allocations found'];
            }

            if ($dryRun) {
                // Just show what would be migrated
                $this->showMigrationPreview($record, $virtualData);
                return ['success' => true, 'error' => null];
            }

            // Perform actual migration
            return $this->performMigration($record, $virtualData);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Show migration preview for dry run
     */
    private function showMigrationPreview(RecordingSale $record, array $virtualData): void
    {
        $this->line("📋 Would migrate record {$record->id}:");
        $this->line("   Livestock ID: {$record->livestock_id}");
        $this->line("   Date: {$record->date}");
        $this->line("   Total Quantity: {$record->total_quantity}");
        $this->line("   Total Weight: {$record->total_weight}");
        $this->line("   Batch Allocations: " . count($virtualData));

        foreach ($virtualData as $allocation) {
            $this->line("     - Batch {$allocation['batch_id']}: {$allocation['quantity']} units, {$allocation['weight']} kg");
        }
        $this->line("");
    }

    /**
     * Perform actual migration
     */
    private function performMigration(RecordingSale $record, array $virtualData): array
    {
        return DB::transaction(function () use ($record, $virtualData) {
            // Store virtual data in batches
            foreach ($virtualData as $allocation) {
                $batch = LivestockBatch::find($allocation['batch_id']);
                if (!$batch) {
                    throw new Exception("Batch not found: {$allocation['batch_id']}");
                }

                $existingData = $batch->data ?? [];

                // Initialize virtual data structure if not exists
                if (!isset($existingData['virtual_sales'])) {
                    $existingData['virtual_sales'] = [];
                }

                // Store virtual sales data
                $dateKey = $record->date instanceof \Carbon\Carbon ? $record->date->format('Y-m-d') : (string) $record->date;
                $existingData['virtual_sales'][$dateKey] = [
                    'quantity' => $allocation['quantity'],
                    'weight' => $allocation['weight'],
                    'date' => $record->date,
                    'status' => 'draft',
                    'metadata' => [
                        'calculation_method' => 'migration_from_real',
                        'calculation_timestamp' => now()->toIso8601String(),
                        'original_record_id' => $record->id,
                        'price_per_unit' => $allocation['price_per_unit'],
                        'amount' => $allocation['amount'],
                        'allocation_order' => $allocation['order'],
                        'migration_info' => [
                            'migrated_at' => now()->toIso8601String(),
                            'migrated_by' => 'MigrateRealToVirtualQuantityCommand',
                            'original_quantity_sales' => $batch->quantity_sales,
                            'original_quantity_available' => $batch->quantity_available,
                        ]
                    ]
                ];

                // Update batch data and reset quantity_sales to 0 for virtual mode
                $batch->update([
                    'data' => $existingData,
                    'quantity_sales' => 0, // Reset to 0 in virtual mode
                    'updated_at' => now()
                ]);
            }

            // Update recording sale metadata
            $record->update([
                'metadata' => array_merge($record->metadata ?? [], [
                    'virtual_mode' => true,
                    'calculation_mode' => 'virtual',
                    'migration_info' => [
                        'migrated_to_virtual' => true,
                        'migrated_at' => now()->toIso8601String(),
                        'migrated_by' => 'MigrateRealToVirtualQuantityCommand',
                        'virtual_data_count' => count($virtualData),
                        'original_status' => $record->status,
                    ]
                ]),
                'updated_at' => now()
            ]);

            return ['success' => true, 'error' => null];
        });
    }

    /**
     * Show migration summary
     */
    private function showMigrationSummary(array $result): void
    {
        $this->info("📊 Migration Summary");
        $this->info("==================");
        $this->info("Total Records: {$result['total_records']}");
        $this->info("Processed: {$result['processed_records']}");
        $this->info("Successful: {$result['success_count']}");
        $this->info("Errors: {$result['error_count']}");
        $this->info("Skipped: {$result['skipped_count']}");
        $this->info("");

        if (!empty($result['errors'])) {
            $this->error("❌ Errors encountered:");
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->error("   - Record {$error['record_id']} ({$error['livestock_id']} - {$error['date']}): {$error['error']}");
            }

            if (count($result['errors']) > 10) {
                $this->error("   ... and " . (count($result['errors']) - 10) . " more errors");
            }
            $this->info("");
        }

        if ($result['success_count'] > 0) {
            $this->info("✅ Migration completed successfully!");
            $this->info("Virtual quantity data has been created for {$result['success_count']} records");
        } else {
            $this->warn("⚠️ No records were successfully migrated");
        }
    }
}
