<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SupplyDataIntegrityService;
use Illuminate\Support\Facades\Log;

class SupplyDataIntegrity extends Component
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
    public $selectedCategories = [];
    public $availableCategories = [
        'stock_integrity' => 'Stock Integrity',
        'current_supply_integrity' => 'CurrentSupply Integrity',
        'purchase_integrity' => 'Purchase Integrity',
        'mutation_integrity' => 'Mutation Integrity',
        'usage_integrity' => 'Usage Integrity',
        'status_integrity' => 'Status Integrity',
        'master_data_integrity' => 'Master Data Integrity',
        'relationship_integrity' => 'Relationship Integrity'
    ];
    public $showCategorySelector = false;

    public function mount()
    {
        // Select all categories by default
        $this->selectedCategories = array_keys($this->availableCategories);
    }

    public function previewInvalidData()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->logs = [];
        $this->showConfirmation = false;

        try {
            $service = new SupplyDataIntegrityService();
            $result = $service->previewInvalidSupplyData($this->selectedCategories);

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
            Log::error('Error in SupplyDataIntegrity::previewInvalidData: ' . $e->getMessage());
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
            $service = new SupplyDataIntegrityService();
            $result = $service->checkAndFixInvalidSupplyData();

            if ($result['success']) {
                $this->logs = $result['logs'];
                $this->deletedStocksCount = $result['deleted_stocks_count'];
            } else {
                $this->error = $result['error'];
                $this->logs = $result['logs'];
            }
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('Error in SupplyDataIntegrity::runIntegrityCheck: ' . $e->getMessage());
        }

        $this->isRunning = false;
    }

    public function restoreRecord($type, $sourceId)
    {
        $service = new SupplyDataIntegrityService();
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
        $service = new SupplyDataIntegrityService();
        return $service->canRestore($type, $sourceId);
    }

    public function restoreStock($type, $sourceId)
    {
        $service = new SupplyDataIntegrityService();
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
        $service = new SupplyDataIntegrityService();
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
        $service = new SupplyDataIntegrityService();
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
        $service = new SupplyDataIntegrityService();
        $result = $service->fixMutationQuantityMismatchStocks();
        $this->logs = array_merge($this->logs, $result['logs']);
        if ($result['fixed_count'] > 0) {
            $this->error = null;
            $this->dispatch('fixedMutationQuantityMismatch');
        } else {
            $this->error = 'No mutation quantity mismatch found or nothing to fix.';
        }
    }

    public function fixAllCurrentSupplyMismatch()
    {
        $service = new SupplyDataIntegrityService();
        $result = $service->fixCurrentSupplyMismatch();
        $this->logs = array_merge($this->logs, $result['logs']);
        if ($result['fixed_count'] > 0) {
            $this->error = null;
            $this->dispatch('fixedCurrentSupplyMismatch');
        } else {
            $this->error = 'No current supply mismatch found or nothing to fix.';
        }
    }

    public function createMissingCurrentSupplyRecords()
    {
        $service = new SupplyDataIntegrityService();
        $result = $service->createMissingCurrentSupplyRecords();
        $this->logs = array_merge($this->logs, $result['logs']);
        if ($result['created_count'] > 0) {
            $this->error = null;
            $this->dispatch('createdMissingCurrentSupply');
        } else {
            $this->error = 'No missing current supply records found or nothing to create.';
        }
    }

    public function previewChanges()
    {
        $this->isRunning = true;
        $this->error = null;
        $this->previewData = [];
        $this->showPreview = false;

        try {
            $service = new SupplyDataIntegrityService();
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
            Log::error('Error in SupplyDataIntegrity::previewChanges: ' . $e->getMessage());
        }

        $this->isRunning = false;
    }

    public function applyChanges()
    {
        $this->isRunning = true;
        $this->error = null;

        try {
            $service = new SupplyDataIntegrityService();

            // Apply all fixes
            $result = $service->fixQuantityMismatchStocks();
            $this->logs = array_merge($this->logs, $result['logs']);

            $result = $service->fixConversionMismatchPurchases();
            $this->logs = array_merge($this->logs, $result['logs']);

            $result = $service->fixMutationQuantityMismatchStocks();
            $this->logs = array_merge($this->logs, $result['logs']);

            $result = $service->fixCurrentSupplyMismatch();
            $this->logs = array_merge($this->logs, $result['logs']);

            $this->showPreview = false;
            $this->previewData = [];
            $this->dispatch('changesApplied');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('Error in SupplyDataIntegrity::applyChanges: ' . $e->getMessage());
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
        Log::info('AuditTrailResult', ['count' => $this->auditTrails->count(), 'ids' => $this->auditTrails->pluck('id')]);
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
        $service = new SupplyDataIntegrityService();
        $service->rollback($auditId);
        $this->logs = array_merge($this->logs, $service->getLogs());
        $this->dispatch('rollbackSuccess');
        // Reload audit trail after rollback
        if ($this->selectedAuditModelType && $this->selectedAuditModelId) {
            $this->loadAuditTrail($this->selectedAuditModelType, $this->selectedAuditModelId);
        }
    }

    public function toggleCategorySelector()
    {
        $this->showCategorySelector = !$this->showCategorySelector;
    }

    public function selectAllCategories()
    {
        $this->selectedCategories = array_keys($this->availableCategories);
    }

    public function deselectAllCategories()
    {
        $this->selectedCategories = [];
    }

    public function render()
    {
        return view('livewire.supply-data-integrity');
    }
}
