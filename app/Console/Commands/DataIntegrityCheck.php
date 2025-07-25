<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\DataIntegrityService;

class DataIntegrityCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:integrity-check 
                            {--dry-run : Show what would be fixed without making changes}
                            {--type=all : Type of integrity check (all, metadata, current-supply, purchase-batch)}
                            {--source-type= : Filter by source type for metadata check}
                            {--farm-id= : Filter by farm ID}
                            {--supply-id= : Filter by supply ID}
                            {--force : Force update even if data seems valid}
                            {--export= : Export results to JSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive data integrity check and fix tool';

    /**
     * Data integrity service
     */
    protected $integrityService;

    /**
     * Execute the console command.
     */
    public function handle(DataIntegrityService $integrityService)
    {
        $this->integrityService = $integrityService;

        $this->info('🔍 Data Integrity Check Tool');
        $this->info('===========================');

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        $type = $this->option('type');
        $options = $this->buildOptions();

        $this->info("📋 Running {$type} integrity check...");

        // Run appropriate check based on type
        switch ($type) {
            case 'metadata':
                $results = $this->runMetadataCheck($options);
                break;

            case 'current-supply':
                $results = $this->runCurrentSupplyCheck($options);
                break;

            case 'purchase-batch':
                $results = $this->runPurchaseBatchCheck($options);
                break;

            case 'all':
            default:
                $results = $this->runComprehensiveCheck($options);
                break;
        }

        // Display results
        $this->displayResults($results, $type);

        // Export results if requested
        if ($exportPath = $this->option('export')) {
            $this->exportResults($results, $exportPath);
        }

        return 0;
    }

    /**
     * Build options array from command options
     */
    protected function buildOptions(): array
    {
        return [
            'dry_run' => $this->option('dry-run'),
            'force' => $this->option('force'),
            'source_type' => $this->option('source-type'),
            'farm_id' => $this->option('farm-id'),
            'supply_id' => $this->option('supply-id'),
        ];
    }

    /**
     * Run metadata integrity check
     */
    protected function runMetadataCheck(array $options): array
    {
        $this->info('🔧 Checking supply stock metadata integrity...');

        $results = $this->integrityService->checkSupplyStockMetadataIntegrity($options);

        $this->displayMetadataResults($results);

        return [
            'type' => 'metadata',
            'results' => $results,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Run current supply integrity check
     */
    protected function runCurrentSupplyCheck(array $options): array
    {
        $this->info('📊 Checking current supply integrity...');

        $results = $this->integrityService->checkCurrentSupplyIntegrity($options);

        $this->displayCurrentSupplyResults($results);

        return [
            'type' => 'current-supply',
            'results' => $results,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Run purchase batch integrity check
     */
    protected function runPurchaseBatchCheck(array $options): array
    {
        $this->info('📦 Checking supply purchase batch integrity...');

        $results = $this->integrityService->checkSupplyPurchaseBatchIntegrity($options);

        $this->displayPurchaseBatchResults($results);

        return [
            'type' => 'purchase-batch',
            'results' => $results,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Run comprehensive integrity check
     */
    protected function runComprehensiveCheck(array $options): array
    {
        $this->info('🔍 Running comprehensive data integrity check...');

        $results = $this->integrityService->runComprehensiveIntegrityCheck($options);

        return [
            'type' => 'comprehensive',
            'results' => $results,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Display metadata check results
     */
    protected function displayMetadataResults(array $results)
    {
        $this->newLine();
        $this->info('📈 Supply Stock Metadata Results:');
        $this->info('==================================');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Checked', $results['total_checked']],
                ['Missing Metadata', $results['missing_metadata']],
                ['Invalid Metadata', $results['invalid_metadata']],
                ['Fixed', $results['fixed']],
                ['Errors', $results['errors']],
            ]
        );
    }

    /**
     * Display current supply check results
     */
    protected function displayCurrentSupplyResults(array $results)
    {
        $this->newLine();
        $this->info('📊 Current Supply Results:');
        $this->info('==========================');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Checked', $results['total_checked']],
                ['Mismatched', $results['mismatched']],
                ['Fixed', $results['fixed']],
                ['Errors', $results['errors']],
            ]
        );
    }

    /**
     * Display purchase batch check results
     */
    protected function displayPurchaseBatchResults(array $results)
    {
        $this->newLine();
        $this->info('📦 Purchase Batch Results:');
        $this->info('==========================');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Checked', $results['total_checked']],
                ['Orphaned Purchases', $results['orphaned_purchases']],
                ['Fixed', $results['fixed']],
                ['Errors', $results['errors']],
            ]
        );
    }

    /**
     * Display comprehensive results
     */
    protected function displayResults(array $results, string $type)
    {
        if ($type === 'all') {
            $this->newLine();
            $this->info('🔍 Comprehensive Data Integrity Check Results:');
            $this->info('==============================================');

            // Display metadata results
            if (isset($results['results']['supply_stock_metadata'])) {
                $this->displayMetadataResults($results['results']['supply_stock_metadata']);
            }

            // Display current supply results
            if (isset($results['results']['current_supply'])) {
                $this->displayCurrentSupplyResults($results['results']['current_supply']);
            }

            // Display purchase batch results
            if (isset($results['results']['supply_purchase_batch'])) {
                $this->displayPurchaseBatchResults($results['results']['supply_purchase_batch']);
            }

            // Summary
            $this->newLine();
            $this->info('📋 Summary:');
            $this->info('===========');

            $totalFixed = 0;
            $totalErrors = 0;

            foreach ($results['results'] as $checkType => $checkResults) {
                if (is_array($checkResults) && isset($checkResults['fixed'])) {
                    $totalFixed += $checkResults['fixed'];
                    $totalErrors += $checkResults['errors'] ?? 0;
                }
            }

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Fixed', $totalFixed],
                    ['Total Errors', $totalErrors],
                ]
            );
        }

        // Show dry run warning if applicable
        if ($this->option('dry-run')) {
            $this->warn('⚠️  This was a dry run. Use --force to apply changes.');
        } else {
            $this->info('✅ Data integrity check completed successfully!');
        }
    }

    /**
     * Export results to JSON file
     */
    protected function exportResults(array $results, string $exportPath)
    {
        try {
            $jsonData = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if (file_put_contents($exportPath, $jsonData)) {
                $this->info("📄 Results exported to: {$exportPath}");
            } else {
                $this->error("❌ Failed to export results to: {$exportPath}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error exporting results: " . $e->getMessage());
        }
    }
}
