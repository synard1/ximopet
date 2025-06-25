@props(['mutationSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">Livestock Mutation</h4>
    {{-- Type (batch/fifo) --}}
    <div class="mb-3">
        <label class="form-label">Mutation Type</label>
        <select wire:model="mutationSettings.livestock_mutation.type" class="form-select">
            <option value="batch">Batch</option>
            <option value="fifo">FIFO</option>
        </select>
    </div>
    {{-- Batch Settings --}}
    <div class="mb-3">
        <h5 class="mb-2">Batch Settings</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.batch_settings.tracking_enabled">
            <label class="form-check-label">Enable Batch Tracking</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.batch_settings.require_batch_number">
            <label class="form-check-label">Require Batch Number</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.batch_settings.allow_multiple_batches">
            <label class="form-check-label">Allow Multiple Batches</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Batch Number Format</label>
            <input type="text" class="form-control"
                wire:model="mutationSettings.livestock_mutation.batch_settings.batch_number_format">
        </div>
    </div>
    {{-- FIFO Settings (only if type is fifo) --}}
    @if($mutationSettings['livestock_mutation']['type'] === 'fifo')
    <div class="mb-3">
        <h5 class="mb-2">FIFO Settings</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.fifo_settings.enabled">
            <label class="form-check-label">Enable FIFO</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.fifo_settings.track_age">
            <label class="form-check-label">Track Age</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Minimum Age (Days)</label>
            <input type="number" class="form-control"
                wire:model="mutationSettings.livestock_mutation.fifo_settings.min_age_days" min="0">
        </div>
        <div class="mb-2">
            <label class="form-label">Maximum Age (Days)</label>
            <input type="number" class="form-control"
                wire:model="mutationSettings.livestock_mutation.fifo_settings.max_age_days" min="0">
        </div>
    </div>
    @endif
    {{-- Validation Rules --}}
    <div class="mb-3">
        <h5 class="mb-2">Validation Rules</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.validation_rules.require_weight">
            <label class="form-check-label">Require Weight</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.validation_rules.require_quantity">
            <label class="form-check-label">Require Quantity</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.validation_rules.allow_partial_mutation">
            <label class="form-check-label">Allow Partial Mutation</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Max Mutation Percentage</label>
            <input type="number" class="form-control"
                wire:model="mutationSettings.livestock_mutation.validation_rules.max_mutation_percentage" min="0"
                max="100">
        </div>
    </div>
    {{-- Document Settings --}}
    <div class="mb-3">
        <h5 class="mb-2">Document Settings</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.document_settings.require_do_number">
            <label class="form-check-label">Require DO Number</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.document_settings.require_invoice">
            <label class="form-check-label">Require Invoice</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="mutationSettings.livestock_mutation.document_settings.require_receipt">
            <label class="form-check-label">Require Receipt</label>
        </div>
    </div>
</div>