@props(['reportingSettings', 'livewireComponent'])

{{-- Reporting Settings Component --}}
<div>
    <h4 class="mb-3">Reporting Settings</h4>

    {{-- General Settings --}}
    <div class="mb-7 pb-4 border-bottom">
        <h5 class="mb-3">General Settings</h5>
        <div class="row">
            <div class="col-md-6">
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

                <div class="mb-3">
                    <label class="form-label">Available Periods</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="daily"
                            wire:model="reportingSettings.available_periods">
                        <label class="form-check-label">Daily</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="weekly"
                            wire:model="reportingSettings.available_periods">
                        <label class="form-check-label">Weekly</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="monthly"
                            wire:model="reportingSettings.available_periods">
                        <label class="form-check-label">Monthly</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="quarterly"
                            wire:model="reportingSettings.available_periods">
                        <label class="form-check-label">Quarterly</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="yearly"
                            wire:model="reportingSettings.available_periods">
                        <label class="form-check-label">Yearly</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Export Formats</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="pdf"
                            wire:model="reportingSettings.export_formats">
                        <label class="form-check-label">PDF</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="excel"
                            wire:model="reportingSettings.export_formats">
                        <label class="form-check-label">Excel</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="csv"
                            wire:model="reportingSettings.export_formats">
                        <label class="form-check-label">CSV</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Retention Period (Days)</label>
                    <input type="number" class="form-control" wire:model="reportingSettings.retention_period" min="1"
                        max="3650">
                </div>
            </div>
        </div>
    </div>

    {{-- Auto Generate Settings --}}
    <div class="mb-7 pb-4 border-bottom">
        <h5 class="mb-3">Auto Generate Settings</h5>
        <div class="row">
            <div class="col-md-6">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox"
                        wire:model="reportingSettings.auto_generate.enabled">
                    <label class="form-check-label">Enable Auto Generate</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Schedule</label>
                    <select wire:model="reportingSettings.auto_generate.schedule" class="form-select">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Report Types --}}
    <div class="mb-7 pb-4 border-bottom">
        <h5 class="mb-3">Report Types</h5>

        {{-- Purchase Reports --}}
        <div class="mb-4">
            <h6>Purchase Reports</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="reportingSettings.reports.purchase.enabled">
                        <label class="form-check-label">Enable Purchase Reports</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Types</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="livestock"
                                wire:model="reportingSettings.reports.purchase.types">
                            <label class="form-check-label">Livestock</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="feed"
                                wire:model="reportingSettings.reports.purchase.types">
                            <label class="form-check-label">Feed</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="supply"
                                wire:model="reportingSettings.reports.purchase.types">
                            <label class="form-check-label">Supply</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Metrics</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="quantity"
                            wire:model="reportingSettings.reports.purchase.metrics">
                        <label class="form-check-label">Quantity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="value"
                            wire:model="reportingSettings.reports.purchase.metrics">
                        <label class="form-check-label">Value</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="frequency"
                            wire:model="reportingSettings.reports.purchase.metrics">
                        <label class="form-check-label">Frequency</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mutation Reports --}}
        <div class="mb-4">
            <h6>Mutation Reports</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="reportingSettings.reports.mutation.enabled">
                        <label class="form-check-label">Enable Mutation Reports</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Types</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="livestock"
                                wire:model="reportingSettings.reports.mutation.types">
                            <label class="form-check-label">Livestock</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="feed"
                                wire:model="reportingSettings.reports.mutation.types">
                            <label class="form-check-label">Feed</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="supply"
                                wire:model="reportingSettings.reports.mutation.types">
                            <label class="form-check-label">Supply</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Metrics</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="quantity"
                            wire:model="reportingSettings.reports.mutation.metrics">
                        <label class="form-check-label">Quantity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="value"
                            wire:model="reportingSettings.reports.mutation.metrics">
                        <label class="form-check-label">Value</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="frequency"
                            wire:model="reportingSettings.reports.mutation.metrics">
                        <label class="form-check-label">Frequency</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Usage Reports --}}
        <div class="mb-4">
            <h6>Usage Reports</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="reportingSettings.reports.usage.enabled">
                        <label class="form-check-label">Enable Usage Reports</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Types</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="livestock"
                                wire:model="reportingSettings.reports.usage.types">
                            <label class="form-check-label">Livestock</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="feed"
                                wire:model="reportingSettings.reports.usage.types">
                            <label class="form-check-label">Feed</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="supply"
                                wire:model="reportingSettings.reports.usage.types">
                            <label class="form-check-label">Supply</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Metrics</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="quantity"
                            wire:model="reportingSettings.reports.usage.metrics">
                        <label class="form-check-label">Quantity</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="value"
                            wire:model="reportingSettings.reports.usage.metrics">
                        <label class="form-check-label">Value</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="frequency"
                            wire:model="reportingSettings.reports.usage.metrics">
                        <label class="form-check-label">Frequency</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>