@props(['purchasingSettings', 'templateConfig', 'activeConfig', 'livewireComponent'])
{{-- Livestock Purchase Settings Component --}}
@php
$template = $templateConfig['purchasing']['livestock_purchase'] ?? [];
$active = $activeConfig['purchasing']['livestock_purchase'] ?? [];
@endphp
<div>
    <h4 class="mb-3">Livestock Purchase</h4>
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.enabled">
                <label class="form-check-label">Enable Livestock Purchase</label>
            </div>
        </div>
    </div>
    <div class="row mb-5">
        @php
        $validationExists = isset($template['validation_rules']) && isset($active['validation_rules']);
        $validationEnabled = $validationExists &&
        ($purchasingSettings['livestock_purchase']['validation_rules']['enabled'] ?? false);
        @endphp
        @if($validationEnabled)
        <div class="col-md-6">
            <h5 class="mb-3">Validation Rules</h5>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.validation_rules.require_farm">
                <label class="form-check-label">Require Farm</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.validation_rules.require_kandang">
                <label class="form-check-label">Require Kandang</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.validation_rules.require_breed">
                <label class="form-check-label">Require Breed</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.validation_rules.require_supplier">
                <label class="form-check-label">Require Supplier</label>
            </div>
        </div>
        @elseif($validationExists)
        <div class="col-md-6">
            <h5 class="mb-3">Validation Rules</h5>
            <x-feature-badge label="Validation Rules" />
        </div>
        @endif

        @php
        $batchExists = isset($template['batch_settings']) && isset($active['batch_settings']);
        $batchEnabled = $batchExists && ($purchasingSettings['livestock_purchase']['batch_settings']['enabled'] ??
        false);
        $canShowMultipleBatch = isset($active['batch_settings']['allow_multiple_batches']);
        $isMultipleBatchEnabled =
        $purchasingSettings['livestock_purchase']['batch_settings']['allow_multiple_batches']['enabled'] ?? false;
        $depletionMethod = $purchasingSettings['livestock_purchase']['batch_settings']['depletion_method'] ?? 'fifo';
        $depletionConfig = $template['batch_settings'] ?? [];
        @endphp
        @if($batchEnabled)
        <div class="col-md-6">
            <h5 class="mb-3">Batch Settings</h5>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.batch_settings.tracking_enabled">
                <label class="form-check-label">Enable Batch Tracking</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.batch_settings.history_enabled">
                <label class="form-check-label">Enable Batch History</label>
            </div>
            @if($canShowMultipleBatch)
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.batch_settings.allow_multiple_batches.enabled">
                <label class="form-check-label">Allow Multiple Batches</label>
            </div>
            @if($isMultipleBatchEnabled)
            <div class="mb-2">
                <label class="form-label">Max Batches</label>
                <input type="number" class="form-control"
                    wire:model="purchasingSettings.livestock_purchase.batch_settings.allow_multiple_batches.max_batches"
                    min="1" max="10">
            </div>
            {{-- <div class="mb-2">
                <label class="form-label">Batch Number Format</label>
                <input type="text" class="form-control"
                    wire:model="purchasingSettings.livestock_purchase.batch_settings.allow_multiple_batches.batch_number_format">
            </div> --}}
            <div class="mb-2">
                <label class="form-label">Depletion Method</label>
                <select class="form-select"
                    wire:model="purchasingSettings.livestock_purchase.batch_settings.allow_multiple_batches.depletion_method">
                    @if(isset($template['batch_settings']['allow_multiple_batches']['depletion_method_fifo']['enabled'])
                    && $template['batch_settings']['allow_multiple_batches']['depletion_method_fifo']['enabled'])
                    <option value="fifo">FIFO (First In First Out)</option>
                    @endif
                    @if(isset($template['batch_settings']['allow_multiple_batches']['depletion_method_manual']['enabled'])
                    && $template['batch_settings']['allow_multiple_batches']['depletion_method_manual']['enabled'])
                    <option value="manual">Manual (User Select Batch)</option>
                    @endif
                </select>
            </div>
            @endif
            @endif
        </div>
        @elseif($batchExists)
        <div class="col-md-6">
            <h5 class="mb-3">Batch Settings</h5>
            <x-feature-badge label="Batch Settings" />
        </div>
        @endif
    </div>
    <div class="row mb-5">
        @php
        $docExists = isset($template['document_settings']) && isset($active['document_settings']) &&
        ($active['document_settings']['enabled'] ?? false);
        $docEnabled = $docExists && ($purchasingSettings['livestock_purchase']['document_settings']['enabled'] ??
        false);
        @endphp
        @if($docExists)
        <div class="col-md-6">
            <h5 class="mb-3">Document Settings</h5>
            @if($docEnabled)
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.document_settings.require_do_number">
                <label class="form-check-label">Require DO Number</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.document_settings.require_invoice">
                <label class="form-check-label">Require Invoice</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.document_settings.require_receipt">
                <label class="form-check-label">Require Receipt</label>
            </div>
            @else
            <x-feature-badge label="Document Settings" />
            @endif
        </div>
        @endif
        @php
        $approvalExists = isset($template['approval_settings']) && isset($active['approval_settings']) &&
        ($active['approval_settings']['enabled'] ?? false);
        $approvalEnabled = $approvalExists && ($purchasingSettings['livestock_purchase']['approval_settings']['enabled']
        ?? false);
        @endphp
        @if($approvalExists)
        <div class="col-md-6">
            <h5 class="mb-3">Approval Settings</h5>
            @if($approvalEnabled)
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.livestock_purchase.approval_settings.require_approval">
                <label class="form-check-label">Require Approval</label>
            </div>
            <div class="mb-2">
                <label class="form-label">Approval Levels</label>
                <input type="number" class="form-control"
                    wire:model="purchasingSettings.livestock_purchase.approval_settings.approval_levels" min="1"
                    max="5">
            </div>
            @else
            <x-feature-badge label="Approval Settings" />
            @endif
        </div>
        @endif
    </div>
</div>