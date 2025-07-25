<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SupplyStock;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;
use App\Services\SupplyMetadataService;
use App\Services\SupplyStockManagementService;

class FixSupplyStockMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supply:fix-metadata 
                            {--dry-run : Show what would be fixed without making changes}
                            {--batch-size=100 : Number of records to process in each batch}
                            {--force : Force update even if metadata already exists}
                            {--source-type= : Filter by source type (purchase, mutation, etc.)}
                            {--farm-id= : Filter by farm ID}
                            {--supply-id= : Filter by supply ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing metadata in supply stock records';

    /**
     * Services
     */
    protected $metadataService;
    protected $stockManagementService;

    /**
     * Statistics
     */
    protected $stats = [
        'total_processed' => 0,
        'fixed' => 0,
        'skipped' => 0,
        'errors' => 0,
        'dry_run' => false
    ];

    /**
     * Execute the console command.
     */
    public function handle(SupplyMetadataService $metadataService, SupplyStockManagementService $stockManagementService)
    {
        $this->metadataService = $metadataService;
        $this->stockManagementService = $stockManagementService;
        $this->stats['dry_run'] = $this->option('dry-run');

        $this->info('🔧 Supply Stock Metadata Fix Tool');
        $this->info('================================');

        if ($this->stats['dry_run']) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        // Build query
        $query = $this->buildQuery();

        // Get total count
        $totalRecords = $query->count();

        if ($totalRecords === 0) {
            $this->info('✅ No records found matching the criteria');
            return 0;
        }

        $this->info("📊 Found {$totalRecords} records to process");

        // Confirm if not dry run
        if (!$this->stats['dry_run'] && !$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with fixing metadata?')) {
                $this->info('❌ Operation cancelled');
                return 0;
            }
        }

        // Process records
        $this->processRecords($query, $totalRecords);

        // Show results
        $this->showResults();

        return 0;
    }

    /**
     * Build the query based on options
     */
    protected function buildQuery()
    {
        $query = SupplyStock::query();

        // Filter by source type
        if ($sourceType = $this->option('source-type')) {
            $query->where('source_type', $sourceType);
        }

        // Filter by farm ID
        if ($farmId = $this->option('farm-id')) {
            $query->where('farm_id', $farmId);
        }

        // Filter by supply ID
        if ($supplyId = $this->option('supply-id')) {
            $query->where('supply_id', $supplyId);
        }

        // Filter records that need metadata fix
        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('metadata')
                    ->orWhere('metadata', '{}')
                    ->orWhere('metadata', '[]')
                    ->orWhere('metadata', '');
            });
        }

        return $query;
    }

    /**
     * Process records in batches
     */
    protected function processRecords($query, $totalRecords)
    {
        $batchSize = (int) $this->option('batch-size');
        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $query->chunk($batchSize, function ($stocks) use ($bar) {
            foreach ($stocks as $stock) {
                $this->processStock($stock);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    /**
     * Process individual stock record
     */
    protected function processStock(SupplyStock $stock)
    {
        $this->stats['total_processed']++;

        try {
            // Skip if metadata already exists and not forcing
            if (!$this->option('force') && $this->hasValidMetadata($stock)) {
                $this->stats['skipped']++;
                return;
            }

            // Build metadata based on source type
            $metadata = $this->buildMetadataForStock($stock);

            if ($metadata) {
                if (!$this->stats['dry_run']) {
                    $stock->update(['metadata' => $metadata]);
                }

                $this->stats['fixed']++;

                Log::info('Supply stock metadata fixed', [
                    'stock_id' => $stock->id,
                    'source_type' => $stock->source_type,
                    'source_id' => $stock->source_id,
                    'metadata_keys' => array_keys($metadata)
                ]);
            } else {
                $this->stats['skipped']++;
                Log::warning('Could not build metadata for stock', [
                    'stock_id' => $stock->id,
                    'source_type' => $stock->source_type,
                    'source_id' => $stock->source_id
                ]);
            }
        } catch (\Exception $e) {
            $this->stats['errors']++;
            Log::error('Error fixing supply stock metadata', [
                'stock_id' => $stock->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Check if stock already has valid metadata
     */
    protected function hasValidMetadata(SupplyStock $stock): bool
    {
        $metadata = $stock->metadata;

        if (empty($metadata)) {
            return false;
        }

        // Check if metadata has basic structure
        return isset($metadata['source_type']) &&
            isset($metadata['history']) &&
            is_array($metadata['history']);
    }

    /**
     * Build metadata for stock based on source type
     */
    protected function buildMetadataForStock(SupplyStock $stock): ?array
    {
        switch ($stock->source_type) {
            case 'purchase':
                return $this->buildPurchaseMetadata($stock);

            case 'mutation':
                return $this->buildMutationMetadata($stock);

            case 'manual':
                return $this->buildManualMetadata($stock);

            default:
                return $this->buildGenericMetadata($stock);
        }
    }

    /**
     * Build metadata for purchase records
     */
    protected function buildPurchaseMetadata(SupplyStock $stock): ?array
    {
        // Get related purchase data
        $purchase = SupplyPurchase::with(['batch.supplier', 'supply', 'farm'])
            ->find($stock->supply_purchase_id);

        if (!$purchase) {
            return null;
        }

        $batch = $purchase->batch;
        $supplierName = $batch->supplier ? $batch->supplier->name : null;

        return $this->metadataService->buildPurchaseMetadata([
            'purchase_id' => $purchase->id,
            'invoice_number' => $batch->invoice_number,
            'supplier_id' => $batch->supplier_id,
            'supplier_name' => $supplierName,
            'purchase_date' => $batch->date,
            'delivery_date' => $batch->date,
            'quantity' => $stock->quantity_in,
            'notes' => $batch->notes,
            'quality_passed' => true,
            'quality_checked_by' => $stock->created_by,
            'tags' => ['purchase', 'fixed'],
            'custom_fields' => [
                'batch_id' => $batch->id,
                'do_number' => $batch->do_number,
                'expedition_id' => $batch->expedition_id,
                'expedition_fee' => $batch->expedition_fee,
                'unit_id' => $purchase->unit_id,
                'converted_unit' => $purchase->converted_unit,
                'price_per_unit' => $purchase->price_per_unit,
                'price_per_converted_unit' => $purchase->price_per_converted_unit,
                'fix_applied_at' => now()->toISOString(),
                'fix_method' => 'artisan_command'
            ]
        ]);
    }

    /**
     * Build metadata for mutation records
     */
    protected function buildMutationMetadata(SupplyStock $stock): ?array
    {
        // For mutation records, we'll build a basic metadata structure
        return [
            'source_type' => 'mutation',
            'mutation_id' => $stock->source_id,
            'batch_code' => $this->metadataService->generateBatchCode('MUT'),
            'mutation_date' => $stock->date,
            'quantity' => $stock->quantity_in,
            'quality_check' => [
                'passed' => true,
                'checked_by' => $stock->created_by,
                'checked_at' => now()->toISOString(),
                'notes' => 'Auto-fixed by metadata repair tool'
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'metadata_fixed',
                    'user_id' => $stock->created_by,
                    'quantity' => $stock->quantity_in,
                    'notes' => 'Metadata repaired by artisan command'
                ]
            ],
            'tags' => ['mutation', 'fixed'],
            'custom_fields' => [
                'fix_applied_at' => now()->toISOString(),
                'fix_method' => 'artisan_command',
                'original_source_type' => $stock->source_type,
                'original_source_id' => $stock->source_id
            ]
        ];
    }

    /**
     * Build metadata for manual records
     */
    protected function buildManualMetadata(SupplyStock $stock): ?array
    {
        return [
            'source_type' => 'manual',
            'batch_code' => $this->metadataService->generateBatchCode('MAN'),
            'manual_date' => $stock->date,
            'quantity' => $stock->quantity_in,
            'quality_check' => [
                'passed' => true,
                'checked_by' => $stock->created_by,
                'checked_at' => now()->toISOString(),
                'notes' => 'Auto-fixed by metadata repair tool'
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'metadata_fixed',
                    'user_id' => $stock->created_by,
                    'quantity' => $stock->quantity_in,
                    'notes' => 'Metadata repaired by artisan command'
                ]
            ],
            'tags' => ['manual', 'fixed'],
            'custom_fields' => [
                'fix_applied_at' => now()->toISOString(),
                'fix_method' => 'artisan_command',
                'original_source_type' => $stock->source_type,
                'original_source_id' => $stock->source_id
            ]
        ];
    }

    /**
     * Build generic metadata for unknown source types
     */
    protected function buildGenericMetadata(SupplyStock $stock): array
    {
        return [
            'source_type' => $stock->source_type ?? 'unknown',
            'batch_code' => $this->metadataService->generateBatchCode('GEN'),
            'date' => $stock->date,
            'quantity' => $stock->quantity_in,
            'quality_check' => [
                'passed' => true,
                'checked_by' => $stock->created_by,
                'checked_at' => now()->toISOString(),
                'notes' => 'Auto-fixed by metadata repair tool'
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'metadata_fixed',
                    'user_id' => $stock->created_by,
                    'quantity' => $stock->quantity_in,
                    'notes' => 'Metadata repaired by artisan command'
                ]
            ],
            'tags' => ['fixed', 'unknown_source'],
            'custom_fields' => [
                'fix_applied_at' => now()->toISOString(),
                'fix_method' => 'artisan_command',
                'original_source_type' => $stock->source_type,
                'original_source_id' => $stock->source_id
            ]
        ];
    }

    /**
     * Show processing results
     */
    protected function showResults()
    {
        $this->newLine();
        $this->info('📈 Processing Results:');
        $this->info('====================');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $this->stats['total_processed']],
                ['Fixed', $this->stats['fixed']],
                ['Skipped', $this->stats['skipped']],
                ['Errors', $this->stats['errors']],
            ]
        );

        if ($this->stats['dry_run']) {
            $this->warn('⚠️  This was a dry run. Use --force to apply changes.');
        } else {
            $this->info('✅ Metadata fix completed successfully!');
        }

        // Log summary
        Log::info('Supply stock metadata fix completed', $this->stats);
    }
}
