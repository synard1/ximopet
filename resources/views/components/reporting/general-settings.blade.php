@props(['reportingSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">General Reporting Settings</h4>
    <div class="mb-3">
        <label class="form-label">Default Period</label>
        <select wire:model="reportingSettings.default_period" class="form-select">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="yearly">Yearly</option>
        </select>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="reportingSettings.auto_generate.enabled">
        <label class="form-check-label">Enable Auto Generate Reports</label>
    </div>
</div>