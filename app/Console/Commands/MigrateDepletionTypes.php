<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LivestockDepletionService;
use App\Config\LivestockDepletionConfig;
use App\Models\LivestockDepletion;
use Illuminate\Support\Facades\Log;

/**
 * Command to migrate existing livestock depletion data to include config normalization
 * 
 * This command adds normalized metadata to existing records to ensure backward
 * compatibility when switching between Indonesian and English depletion types.
 * 
 * @version 1.0
 * @since 2025-01-23
 */
class MigrateDepletionTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livestock:migrate-depletion-types 
                            {--batch-size=100 : Number of records to process per batch}
                            {--dry-run : Show what would be migrated without making changes}
                            {--force : Force migration even if already completed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing livestock depletion data to include normalized config metadata';

    /**
     * Livestock depletion service
     */
    protected ?LivestockDepletionService $depletionService = null;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get depletion service instance
     */
    protected function getDepletionService(): LivestockDepletionService
    {
        if (!$this->depletionService) {
            $this->depletionService = app(LivestockDepletionService::class);
        }
        return $this->depletionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting livestock depletion types migration...');
        $this->newLine();

        // Get migration status first
        $status = $this->getDepletionService()->getMigrationStatus();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Records', number_format($status['total_records'])],
                ['Migrated Records', number_format($status['migrated_records'])],
                ['Unmigrated Records', number_format($status['unmigrated_records'])],
                ['Migration Percentage', $status['migration_percentage'] . '%'],
                ['Migration Complete', $status['migration_complete'] ? 'Yes' : 'No']
            ]
        );

        if ($status['migration_complete'] && !$this->option('force')) {
            $this->info('✅ Migration already completed. Use --force to run again.');
            return 0;
        }

        if ($status['unmigrated_records'] === 0) {
            $this->info('✅ No records need migration.');
            return 0;
        }

        $this->newLine();

        // Show preview of what will be migrated
        $this->showMigrationPreview();

        // Confirm before proceeding (unless dry-run)
        if (!$this->option('dry-run')) {
            if (!$this->confirm('Do you want to proceed with the migration?')) {
                $this->info('Migration cancelled.');
                return 1;
            }
        }

        $this->newLine();

        // Perform migration
        $batchSize = (int) $this->option('batch-size');

        if ($this->option('dry-run')) {
            $this->performDryRun($batchSize);
        } else {
            $this->performMigration($batchSize);
        }

        return 0;
    }

    /**
     * Show preview of migration changes
     */
    protected function showMigrationPreview()
    {
        $this->info('📋 Migration Preview:');
        $this->newLine();

        // Get sample records that need migration
        $sampleRecords = LivestockDepletion::whereNull('metadata->depletion_config')
            ->orWhereJsonLength('metadata->depletion_config', 0)
            ->limit(5)
            ->get();

        if ($sampleRecords->isEmpty()) {
            $this->info('No records found that need migration.');
            return;
        }

        $previewData = [];
        foreach ($sampleRecords as $record) {
            $normalizedType = LivestockDepletionConfig::normalize($record->jenis);
            $previewData[] = [
                'ID' => $record->id,
                'Current Type' => $record->jenis,
                'Normalized Type' => $normalizedType,
                'Display Name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                'Category' => LivestockDepletionConfig::getCategory($normalizedType),
            ];
        }

        $this->table(
            ['ID', 'Current Type', 'Normalized Type', 'Display Name', 'Category'],
            $previewData
        );

        $this->newLine();
        $this->info('📊 Type Distribution:');
        $this->showTypeDistribution();
    }

    /**
     * Show distribution of depletion types
     */
    protected function showTypeDistribution()
    {
        $typeDistribution = LivestockDepletion::selectRaw('jenis, COUNT(*) as count')
            ->whereNull('metadata->depletion_config')
            ->orWhereJsonLength('metadata->depletion_config', 0)
            ->groupBy('jenis')
            ->orderByDesc('count')
            ->get();

        $distributionData = [];
        foreach ($typeDistribution as $item) {
            $normalizedType = LivestockDepletionConfig::normalize($item->jenis);
            $distributionData[] = [
                'Current Type' => $item->jenis,
                'Normalized Type' => $normalizedType,
                'Count' => number_format($item->count),
                'Category' => LivestockDepletionConfig::getCategory($normalizedType),
            ];
        }

        $this->table(
            ['Current Type', 'Normalized Type', 'Count', 'Category'],
            $distributionData
        );
    }

    /**
     * Perform dry run migration
     */
    protected function performDryRun(int $batchSize)
    {
        $this->info('🔍 Performing dry run migration...');
        $this->newLine();

        $totalRecords = LivestockDepletion::whereNull('metadata->depletion_config')
            ->orWhereJsonLength('metadata->depletion_config', 0)
            ->count();

        $processedRecords = 0;

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->setFormat('verbose');

        LivestockDepletion::whereNull('metadata->depletion_config')
            ->orWhereJsonLength('metadata->depletion_config', 0)
            ->chunk($batchSize, function ($records) use (&$processedRecords, $progressBar) {
                foreach ($records as $record) {
                    // Just simulate processing
                    $normalizedType = LivestockDepletionConfig::normalize($record->jenis);

                    // Log what would be changed
                    Log::info('DRY RUN: Would migrate record', [
                        'id' => $record->id,
                        'current_type' => $record->jenis,
                        'normalized_type' => $normalizedType,
                        'legacy_type' => LivestockDepletionConfig::toLegacy($normalizedType)
                    ]);

                    $processedRecords++;
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Dry run completed. {$processedRecords} records would be migrated.");
        $this->info('📝 Check the logs for detailed information about what would be changed.');
    }

    /**
     * Perform actual migration
     */
    protected function performMigration(int $batchSize)
    {
        $this->info('⚡ Performing migration...');
        $this->newLine();

        $result = $this->getDepletionService()->migrateLegacyData($batchSize);

        if ($result['success']) {
            $this->info('✅ Migration completed successfully!');
            $this->newLine();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Processed', number_format($result['total_processed'])],
                    ['Total Updated', number_format($result['total_updated'])],
                    ['Errors', count($result['errors'])],
                    ['Success Rate', round(($result['total_updated'] / max($result['total_processed'], 1)) * 100, 2) . '%'],
                    ['Completed At', $result['migration_completed_at']]
                ]
            );

            if (!empty($result['errors'])) {
                $this->newLine();
                $this->warn('⚠️ Some errors occurred during migration:');
                foreach (array_slice($result['errors'], 0, 5) as $error) {
                    $this->line("  • {$error}");
                }
                if (count($result['errors']) > 5) {
                    $this->line("  • ... and " . (count($result['errors']) - 5) . " more errors");
                }
                $this->info('📝 Check the logs for complete error details.');
            }
        } else {
            $this->error('❌ Migration failed!');
            $this->newLine();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Processed', number_format($result['total_processed'])],
                    ['Total Updated', number_format($result['total_updated'])],
                    ['Errors', count($result['errors'])]
                ]
            );

            if (!empty($result['errors'])) {
                $this->newLine();
                $this->error('Errors encountered:');
                foreach (array_slice($result['errors'], 0, 10) as $error) {
                    $this->line("  • {$error}");
                }
            }

            return 1;
        }

        // Show final migration status
        $this->newLine();
        $this->info('📊 Final Migration Status:');
        $finalStatus = $this->getDepletionService()->getMigrationStatus();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Records', number_format($finalStatus['total_records'])],
                ['Migrated Records', number_format($finalStatus['migrated_records'])],
                ['Migration Percentage', $finalStatus['migration_percentage'] . '%'],
                ['Migration Complete', $finalStatus['migration_complete'] ? 'Yes' : 'No']
            ]
        );

        if ($finalStatus['migration_complete']) {
            $this->info('🎉 All records have been successfully migrated!');
        }

        return 0;
    }
}
 