@props(['purchasingSettings', 'templateConfig', 'activeConfig', 'livewireComponent'])
{{-- Supply Purchase Settings Component --}}
@php
$template = $templateConfig['purchasing']['supply_purchase'] ?? [];
$active = $activeConfig['purchasing']['supply_purchase'] ?? [];
@endphp
<div>
    <h4 class="mb-3">Supply Purchase</h4>
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" wire:model="purchasingSettings.supply_purchase.enabled">
                <label class="form-check-label">Enable Supply Purchase</label>
            </div>
        </div>
    </div>
    <div class="row mb-5">
        @php
        $validationExists = isset($template['validation_rules']) && isset($active['validation_rules']);
        $validationEnabled = $validationExists && ($purchasingSettings['supply_purchase']['validation_rules']['enabled']
        ?? false);
        @endphp
        @if($validationEnabled)
        <div class="col-md-6">
            <h5 class="mb-3">Validation Rules</h5>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.supply_purchase.validation_rules.require_supplier">
                <label class="form-check-label">Require Supplier</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.supply_purchase.validation_rules.require_price">
                <label class="form-check-label">Require Price</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.supply_purchase.validation_rules.require_quantity">
                <label class="form-check-label">Require Quantity</label>
            </div>
        </div>
        @elseif($validationExists)
        <div class="col-md-6">
            <h5 class="mb-3">Validation Rules</h5>
            <x-feature-badge label="Validation Rules" />
        </div>
        @endif
        <div class="col-md-6">
            <h5 class="mb-3">Batch Settings</h5>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.supply_purchase.batch_settings.enabled">
                <label class="form-check-label">Enable Batch Tracking</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.supply_purchase.batch_settings.require_batch_number">
                <label class="form-check-label">Require Batch Number</label>
            </div>
            @php
            $autoGenExists = isset($template['batch_settings']['auto_generate_batch']) &&
            isset($active['batch_settings']['auto_generate_batch']);
            $autoGenEnabled = $autoGenExists &&
            ($purchasingSettings['supply_purchase']['batch_settings']['auto_generate_batch']['enabled'] ?? false);
            @endphp
            @if($autoGenExists && $autoGenEnabled)
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" disabled checked>
                <label class="form-check-label">Auto Generate Batch (Enabled)</label>
            </div>
            @elseif($autoGenExists)
            <x-feature-badge label="Auto Generate Batch" />
            @endif
        </div>
    </div>
    <div class="row mb-5">
        @php
        $docExists = isset($template['document_settings']) && isset($active['document_settings']) &&
        ($active['document_settings']['enabled'] ?? false);
        $docEnabled = $docExists && ($purchasingSettings['supply_purchase']['document_settings']['enabled'] ?? false);
        @endphp
        @if($docExists)
        <div class="col-md-6">
            <h5 class="mb-3">Document Settings</h5>
            @if($docEnabled)
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.supply_purchase.document_settings.require_do_number">
                <label class="form-check-label">Require DO Number</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.supply_purchase.document_settings.require_invoice">
                <label class="form-check-label">Require Invoice</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox"
                    wire:model="purchasingSettings.supply_purchase.document_settings.require_receipt">
                <label class="form-check-label">Require Receipt</label>
            </div>
            @else
            <x-feature-badge label="Document Settings" />
            @endif
        </div>
        @endif
    </div>
</div>