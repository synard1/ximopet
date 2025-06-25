@props(['reportingSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">Reporting Settings</h4>
    {{-- Report Generation --}}
    <div class="mb-3">
        <h5 class="mb-2">Report Generation</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="reportingSettings.generation.enabled">
            <label class="form-check-label">Enable Report Generation</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="reportingSettings.generation.allow_custom_reports">
            <label class="form-check-label">Allow Custom Reports</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Default Report Format</label>
            <select class="form-select" wire:model="reportingSettings.generation.default_format">
                <option value="pdf">PDF</option>
                <option value="excel">Excel</option>
                <option value="csv">CSV</option>
            </select>
        </div>
    </div>

    {{-- Report Scheduling --}}
    <div class="mb-3">
        <h5 class="mb-2">Report Scheduling</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="reportingSettings.scheduling.enabled">
            <label class="form-check-label">Enable Report Scheduling</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="reportingSettings.scheduling.allow_recurring">
            <label class="form-check-label">Allow Recurring Reports</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Default Schedule Frequency</label>
            <select class="form-select" wire:model="reportingSettings.scheduling.default_frequency">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
        </div>
    </div>

    {{-- Report Delivery --}}
    <div class="mb-3">
        <h5 class="mb-2">Report Delivery</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="reportingSettings.delivery.enabled">
            <label class="form-check-label">Enable Report Delivery</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="reportingSettings.delivery.allow_email">
            <label class="form-check-label">Allow Email Delivery</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="reportingSettings.delivery.allow_download">
            <label class="form-check-label">Allow Direct Download</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Default Delivery Method</label>
            <select class="form-select" wire:model="reportingSettings.delivery.default_method">
                <option value="email">Email</option>
                <option value="download">Download</option>
            </select>
        </div>
    </div>

    {{-- Report Retention --}}
    <div class="mb-3">
        <h5 class="mb-2">Report Retention</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="reportingSettings.retention.enabled">
            <label class="form-check-label">Enable Report Retention</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Retention Period (days)</label>
            <input type="number" class="form-control" wire:model="reportingSettings.retention.period">
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="reportingSettings.retention.auto_delete">
            <label class="form-check-label">Auto Delete Expired Reports</label>
        </div>
    </div>
</div>