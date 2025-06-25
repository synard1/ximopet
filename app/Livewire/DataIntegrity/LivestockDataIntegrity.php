<?php

namespace App\Livewire\DataIntegrity;

use Livewire\Component;
use App\Services\LivestockDataIntegrityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * LivestockDataIntegrity Component
 * 
 * Komponen untuk mengecek dan memperbaiki integritas data livestock
 * termasuk relasi dengan CurrentLivestock
 * 
 * @version 2.0.0
 * @author System
 * @since 2025-01-19
 */
class LivestockDataIntegrity extends Component
{
    // Core properties
    public $logs = [];
    public $isRunning = false;
    public $error = null;

    // Preview and confirmation properties
    public $showConfirmation = false;
    public $showPreview = false;
    public $previewData = [];

    // Count properties
    public $invalidStocksCount = 0;
    public $invalidBatchesCount = 0;
    public $deletedStocksCount = 0;
    public $missingCurrentLivestockCount = 0;

    // Audit trail properties
    public $auditTrails = [];
    public $showAuditTrail = false;
    public $selectedAuditModelType = null;
    public $selectedAuditModelId = null;

    /**
     * Preview invalid data tanpa melakukan perubahan
     */
    public function previewInvalidData()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->logs = [];
        $this->showConfirmation = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->previewInvalidLivestockData();

            // Ensure result is array
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success'] ?? false) {
                $this->logs = $result['logs'] ?? [];
                $this->invalidStocksCount = $result['invalid_stocks_count'] ?? 0;
                $this->invalidBatchesCount = $result['invalid_batches_count'] ?? 0;
                $this->missingCurrentLivestockCount = $result['missing_current_livestock_count'] ?? 0;

