@props(['usageSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">Feed Usage</h4>
    {{-- Enable --}}
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" wire:model="usageSettings.feed_usage.enabled">
        <label class="form-check-label">Enable Feed Usage</label>
    </div>
    {{-- Validation Rules --}}
    <div class="mb-3">
        <h5 class="mb-2">Validation Rules</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.validation_rules.require_farm">
            <label class="form-check-label">Require Farm</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.validation_rules.require_kandang">
            <label class="form-check-label">Require Kandang</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.validation_rules.require_feed">
            <label class="form-check-label">Require Feed</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.validation_rules.require_quantity">
            <label class="form-check-label">Require Quantity</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.validation_rules.require_unit">
            <label class="form-check-label">Require Unit</label>
        </div>
    </div>
    {{-- Batch Settings --}}
    <div class="mb-3">
        <h5 class="mb-2">Batch Settings</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.batch_settings.enabled">
            <label class="form-check-label">Enable Batch Tracking</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.batch_settings.require_batch_number">
            <label class="form-check-label">Require Batch Number</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.batch_settings.auto_generate_batch">
            <label class="form-check-label">Auto Generate Batch</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Batch Number Format</label>
            <input type="text" class="form-control"
                wire:model="usageSettings.feed_usage.batch_settings.batch_number_format">
        </div>
    </div>
    {{-- Document Settings --}}
    <div class="mb-3">
        <h5 class="mb-2">Document Settings</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.document_settings.require_do_number">
            <label class="form-check-label">Require DO Number</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.document_settings.require_invoice">
            <label class="form-check-label">Require Invoice</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="usageSettings.feed_usage.document_settings.require_receipt">
            <label class="form-check-label">Require Receipt</label>
        </div>
    </div>
</div>