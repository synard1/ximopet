<?php

namespace App\Livewire\DataIntegrity;

use Livewire\Component;
use App\Services\FeedDataIntegrityService;
use Illuminate\Support\Facades\Log;

class FeedDataIntegrity extends Component
{
    public $logs = [];
    public $isRunning = false;
    public $error = null;
    public $showConfirmation = false;
    public $invalidStocksCount = 0;
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
            $service = new FeedDataIntegrityService();
            $result = $service->previewInvalidFeedData();

            if ($result['success']) {
                $this->logs = $result['logs'];
                $this->invalidStocksCount = $result['invalid_stocks_count'];
                if ($this->invalidStocksCount > 0) {
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
            $service = new FeedDataIntegrityService();
            $result = $service->checkAndFixInvalidFeedData();

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
        $service = new \App\Services\FeedDataIntegrityService();
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
        $service = new \App\Services\FeedDataIntegrityService();
        return $service->canRestore($type, $sourceId);
    }

    public function restoreStock($type, $sourceId)
    {
        $service = new \App\Services\FeedDataIntegrityService();
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
        $service = new \App\Services\FeedDataIntegrityService();
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
        $service = new \App\Services\FeedDataIntegrityService();
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
        $service = new \App\Services\FeedDataIntegrityService();
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
            $service = new FeedDataIntegrityService();
            $result = $service->previewChanges();

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
            $service = new FeedDataIntegrityService();

            // Apply all fixes
            $result = $service->fixQuantityMismatchStocks();
            $this->logs = array_merge($this->logs, $result['logs']);

            $result = $service->fixConversionMismatchPurchases();
            $this->logs = array_merge($this->logs, $result['logs']);

            $result = $service->fixMutationQuantityMismatchStocks();
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
        $service = new \App\Services\FeedDataIntegrityService();
        $service->rollback($auditId);
        $this->logs = array_merge($this->logs, $service->getLogs());
        $this->dispatch('rollbackSuccess');
        // Reload audit trail after rollback
        if ($this->selectedAuditModelType && $this->selectedAuditModelId) {
            $this->loadAuditTrail($this->selectedAuditModelType, $this->selectedAuditModelId);
        }
    }

    public function render()
    {
        return view('livewire.data-integrity.feed-data-integrity');
    }
}
