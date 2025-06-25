@props(['reportingSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">Report Types</h4>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="reportingSettings.reports.purchase.enabled">
        <label class="form-check-label">Enable Purchase Reports</label>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="reportingSettings.reports.mutation.enabled">
        <label class="form-check-label">Enable Mutation Reports</label>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="reportingSettings.reports.usage.enabled">
        <label class="form-check-label">Enable Usage Reports</label>
    </div>
</div>