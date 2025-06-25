@props(['livestockSettings', 'templateConfig', 'activeConfig', 'livewireComponent'])

@php
// Get livestock config from activeConfig
$livestockConfig = $activeConfig['livestock']['recording_method']['batch_settings'] ?? [];
$depletionMethods = $livestockConfig['depletion_methods'] ?? [];
$mutationMethods = $livestockConfig['mutation_methods'] ?? [];
$feedUsageMethods = $livestockConfig['feed_usage_methods'] ?? [];

// Helper function to get method status badge
function getMethodStatusBadge($method, $config) {
$enabled = $config['enabled'] ?? false;
$status = $config['status'] ?? 'not_found';

if ($enabled && $status === 'ready') {
return '<span class="badge bg-success fs-7">Ready</span>';
} elseif ($status === 'development') {
return '<span class="badge bg-warning fs-7">Development</span>';
} elseif ($status === 'not_applicable') {
return '<span class="badge bg-secondary fs-7">N/A</span>';
} else {
return '<span class="badge bg-light text-dark fs-7">Disabled</span>';
}
}

// Helper function to check if method is selectable
function isMethodSelectable($config) {
return ($config['enabled'] ?? false) && ($config['status'] ?? '') === 'ready';
}
@endphp

{{-- Livestock Settings Component --}}
<div>
    {{-- Recording Method --}}
    <div class="mb-7 pb-4 border-bottom">
        <h4 class="mb-3">
            <i class="bi bi-gear text-primary me-2"></i>Recording Method
        </h4>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Recording Type</label>
                    <select wire:model="livestockSettings.recording_method.type" class="form-select">
                        <option value="batch">Batch Recording</option>
                        <option value="total">Total Recording</option>
                    </select>
                    <div class="form-text">Choose how livestock records are tracked</div>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.recording_method.allow_multiple_batches">
                    <label class="form-check-label fw-semibold">Allow Multiple Batches</label>
                    <div class="form-text">Enable multiple batch management per livestock</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Recording Type Guide:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Batch:</strong> Track livestock in groups/batches</li>
                        <li><strong>Total:</strong> Track livestock as total numbers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Batch Settings (only show if batch recording is selected) --}}
    @if($livestockSettings['recording_method']['type'] === 'batch')
    <div class="mb-7 pb-4 border-bottom">
        <h4 class="mb-3">
            <i class="bi bi-layers text-primary me-2"></i>Batch Settings
        </h4>

        {{-- Depletion Methods --}}
        <div class="mb-5">
            <h5 class="mb-3">
                <i class="bi bi-arrow-down-circle me-2"></i>Depletion Methods
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Default Depletion Method</label>
                    <select wire:model="livestockSettings.recording_method.batch_settings.depletion_method_default"
                        class="form-select mb-3">
                        @foreach($depletionMethods as $method => $config)
                        <option value="{{ $method }}" {{ !isMethodSelectable($config) ? 'disabled' : '' }}>
                            {{ strtoupper($method) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-light">
                        <strong>Available Methods:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($depletionMethods as $method => $config)
                            <li class="d-flex justify-content-between align-items-center">
                                <span>{{ strtoupper($method) }}</span>
                                {!! getMethodStatusBadge($method, $config) !!}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Depletion Method Configuration --}}
            @foreach($depletionMethods as $method => $config)
            @if(isMethodSelectable($config))
            <div class="card mb-3" x-data="{ open: false }">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">{{ strtoupper($method) }} Configuration</h6>
                        <button type="button" class="btn btn-sm btn-light" @click="open = !open">
                            <span x-show="!open">Configure</span>
                            <span x-show="open">Hide</span>
                        </button>
                    </div>
                </div>
                <div class="card-body" x-show="open" x-transition>
                    @if($method === 'fifo')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.recording_method.batch_settings.depletion_methods.fifo.track_age">
                                <label class="form-check-label">Track Age</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.recording_method.batch_settings.depletion_methods.fifo.auto_select">
                                <label class="form-check-label">Auto Select Batches</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.recording_method.batch_settings.depletion_methods.fifo.prefer_older_batches">
                                <label class="form-check-label">Prefer Older Batches</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Min Age Days</label>
                                <input type="number" class="form-control" min="0"
                                    wire:model="livestockSettings.recording_method.batch_settings.depletion_methods.fifo.min_age_days">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Max Age Days</label>
                                <input type="number" class="form-control" min="0"
                                    wire:model="livestockSettings.recording_method.batch_settings.depletion_methods.fifo.max_age_days">
                            </div>
                        </div>
                    </div>
                    @elseif($method === 'manual')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.recording_method.batch_settings.depletion_methods.manual.show_batch_details">
                                <label class="form-check-label">Show Batch Details</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.recording_method.batch_settings.depletion_methods.manual.require_selection">
                                <label class="form-check-label">Require Selection</label>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            @endforeach
        </div>

        {{-- Mutation Methods --}}
        <div class="mb-5">
            <h5 class="mb-3">
                <i class="bi bi-shuffle me-2"></i>Mutation Methods
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Default Mutation Method</label>
                    <select wire:model="livestockSettings.recording_method.batch_settings.mutation_method_default"
                        class="form-select mb-3">
                        @foreach($mutationMethods as $method => $config)
                        <option value="{{ $method }}" {{ !isMethodSelectable($config) ? 'disabled' : '' }}>
                            {{ strtoupper($method) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-light">
                        <strong>Available Methods:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($mutationMethods as $method => $config)
                            <li class="d-flex justify-content-between align-items-center">
                                <span>{{ strtoupper($method) }}</span>
                                {!! getMethodStatusBadge($method, $config) !!}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Feed Usage Methods --}}
        <div class="mb-5">
            <h5 class="mb-3">
                <i class="bi bi-egg me-2"></i>Feed Usage Methods
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Default Feed Usage Method</label>
                    <select wire:model="livestockSettings.recording_method.batch_settings.feed_usage_method_default"
                        class="form-select mb-3">
                        @foreach($feedUsageMethods as $method => $config)
                        <option value="{{ $method }}" {{ !isMethodSelectable($config) ? 'disabled' : '' }}>
                            {{ strtoupper($method) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-light">
                        <strong>Available Methods:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($feedUsageMethods as $method => $config)
                            <li class="d-flex justify-content-between align-items-center">
                                <span>{{ strtoupper($method) }}</span>
                                {!! getMethodStatusBadge($method, $config) !!}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Batch Tracking Settings --}}
        <div class="mb-5">
            <h5 class="mb-3">
                <i class="bi bi-list-check me-2"></i>Batch Tracking
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.recording_method.batch_settings.batch_tracking.enabled">
                        <label class="form-check-label fw-semibold">Enable Batch Tracking</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.recording_method.batch_settings.batch_tracking.track_individual_batches">
                        <label class="form-check-label">Track Individual Batches</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.recording_method.batch_settings.batch_tracking.track_batch_performance">
                        <label class="form-check-label">Track Batch Performance</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.recording_method.batch_settings.batch_tracking.batch_aging">
                        <label class="form-check-label">Enable Batch Aging</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Validation Rules --}}
        <div class="mb-5">
            <h5 class="mb-3">
                <i class="bi bi-shield-check me-2"></i>Validation Rules
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.recording_method.batch_settings.validation_rules.require_batch_selection">
                        <label class="form-check-label">Require Batch Selection</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.recording_method.batch_settings.validation_rules.allow_partial_batch_usage">
                        <label class="form-check-label">Allow Partial Batch Usage</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Max Batches per Recording</label>
                        <input type="number" class="form-control" min="1" max="10"
                            wire:model="livestockSettings.recording_method.batch_settings.validation_rules.max_batches_per_recording">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Min Batch Quantity</label>
                        <input type="number" class="form-control" min="1"
                            wire:model="livestockSettings.recording_method.batch_settings.validation_rules.min_batch_quantity">
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Depletion Tracking --}}
    <div class="mb-7 pb-4 border-bottom">
        <h4 class="mb-3">
            <i class="bi bi-graph-down text-primary me-2"></i>Depletion Tracking
        </h4>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" wire:model="livestockSettings.depletion_tracking.enabled">
            <label class="form-check-label fw-semibold">Enable Depletion Tracking</label>
        </div>

        @if($livestockSettings['depletion_tracking']['enabled'] ?? false)
        {{-- Depletion Types --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="mb-3">
                    <h6>Mortality</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.mortality.enabled">
                        <label class="form-check-label">Enable Mortality Tracking</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.mortality.require_reason">
                        <label class="form-check-label">Require Reason</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.mortality.track_by_batch">
                        <label class="form-check-label">Track by Batch</label>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <h6>Sales</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.sales.enabled">
                        <label class="form-check-label">Enable Sales Tracking</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.sales.require_buyer_info">
                        <label class="form-check-label">Require Buyer Info</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.sales.track_by_batch">
                        <label class="form-check-label">Track by Batch</label>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <h6>Culling</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.culling.enabled">
                        <label class="form-check-label">Enable Culling Tracking</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.culling.require_reason">
                        <label class="form-check-label">Require Reason</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.depletion_tracking.types.culling.track_by_batch">
                        <label class="form-check-label">Track by Batch</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Restrictions --}}
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Max Depletion per Day per Batch</label>
                    <input type="number" class="form-control"
                        wire:model="livestockSettings.depletion_tracking.input_restrictions.max_depletion_per_day_per_batch"
                        min="1">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Minimum Interval (Minutes)</label>
                    <input type="number" class="form-control"
                        wire:model="livestockSettings.depletion_tracking.input_restrictions.min_interval_minutes"
                        min="0" max="1440">
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Weight Tracking --}}
    <div class="mb-7 pb-4 border-bottom">
        <h4 class="mb-3">
            <i class="bi bi-speedometer2 text-primary me-2"></i>Weight Tracking
        </h4>
        <div class="row">
            <div class="col-md-6">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.weight_tracking.enabled">
                    <label class="form-check-label fw-semibold">Enable Weight Tracking</label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Weight Unit</label>
                    <select wire:model="livestockSettings.weight_tracking.unit" class="form-select">
                        <option value="gram">Gram</option>
                        <option value="kg">Kilogram</option>
                        <option value="pound">Pound</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Precision (Decimal Places)</label>
                    <input type="number" class="form-control" wire:model="livestockSettings.weight_tracking.precision"
                        min="0" max="4">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.weight_tracking.weight_gain_calculation">
                    <label class="form-check-label">Calculate Weight Gain</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.weight_tracking.track_by_batch">
                    <label class="form-check-label">Track by Batch</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Performance Metrics --}}
    <div class="mb-7 pb-4 border-bottom">
        <h4 class="mb-3">
            <i class="bi bi-graph-up text-primary me-2"></i>Performance Metrics
        </h4>
        <div class="row">
            <div class="col-md-6">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.performance_metrics.enabled">
                    <label class="form-check-label fw-semibold">Enable Performance Metrics</label>
                </div>

                <h5 class="mb-3">Metrics to Track</h5>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.performance_metrics.metrics.fcr">
                    <label class="form-check-label">FCR (Feed Conversion Ratio)</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.performance_metrics.metrics.ip">
                    <label class="form-check-label">IP (Index Performance)</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.performance_metrics.metrics.adg">
                    <label class="form-check-label">ADG (Average Daily Gain)</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox"
                        wire:model="livestockSettings.performance_metrics.metrics.mortality_rate">
                    <label class="form-check-label">Mortality Rate</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Method Status Legend --}}
    <div class="alert alert-info">
        <h6><i class="bi bi-info-circle me-2"></i>Method Status Legend</h6>
        <div class="row">
            <div class="col-md-6">
                <ul class="mb-0">
                    <li><span class="badge bg-success fs-7">Ready</span> - Method is implemented and available for use
                    </li>
                    <li><span class="badge bg-warning fs-7">Development</span> - Method is under development</li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="mb-0">
                    <li><span class="badge bg-secondary fs-7">N/A</span> - Method is not applicable</li>
                    <li><span class="badge bg-light text-dark fs-7">Disabled</span> - Method is disabled</li>
                </ul>
            </div>
        </div>
    </div>
</div>