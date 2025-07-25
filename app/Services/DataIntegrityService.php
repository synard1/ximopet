<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SupplyStock;
use App\Models\CurrentSupply;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;

class DataIntegrityService
{
    protected $metadataService;
    protected $stockManagementService;

    public function __construct(
        SupplyMetadataService $metadataService,
        SupplyStockManagementService $stockManagementService
    ) {
        $this->metadataService = $metadataService;
        $this->stockManagementService = $stockManagementService;
    }

    /**
     * Check and fix supply stock metadata integrity
     */
    public function checkSupplyStockMetadataIntegrity(array $options = []): array
    {
        $stats = [
            'total_checked' => 0,
            'missing_metadata' => 0,
            'invalid_metadata' => 0,
            'fixed' => 0,
            'errors' => 0,
            'dry_run' => $options['dry_run'] ?? false
        ];

        try {
            // Build query
            $query = $this->buildSupplyStockQuery($options);
            $stocks = $query->get();

            foreach ($stocks as $stock) {
                $stats['total_checked']++;

                // Check metadata integrity
                $integrityResult = $this->checkStockMetadataIntegrity($stock);

                if ($integrityResult['needs_fix']) {
                    if ($integrityResult['type'] === 'missing') {
                        $stats['missing_metadata']++;
                    } else {
                        $stats['invalid_metadata']++;
                    }

                    // Fix if not dry run
                    if (!$stats['dry_run']) {
                        $fixResult = $this->fixStockMetadata($stock);
                        if ($fixResult['success']) {
                            $stats['fixed']++;
                        } else {
                            $stats['errors']++;
                        }
                    }
                }
            }

            Log::info('Supply stock metadata integrity check completed', $stats);
        } catch (\Exception $e) {
            $stats['errors']++;
            Log::error('Error during metadata integrity check', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return $stats;
    }

    /**
     * Check and fix current supply integrity
     */
    public function checkCurrentSupplyIntegrity(array $options = []): array
    {
        $stats = [
            'total_checked' => 0,
            'mismatched' => 0,
            'fixed' => 0,
            'errors' => 0,
            'dry_run' => $options['dry_run'] ?? false
        ];

        try {
            $currentSupplies = CurrentSupply::where('type', 'supply')->get();

            foreach ($currentSupplies as $currentSupply) {
                $stats['total_checked']++;

                // Calculate expected quantity from supply stocks
                $expectedQuantity = $this->calculateExpectedSupplyQuantity($currentSupply);

                if (abs($currentSupply->quantity - $expectedQuantity) > 0.01) {
                    $stats['mismatched']++;

                    if (!$stats['dry_run']) {
                        $currentSupply->update(['quantity' => $expectedQuantity]);
                        $stats['fixed']++;
                    }
                }
            }

            Log::info('Current supply integrity check completed', $stats);
        } catch (\Exception $e) {
            $stats['errors']++;
            Log::error('Error during current supply integrity check', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return $stats;
    }

    /**
     * Check and fix supply purchase batch integrity
     */
    public function checkSupplyPurchaseBatchIntegrity(array $options = []): array
    {
        $stats = [
            'total_checked' => 0,
            'orphaned_purchases' => 0,
            'fixed' => 0,
            'errors' => 0,
            'dry_run' => $options['dry_run'] ?? false
        ];

        try {
            $batches = SupplyPurchaseBatch::with('supplyPurchases')->get();

            foreach ($batches as $batch) {
                $stats['total_checked']++;

                // Check for orphaned purchases (purchases without batch)
                $orphanedPurchases = SupplyPurchase::where('supply_purchase_batch_id', $batch->id)
                    ->whereDoesntHave('batch')
                    ->get();

                if ($orphanedPurchases->count() > 0) {
                    $stats['orphaned_purchases'] += $orphanedPurchases->count();

                    if (!$stats['dry_run']) {
                        // Delete orphaned purchases
                        $orphanedPurchases->each(function ($purchase) {
                            $purchase->delete();
                        });
                        $stats['fixed'] += $orphanedPurchases->count();
                    }
                }
            }

            Log::info('Supply purchase batch integrity check completed', $stats);
        } catch (\Exception $e) {
            $stats['errors']++;
            Log::error('Error during supply purchase batch integrity check', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return $stats;
    }

    /**
     * Comprehensive data integrity check
     */
    public function runComprehensiveIntegrityCheck(array $options = []): array
    {
        $results = [
            'supply_stock_metadata' => $this->checkSupplyStockMetadataIntegrity($options),
            'current_supply' => $this->checkCurrentSupplyIntegrity($options),
            'supply_purchase_batch' => $this->checkSupplyPurchaseBatchIntegrity($options),
            'timestamp' => now()->toISOString(),
            'options' => $options
        ];

        Log::info('Comprehensive data integrity check completed', $results);

        return $results;
    }

    /**
     * Build supply stock query based on options
     */
    protected function buildSupplyStockQuery(array $options)
    {
        $query = SupplyStock::query();

        // Filter by source type
        if (!empty($options['source_type'])) {
            $query->where('source_type', $options['source_type']);
        }

        // Filter by farm ID
        if (!empty($options['farm_id'])) {
            $query->where('farm_id', $options['farm_id']);
        }

        // Filter by supply ID
        if (!empty($options['supply_id'])) {
            $query->where('supply_id', $options['supply_id']);
        }

        // Filter records that need metadata fix
        if (empty($options['force'])) {
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
     * Check individual stock metadata integrity
     */
    protected function checkStockMetadataIntegrity(SupplyStock $stock): array
    {
        $metadata = $stock->metadata;

        // Check if metadata is missing
        if (empty($metadata)) {
            return [
                'needs_fix' => true,
                'type' => 'missing',
                'reason' => 'Metadata is empty or null'
            ];
        }

        // Check if metadata has basic structure
        if (!isset($metadata['source_type']) || !isset($metadata['history'])) {
            return [
                'needs_fix' => true,
                'type' => 'invalid',
                'reason' => 'Metadata missing required fields'
            ];
        }

        // Validate metadata structure
        $validation = $this->metadataService->validateMetadata($metadata);
        if (!$validation['valid']) {
            return [
                'needs_fix' => true,
                'type' => 'invalid',
                'reason' => 'Metadata validation failed: ' . implode(', ', $validation['errors'])
            ];
        }

        return [
            'needs_fix' => false,
            'type' => 'valid',
            'reason' => 'Metadata is valid'
        ];
    }

    /**
     * Fix stock metadata
     */
    protected function fixStockMetadata(SupplyStock $stock): array
    {
        try {
            // Build metadata based on source type
            $metadata = $this->buildMetadataForStock($stock);

            if ($metadata) {
                $stock->update(['metadata' => $metadata]);

                Log::info('Stock metadata fixed', [
                    'stock_id' => $stock->id,
                    'source_type' => $stock->source_type,
                    'metadata_keys' => array_keys($metadata)
                ]);

                return ['success' => true, 'metadata' => $metadata];
            }

            return ['success' => false, 'reason' => 'Could not build metadata'];
        } catch (\Exception $e) {
            Log::error('Error fixing stock metadata', [
                'stock_id' => $stock->id,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'reason' => $e->getMessage()];
        }
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
                'fix_method' => 'data_integrity_service'
            ]
        ]);
    }

    /**
     * Build metadata for mutation records
     */
    protected function buildMutationMetadata(SupplyStock $stock): array
    {
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
                'notes' => 'Auto-fixed by data integrity service'
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'metadata_fixed',
                    'user_id' => $stock->created_by,
                    'quantity' => $stock->quantity_in,
                    'notes' => 'Metadata repaired by data integrity service'
                ]
            ],
            'tags' => ['mutation', 'fixed'],
            'custom_fields' => [
                'fix_applied_at' => now()->toISOString(),
                'fix_method' => 'data_integrity_service',
                'original_source_type' => $stock->source_type,
                'original_source_id' => $stock->source_id
            ]
        ];
    }

    /**
     * Build metadata for manual records
     */
    protected function buildManualMetadata(SupplyStock $stock): array
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
                'notes' => 'Auto-fixed by data integrity service'
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'metadata_fixed',
                    'user_id' => $stock->created_by,
                    'quantity' => $stock->quantity_in,
                    'notes' => 'Metadata repaired by data integrity service'
                ]
            ],
            'tags' => ['manual', 'fixed'],
            'custom_fields' => [
                'fix_applied_at' => now()->toISOString(),
                'fix_method' => 'data_integrity_service',
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
                'notes' => 'Auto-fixed by data integrity service'
            ],
            'history' => [
                [
                    'date' => now()->toISOString(),
                    'action' => 'metadata_fixed',
                    'user_id' => $stock->created_by,
                    'quantity' => $stock->quantity_in,
                    'notes' => 'Metadata repaired by data integrity service'
                ]
            ],
            'tags' => ['fixed', 'unknown_source'],
            'custom_fields' => [
                'fix_applied_at' => now()->toISOString(),
                'fix_method' => 'data_integrity_service',
                'original_source_type' => $stock->source_type,
                'original_source_id' => $stock->source_id
            ]
        ];
    }

    /**
     * Calculate expected supply quantity from supply stocks
     */
    protected function calculateExpectedSupplyQuantity(CurrentSupply $currentSupply): float
    {
        return SupplyStock::join('supply_purchases', 'supply_stocks.supply_purchase_id', '=', 'supply_purchases.id')
            ->join('supply_purchase_batches', 'supply_purchases.supply_purchase_batch_id', '=', 'supply_purchase_batches.id')
            ->where('supply_stocks.farm_id', $currentSupply->farm_id)
            ->where('supply_stocks.supply_id', $currentSupply->item_id)
            ->where('supply_purchase_batches.status', SupplyPurchaseBatch::STATUS_ARRIVED)
            ->whereNull('supply_stocks.deleted_at')
            ->sum(DB::raw('supply_stocks.quantity_in - supply_stocks.quantity_used - supply_stocks.quantity_mutated'));
    }
}