                if ($this->invalidStocksCount > 0 || $this->invalidBatchesCount > 0 || $this->missingCurrentLivestockCount > 0) {
                    $this->showConfirmation = true;
                }
            } else {
                $this->error = $result['error'] ?? 'Unknown error occurred';
                $this->logs = $result['logs'] ?? [];
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LivestockDataIntegrity::previewInvalidData failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Menjalankan integrity check dan memperbaiki data yang invalid
     */
    public function runIntegrityCheck()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->logs = [];
        $this->showConfirmation = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->checkAndFixInvalidLivestockData();

            // Ensure result is array
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success'] ?? false) {
                $this->logs = $result['logs'] ?? [];
                $this->deletedStocksCount = $result['deleted_stocks_count'] ?? 0;
            } else {
                $this->error = $result['error'] ?? 'Unknown error occurred';
                $this->logs = $result['logs'] ?? [];
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LivestockDataIntegrity::runIntegrityCheck failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Restore related record (purchase/mutation) jika soft deleted
     */
    public function restoreRecord($type, $sourceId)
    {
        try {
            $service = new LivestockDataIntegrityService();
            $restored = $service->restoreRelatedRecord($type, $sourceId);
            $this->logs = array_merge($this->logs, $service->getLogs());

            if ($restored) {
                $this->error = null;
                $this->dispatch('restoredSuccessfully');
                Log::info('Successfully restored record', ['type' => $type, 'source_id' => $sourceId]);
            } else {
                $this->error = 'Restore failed or record is not restorable.';
                Log::warning('Failed to restore record', ['type' => $type, 'source_id' => $sourceId]);
            }
        } catch (\Exception $e) {
            $this->error = 'Error during restore: ' . $e->getMessage();
            Log::error('Exception during restore', [
                'error' => $e->getMessage(),
                'type' => $type,
                'source_id' => $sourceId
            ]);
        }
    }

    /**
     * Cek apakah record bisa di-restore
     */
    public function canRestore($type, $sourceId)
    {
        try {
            $service = new LivestockDataIntegrityService();
            return $service->canRestore($type, $sourceId);
        } catch (\Exception $e) {
            Log::error('Error checking restore capability', [
                'error' => $e->getMessage(),
                'type' => $type,
                'source_id' => $sourceId
            ]);
            return false;
        }
    }

    /**
     * Restore missing stock untuk purchase atau mutation
     */
    public function restoreStock($type, $sourceId)
    {
        try {
            $service = new LivestockDataIntegrityService();
            $restored = $service->restoreMissingStock($type, $sourceId);
            $this->logs = array_merge($this->logs, $service->getLogs());

            if ($restored) {
                $this->error = null;
                $this->dispatch('restoredStockSuccessfully');
                Log::info('Successfully restored stock', ['type' => $type, 'source_id' => $sourceId]);
            } else {
                $this->error = 'Restore stock failed or stock already exists.';
                Log::warning('Failed to restore stock', ['type' => $type, 'source_id' => $sourceId]);
            }
        } catch (\Exception $e) {
            $this->error = 'Error during stock restore: ' . $e->getMessage();
            Log::error('Exception during stock restore', [
                'error' => $e->getMessage(),
                'type' => $type,
                'source_id' => $sourceId
            ]);
        }
    }

    /**
     * Fix semua quantity mismatch
     */
    public function fixAllQuantityMismatch()
    {
        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->fixQuantityMismatchStocks();
            $this->logs = array_merge($this->logs, $result['logs'] ?? []);

            if (($result['fixed_count'] ?? 0) > 0) {
                $this->error = null;
                $this->dispatch('fixedQuantityMismatch');
                Log::info('Fixed quantity mismatches', ['count' => $result['fixed_count']]);
            } else {
                $this->error = 'No quantity mismatch found or nothing to fix.';
            }
        } catch (\Exception $e) {
            $this->error = 'Error fixing quantity mismatch: ' . $e->getMessage();
            Log::error('Exception fixing quantity mismatch', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Fix semua conversion mismatch
     */
    public function fixAllConversionMismatch()
    {
        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->fixConversionMismatchPurchases();
            $this->logs = array_merge($this->logs, $result['logs'] ?? []);

            if (($result['fixed_count'] ?? 0) > 0) {
                $this->error = null;
                $this->dispatch('fixedConversionMismatch');
                Log::info('Fixed conversion mismatches', ['count' => $result['fixed_count']]);
            } else {
                $this->error = 'No conversion mismatch found or nothing to fix.';
            }
        } catch (\Exception $e) {
            $this->error = 'Error fixing conversion mismatch: ' . $e->getMessage();
            Log::error('Exception fixing conversion mismatch', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Fix semua mutation quantity mismatch
     */
    public function fixAllMutationQuantityMismatch()
    {
        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->fixMutationQuantityMismatchStocks();
            $this->logs = array_merge($this->logs, $result['logs'] ?? []);

            if (($result['fixed_count'] ?? 0) > 0) {
                $this->error = null;
                $this->dispatch('fixedMutationQuantityMismatch');
                Log::info('Fixed mutation quantity mismatches', ['count' => $result['fixed_count']]);
            } else {
                $this->error = 'No mutation quantity mismatch found or nothing to fix.';
            }
        } catch (\Exception $e) {
            $this->error = 'Error fixing mutation quantity mismatch: ' . $e->getMessage();
            Log::error('Exception fixing mutation quantity mismatch', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Preview CurrentLivestock changes
     */
    public function previewCurrentLivestockChanges()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->showPreview = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->previewCurrentLivestockChanges();

            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success'] ?? false) {
                $this->previewData = $result['preview'] ?? [];
                if (count($this->previewData) > 0) {
                    $this->showPreview = true;
                    Log::info('CurrentLivestock preview generated', ['changes' => count($this->previewData)]);
                } else {
                    $this->error = 'No CurrentLivestock changes to preview.';
                }
            } else {
                $this->error = $result['error'] ?? 'Failed to generate CurrentLivestock preview.';
            }
        } catch (\Exception $e) {
            $this->error = 'Error previewing CurrentLivestock changes: ' . $e->getMessage();
            Log::error('Exception previewing CurrentLivestock changes', ['error' => $e->getMessage()]);
        }

        $this->isRunning = false;
    }

    /**
     * Fix missing CurrentLivestock records
     */
    public function fixMissingCurrentLivestock()
    {
        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->fixMissingCurrentLivestock();

            if (is_object($result)) {
                $result = (array) $result;
            }

            $this->logs = array_merge($this->logs, $result['logs'] ?? []);

            if (($result['fixed_count'] ?? 0) > 0 || ($result['removed_count'] ?? 0) > 0) {
                $this->error = null;
                $this->showPreview = false;
                $this->previewData = [];
                $this->dispatch('fixedMissingCurrentLivestock');
                Log::info('Fixed CurrentLivestock records', [
                    'fixed_count' => $result['fixed_count'] ?? 0,
                    'removed_count' => $result['removed_count'] ?? 0
                ]);
            } else {
                $this->error = 'No missing CurrentLivestock found or nothing to fix.';
            }
        } catch (\Exception $e) {
            $this->error = 'Error fixing missing CurrentLivestock: ' . $e->getMessage();
            Log::error('Exception fixing missing CurrentLivestock', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Preview perubahan sebelum fix
     */
    public function previewChanges()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->previewData = [];
        $this->showPreview = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->previewChanges();

            // Ensure result is array
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success'] ?? false) {
                $this->previewData = $result['preview_data'] ?? [];
                if (count($this->previewData) > 0) {
                    $this->showPreview = true;
                } else {
                    $this->error = 'No changes to preview.';
                }
            } else {
                $this->error = $result['error'] ?? 'Failed to get preview data.';
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LivestockDataIntegrity::previewChanges failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Apply semua perubahan
     */
    public function applyChanges()
    {
        $this->isRunning = true;
        $this->error = null;

        try {
            $service = new LivestockDataIntegrityService();

            // Fix empty source_type/source_id in bulk first
            $service->fixEmptySourceBatches();

            // Apply all other fixes
            $result = $service->fixQuantityMismatchStocks();
            if (is_object($result)) {
                $result = (array) $result;
            }
            $this->logs = array_merge($this->logs, $result['logs'] ?? []);

            $result = $service->fixConversionMismatchPurchases();
            if (is_object($result)) {
                $result = (array) $result;
            }
            $this->logs = array_merge($this->logs, $result['logs'] ?? []);

            $result = $service->fixMutationQuantityMismatchStocks();
            if (is_object($result)) {
                $result = (array) $result;
            }
            $this->logs = array_merge($this->logs, $result['logs'] ?? []);

            // Fix missing CurrentLivestock
            $result = $service->fixMissingCurrentLivestock();
            if (is_object($result)) {
                $result = (array) $result;
            }
            $this->logs = array_merge($this->logs, $result['logs'] ?? []);

            $this->showPreview = false;
            $this->previewData = [];
            $this->dispatch('changesApplied');
            Log::info('Successfully applied all changes');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LivestockDataIntegrity::applyChanges failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Load audit trail untuk model tertentu
     */
    public function loadAuditTrail($modelType, $modelId)
    {
        try {
            Log::info('LoadAuditTrail', ['modelType' => $modelType, 'modelId' => $modelId]);
            $this->selectedAuditModelType = $modelType;
            $this->selectedAuditModelId = $modelId;
            $this->auditTrails = \App\Models\DataAuditTrail::where('model_type', $modelType)
                ->where('model_id', $modelId)
                ->orderByDesc('created_at')
                ->get()
                ->toArray(); // Convert to array to fix the error

            Log::info('AuditTrailResult', [
                'count' => count($this->auditTrails),
                'ids' => array_column($this->auditTrails, 'id')
            ]);
            $this->showAuditTrail = true;
        } catch (\Exception $e) {
            $this->error = 'Error loading audit trail: ' . $e->getMessage();
            Log::error('Exception loading audit trail', [
                'error' => $e->getMessage(),
                'model_type' => $modelType,
                'model_id' => $modelId
            ]);
        }
    }

    /**
     * Hide audit trail
     */
    public function hideAuditTrail()
    {
        $this->showAuditTrail = false;
        $this->auditTrails = [];
        $this->selectedAuditModelType = null;
        $this->selectedAuditModelId = null;
    }

    /**
     * Rollback audit trail
     */
    public function rollbackAudit($auditId)
    {
        try {
            $service = new LivestockDataIntegrityService();
            $service->rollback($auditId);
            $this->logs = array_merge($this->logs, $service->getLogs());
            $this->dispatch('rollbackSuccess');
            Log::info('Successfully rolled back audit', ['audit_id' => $auditId]);

            // Reload audit trail after rollback
            if ($this->selectedAuditModelType && $this->selectedAuditModelId) {
                $this->loadAuditTrail($this->selectedAuditModelType, $this->selectedAuditModelId);
            }
        } catch (\Exception $e) {
            $this->error = 'Error during rollback: ' . $e->getMessage();
            Log::error('Exception during rollback', [
                'error' => $e->getMessage(),
                'audit_id' => $auditId
            ]);
        }
    }

    /**
     * Fix single invalid record
     */
    public function fixSingleInvalidRecord($batchId)
    {
        $this->isRunning = true;
        $this->error = null;

        try {
            $batch = \App\Models\LivestockBatch::find($batchId);
            if (!$batch) {
                $this->logs[] = [
                    'type' => 'fix_single_error',
                    'message' => "Batch/Stock with ID $batchId not found.",
                    'data' => ['id' => $batchId],
                ];
                $this->isRunning = false;
                return;
            }

            $fixed = false;

            // Try from purchase item
            if ($batch->livestock_purchase_item_id) {
                $purchaseItem = DB::table('livestock_purchase_items')
                    ->join('livestock_purchases', 'livestock_purchase_items.livestock_purchase_id', '=', 'livestock_purchases.id')
                    ->where('livestock_purchase_items.id', $batch->livestock_purchase_item_id)
                    ->whereNull('livestock_purchases.deleted_at')
                    ->first();

                if ($purchaseItem) {
                    $batch->source_type = 'purchase';
                    $batch->source_id = $purchaseItem->livestock_purchase_id;
                    $batch->save();
                    $fixed = true;
                }
            }

            // Try from mutation if batch has mutation_id
            if (!$fixed && isset($batch->mutation_id) && $batch->mutation_id) {
                $mutation = \App\Models\Mutation::where('id', $batch->mutation_id)->whereNull('deleted_at')->first();
                if ($mutation) {
                    $batch->source_type = 'mutation';
                    $batch->source_id = $mutation->id;
                    $batch->save();
                    $fixed = true;
                }
            }

            if ($fixed) {
                $this->logs[] = [
                    'type' => 'fix_single_success',
                    'message' => "Berhasil memperbaiki batch ID $batchId.",
                    'data' => $batch->toArray(),
                ];
                Log::info('Successfully fixed single record', ['batch_id' => $batchId]);
            } else {
                $this->logs[] = [
                    'type' => 'fix_single_failed',
                    'message' => "Tidak bisa memperbaiki batch ID $batchId secara otomatis. Silakan cek data sumber (purchase/mutation) di admin.",
                    'data' => $batch->toArray(),
                ];
                Log::warning('Failed to fix single record', ['batch_id' => $batchId]);
            }

            $this->previewInvalidData();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('Exception fixing single record', [
                'error' => $e->getMessage(),
                'batch_id' => $batchId
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Preview purchase item batch mismatches
     */
    public function previewPurchaseItemBatchMismatches()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->previewData = [];
        $this->showPreview = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->previewPurchaseItemBatchChanges();

            // Ensure result is array
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success'] ?? false) {
                $this->previewData = $result['preview_data'] ?? [];
                if (count($this->previewData) > 0) {
                    $this->showPreview = true;
                } else {
                    $this->error = 'No purchase item and batch mismatches found.';
                }
            } else {
                $this->error = $result['error'] ?? 'Failed to get preview data.';
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LivestockDataIntegrity::previewPurchaseItemBatchMismatches failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Fix purchase item batch mismatches
     */
    public function fixPurchaseItemBatchMismatches()
    {
        $this->isRunning = true;
        $this->error = null;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->fixPurchaseItemBatchMismatches();

            // Ensure result is array
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success'] ?? false) {
                $this->logs = array_merge($this->logs, $result['logs'] ?? []);
                $this->showPreview = false;
                $this->previewData = [];
                $this->dispatch('purchaseItemBatchMismatchesFixed');
                Log::info('Fixed purchase item batch mismatches');
            } else {
                $this->error = $result['error'] ?? 'Failed to fix mismatches.';
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LivestockDataIntegrity::fixPurchaseItemBatchMismatches failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Check price data integrity issues
     */
    public function checkPriceDataIntegrity()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->logs = [];

        try {
            $service = new LivestockDataIntegrityService();

            // Run price integrity check (which calls checkPriceDataIntegrity internally)
            $result = $service->previewInvalidLivestockData();

            if ($result['success'] ?? false) {
                // Filter logs to show only price-related issues
                $priceRelatedTypes = [
                    'price_data_missing',
                    'price_calculation_mismatch',
                    'livestock_price_aggregation_issue'
                ];

                $allLogs = $result['logs'] ?? [];
                $priceLogs = array_filter($allLogs, function ($log) use ($priceRelatedTypes) {
                    return in_array($log['type'] ?? '', $priceRelatedTypes);
                });

                $this->logs = array_values($priceLogs);

                if (count($priceLogs) > 0) {
                    $this->dispatch('info', 'Found ' . count($priceLogs) . ' price data integrity issues.');
                } else {
                    $this->dispatch('success', 'No price data integrity issues found.');
                }
            } else {
                $this->error = $result['error'] ?? 'Unknown error occurred';
                $this->logs = $result['logs'] ?? [];
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LivestockDataIntegrity::checkPriceDataIntegrity failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Fix price data integrity issues
     */
    public function fixPriceDataIntegrity()
    {
        $this->isRunning = true;
        $this->error = null;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->fixPriceDataIntegrity();

            if ($result['success'] ?? false) {
                $this->logs = array_merge($this->logs, $result['logs'] ?? []);
                $fixedCount = $result['fixed_count'] ?? 0;

                if ($fixedCount > 0) {
                    $this->dispatch('success', "Successfully fixed {$fixedCount} price data integrity issues.");
                } else {
                    $this->dispatch('info', 'No price data integrity issues found to fix.');
                }
            } else {
                $this->error = $result['error'] ?? 'Unknown error occurred';
                $this->logs = array_merge($this->logs, $result['logs'] ?? []);
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('LivestockDataIntegrity::fixPriceDataIntegrity failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->isRunning = false;
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.data-integrity.livestock-data-integrity');
    }
}
