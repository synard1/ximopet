@props(['livestockSettings', 'templateConfig', 'activeConfig', 'livewireComponent'])

@php
// Get livestock config from activeConfig
$livestockConfig = $activeConfig['livestock']['recording_method']['batch_settings'] ?? [];
$depletionMethods = $livestockConfig['depletion_methods'] ?? [];
$mutationMethods = $livestockConfig['mutation_methods'] ?? [];
$feedUsageMethods = $livestockConfig['feed_usage_methods'] ?? [];

// Check if multiple batches is allowed
$allowMultipleBatches = $livewireComponent->isLivestockMultipleBatchesAllowed();
$recordingType = $livewireComponent->getLivestockRecordingType();
$showMethods = $livewireComponent->shouldShowLivestockMethods();
$isRecordingTypeEditable = $livewireComponent->isLivestockRecordingTypeEditable();

// Helper function to get method status badge with better contrast
function getMethodStatusBadge($method, $config) {
$enabled = $config['enabled'] ?? false;
$status = $config['status'] ?? 'not_found';

if ($enabled && $status === 'ready') {
return '<span class="badge bg-success text-white fw-bold">Ready</span>';
} elseif ($status === 'development') {
return '<span class="badge bg-warning text-dark fw-bold">Development</span>';
} elseif ($status === 'not_applicable') {
return '<span class="badge bg-secondary text-white fw-bold">N/A</span>';
} else {
return '<span class="badge bg-light text-dark fw-bold border">Disabled</span>';
}
}

// Helper function to check if method is selectable
function isMethodSelectable($config) {
return ($config['enabled'] ?? false) && ($config['status'] ?? '') === 'ready';
}
@endphp

{{-- Enhanced Livestock Settings Component --}}
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
                    @if($isRecordingTypeEditable)
                    <select wire:model="livestockSettings.recording_method.type" class="form-select">
                        <option value="batch">Batch Recording</option>
                        <option value="total">Total Recording</option>
                    </select>
                    @else
                    <div class="form-control bg-light" style="cursor: not-allowed;">
                        {{ $recordingType === 'batch' ? 'Batch Recording' : 'Total Recording' }}
                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-lock me-1"></i>Automatically determined by "Allow Multiple Batches" setting
                        </small>
                    </div>
                    @endif
                    <div class="form-text">Choose how livestock records are tracked</div>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox"
                        wire:model.live="livestockSettings.recording_method.allow_multiple_batches"
                        id="allowMultipleBatches">
                    <label class="form-check-label fw-semibold" for="allowMultipleBatches">Allow Multiple
                        Batches</label>
                    <div class="form-text">Enable multiple batch management per livestock</div>
                </div>

                {{-- Business Logic Info --}}
                <div class="alert alert-{{ $allowMultipleBatches ? 'info' : 'warning' }} mt-3">
                    <i class="bi bi-{{ $allowMultipleBatches ? 'info-circle' : 'exclamation-triangle' }} me-2"></i>
                    <strong>Current Configuration:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Recording Type:</strong> {{ $recordingType === 'batch' ? 'Batch Recording' : 'Total
                            Recording' }} (Auto)</li>
                        <li><strong>Multiple Batches:</strong> {{ $allowMultipleBatches ? 'Enabled' : 'Disabled' }}</li>
                        <li><strong>Methods:</strong> {{ $showMethods ? 'Visible' : 'Hidden' }}</li>
                    </ul>
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
                    <hr class="my-2">
                    <small class="text-muted">
                        <strong>Auto Logic:</strong><br>
                        • Multiple Batches ON → Batch Recording<br>
                        • Multiple Batches OFF → Total Recording
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- Batch Settings (only show if multiple batches is enabled) --}}
    @if($showMethods)
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
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <strong><i class="bi bi-list-ul me-2"></i>Available Methods</strong>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                @foreach($depletionMethods as $method => $config)
                                <li class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                    <span class="fw-semibold text-dark">{{ strtoupper($method) }}</span>
                                    {!! getMethodStatusBadge($method, $config) !!}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
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
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <strong><i class="bi bi-list-ul me-2"></i>Available Methods</strong>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                @foreach($mutationMethods as $method => $config)
                                <li class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                    <span class="fw-semibold text-dark">{{ strtoupper($method) }}</span>
                                    {!! getMethodStatusBadge($method, $config) !!}
                                </li>
                                @endforeach
                            </ul>
                        </div>
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
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <strong><i class="bi bi-list-ul me-2"></i>Available Methods</strong>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                @foreach($feedUsageMethods as $method => $config)
                                <li class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                    <span class="fw-semibold text-dark">{{ strtoupper($method) }}</span>
                                    {!! getMethodStatusBadge($method, $config) !!}
                                </li>
                                @endforeach
                            </ul>
                        </div>
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
                        <label class="form-check-label fw-semibold">Track Individual Batches</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.recording_method.batch_settings.batch_tracking.track_batch_performance">
                        <label class="form-check-label fw-semibold">Track Batch Performance</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="livestockSettings.recording_method.batch_settings.batch_tracking.batch_aging">
                        <label class="form-check-label fw-semibold">Enable Batch Aging</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-4 border rounded bg-light-subtle">
                        <h6 class="fw-semibold text-dark">Batch Tracking Features:</h6>
                        <ul class="list-unstyled text-dark small">
                            <li><strong>Batch Tracking:</strong> Enable overall batch management</li>
                            <li><strong>Individual Batches:</strong> Track each batch separately</li>
                            <li><strong>Batch Performance:</strong> Monitor FCR, growth rates, etc.</li>
                            <li><strong>Batch Aging:</strong> Track age and lifecycle stages</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    {{-- Message when methods are hidden --}}
    <div class="mb-7 pb-4 border-bottom">
        <div class="alert alert-warning">
            <i class="bi bi-eye-slash me-2"></i>
            <strong>Batch Settings Hidden</strong>
            <p class="mb-0 mt-2">
                Batch settings and methods are hidden because "Allow Multiple Batches" is disabled.
                <br>
                <small class="text-muted">
                    Enable "Allow Multiple Batches" above to access depletion, mutation, and feed usage methods.
                </small>
            </p>
        </div>
    </div>
    @endif

    {{-- Status Legend --}}
    <div class="mb-7">
        <h5 class="mb-3">
            <i class="bi bi-info-circle text-primary me-2"></i>Status Legend
        </h5>
        <div class="d-flex flex-wrap gap-4">
            <div class="d-flex align-items-center">
                <span class="badge bg-success text-white me-2">Ready</span>
                <span class="text-dark">Method implemented and selectable</span>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge bg-warning text-dark me-2">Development</span>
                <span class="text-dark">Method under development (disabled)</span>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge bg-secondary text-white me-2">N/A</span>
                <span class="text-dark">Method not applicable (disabled)</span>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge bg-light text-dark border me-2">Disabled</span>
                <span class="text-dark">Method disabled (disabled)</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
    // Listen for livestock multiple batch changes
    Livewire.on('livestockMultipleBatchChanged', function (data) {
        console.log('Livestock multiple batch changed:', data);
        
        // Show notification
        if (data.allow_multiple_batches) {
            toastr.info('Switched to Batch Recording - Methods are now visible');
        } else {
            toastr.warning('Switched to Total Recording - Methods are now hidden');
        }
    });
});
</script>
@endpush