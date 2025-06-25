@props(['mutationSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">Feed Mutation</h4>
    {{-- Type (batch/fifo) --}}
    <div class="mb-3">
        <label class="form-label">Mutation Type</label>
        <select wire:model="mutationSettings.feed_mutation.type" class="form-select">
            <option value="batch">Batch</option>
            <option value="fifo">FIFO</option>
        </select>
    </div>
    {{-- Batch Settings --}}
    <div class="mb-3">
        <h5 class="mb-2">Batch Settings</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.feed_mutation.batch_settings.tracking_enabled">
            <label class="form-check-label">Enable Batch Tracking</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.feed_mutation.batch_settings.require_batch_number">
            <label class="form-check-label">Require Batch Number</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Batch Number Format</label>
            <input type="text" class="form-control"
                wire:model="mutationSettings.feed_mutation.batch_settings.batch_number_format">
        </div>
    </div>
    {{-- Validation Rules --}}
    <div class="mb-3">
        <h5 class="mb-2">Validation Rules</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.feed_mutation.validation_rules.require_quantity">
            <label class="form-check-label">Require Quantity</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.feed_mutation.validation_rules.allow_partial_mutation">
            <label class="form-check-label">Allow Partial Mutation</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Max Mutation Percentage</label>
            <input type="number" class="form-control"
                wire:model="mutationSettings.feed_mutation.validation_rules.max_mutation_percentage" min="0" max="100">
        </div>
    </div>
    {{-- Document Settings --}}
    <div class="mb-3">
        <h5 class="mb-2">Document Settings</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.feed_mutation.document_settings.require_do_number">
            <label class="form-check-label">Require DO Number</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.feed_mutation.document_settings.require_invoice">
            <label class="form-check-label">Require Invoice</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.feed_mutation.document_settings.require_receipt">
            <label class="form-check-label">Require Receipt</label>
        </div>
    </div>
</div>