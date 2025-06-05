<?php

namespace App\Livewire\DataIntegrity;

use Livewire\Component;
use App\Services\LivestockDataIntegrityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LivestockDataIntegrity extends Component
{
    public $logs = [];
    public $isRunning = false;
    public $error = null;
    public $showConfirmation = false;
    public $invalidStocksCount = 0;
    public $invalidBatchesCount = 0;
    public $deletedStocksCount = 0;
    public $previewData = [];
    public $showPreview = false;
    public $auditTrails = [];
    public $showAuditTrail = false;
    public $selectedAuditModelType = null;
    public $selectedAuditModelId = null;

    public function previewInvalidData()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->logs = [];
        $this->showConfirmation = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->previewInvalidLivestockData();
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success']) {
                $this->logs = $result['logs'];
                $this->invalidStocksCount = $result['invalid_stocks_count'] ?? 0;
                $this->invalidBatchesCount = $result['invalid_batches_count'] ?? 0;
                if (($this->invalidStocksCount > 0) || ($this->invalidBatchesCount > 0)) {
                    $this->showConfirmation = true;
                }
            } else {
                $this->error = $result['error'];
                $this->logs = $result['logs'];
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->isRunning = false;
    }

    public function runIntegrityCheck()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->logs = [];
        $this->showConfirmation = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->checkAndFixInvalidLivestockData();
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success']) {
                $this->logs = $result['logs'];
                $this->deletedStocksCount = $result['deleted_stocks_count'];
            } else {
                $this->error = $result['error'];
                $this->logs = $result['logs'];
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->isRunning = false;
    }

    public function restoreRecord($type, $sourceId)
    {
        $service = new \App\Services\LivestockDataIntegrityService();
        $restored = $service->restoreRelatedRecord($type, $sourceId);
        $this->logs = array_merge($this->logs, $service->getLogs());
        if ($restored) {
            $this->error = null;
            $this->dispatch('restoredSuccessfully');
        } else {
            $this->error = 'Restore failed or record is not restorable.';
        }
    }

    public function canRestore($type, $sourceId)
    {
        $service = new \App\Services\LivestockDataIntegrityService();
        return $service->canRestore($type, $sourceId);
    }

    public function restoreStock($type, $sourceId)
    {
        $service = new \App\Services\LivestockDataIntegrityService();
        $restored = $service->restoreMissingStock($type, $sourceId);
        $this->logs = array_merge($this->logs, $service->getLogs());
        if ($restored) {
            $this->error = null;
            $this->dispatch('restoredStockSuccessfully');
        } else {
            $this->error = 'Restore stock failed or stock already exists.';
        }
    }

    public function fixAllQuantityMismatch()
    {
        $service = new \App\Services\LivestockDataIntegrityService();
        $result = $service->fixQuantityMismatchStocks();
        $this->logs = array_merge($this->logs, $result['logs']);
        if ($result['fixed_count'] > 0) {
            $this->error = null;
            $this->dispatch('fixedQuantityMismatch');
        } else {
            $this->error = 'No quantity mismatch found or nothing to fix.';
        }
    }

    public function fixAllConversionMismatch()
    {
        $service = new \App\Services\LivestockDataIntegrityService();
        $result = $service->fixConversionMismatchPurchases();
        $this->logs = array_merge($this->logs, $result['logs']);
        if ($result['fixed_count'] > 0) {
            $this->error = null;
            $this->dispatch('fixedConversionMismatch');
        } else {
            $this->error = 'No conversion mismatch found or nothing to fix.';
        }
    }

    public function fixAllMutationQuantityMismatch()
    {
        $service = new \App\Services\LivestockDataIntegrityService();
        $result = $service->fixMutationQuantityMismatchStocks();
        $this->logs = array_merge($this->logs, $result['logs']);
        if ($result['fixed_count'] > 0) {
            $this->error = null;
            $this->dispatch('fixedMutationQuantityMismatch');
        } else {
            $this->error = 'No mutation quantity mismatch found or nothing to fix.';
        }
    }

    public function previewChanges()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->previewData = [];
        $this->showPreview = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->previewChanges();
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success']) {
                $this->previewData = $result['preview_data'];
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
        }

        $this->isRunning = false;
    }

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
            $this->logs = array_merge($this->logs, $result['logs']);

            $result = $service->fixConversionMismatchPurchases();
            if (is_object($result)) {
                $result = (array) $result;
            }
            $this->logs = array_merge($this->logs, $result['logs']);

            $result = $service->fixMutationQuantityMismatchStocks();
            if (is_object($result)) {
                $result = (array) $result;
            }
            $this->logs = array_merge($this->logs, $result['logs']);

            $this->showPreview = false;
            $this->previewData = [];
            $this->dispatch('changesApplied');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->isRunning = false;
    }

    public function loadAuditTrail($modelType, $modelId)
    {
        Log::info('LoadAuditTrail', ['modelType' => $modelType, 'modelId' => $modelId]);
        $this->selectedAuditModelType = $modelType;
        $this->selectedAuditModelId = $modelId;
        $this->auditTrails = \App\Models\DataAuditTrail::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderByDesc('created_at')
            ->get();
        Log::info('AuditTrailResult', ['count' => count($this->auditTrails), 'ids' => $this->auditTrails->pluck('id')->toArray()]);
        $this->showAuditTrail = true;
    }

    public function hideAuditTrail()
    {
        $this->showAuditTrail = false;
        $this->auditTrails = [];
        $this->selectedAuditModelType = null;
        $this->selectedAuditModelId = null;
    }

    public function rollbackAudit($auditId)
    {
        $service = new \App\Services\LivestockDataIntegrityService();
        $service->rollback($auditId);
        $this->logs = array_merge($this->logs, $service->getLogs());
        $this->dispatch('rollbackSuccess');
        // Reload audit trail after rollback
        if ($this->selectedAuditModelType && $this->selectedAuditModelId) {
            $this->loadAuditTrail($this->selectedAuditModelType, $this->selectedAuditModelId);
        }
    }

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
            } else {
                $this->logs[] = [
                    'type' => 'fix_single_failed',
                    'message' => "Tidak bisa memperbaiki batch ID $batchId secara otomatis. Silakan cek data sumber (purchase/mutation) di admin.",
                    'data' => $batch->toArray(),
                ];
            }
            $this->previewInvalidData();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
        $this->isRunning = false;
    }

    public function previewPurchaseItemBatchMismatches()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->previewData = [];
        $this->showPreview = false;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->previewPurchaseItemBatchChanges();
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success']) {
                $this->previewData = $result['preview_data'];
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
        }

        $this->isRunning = false;
    }

    public function fixPurchaseItemBatchMismatches()
    {
        $this->isRunning = true;
        $this->error = null;

        try {
            $service = new LivestockDataIntegrityService();
            $result = $service->fixPurchaseItemBatchMismatches();
            if (is_object($result)) {
                $result = (array) $result;
            }

            if ($result['success']) {
                $this->logs = array_merge($this->logs, $result['logs']);
                $this->showPreview = false;
                $this->previewData = [];
                $this->dispatch('purchaseItemBatchMismatchesFixed');
            } else {
                $this->error = $result['error'] ?? 'Failed to fix mismatches.';
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->isRunning = false;
    }

    public function render()
    {
        return view('livewire.data-integrity.livestock-data-integrity');
    }
}
