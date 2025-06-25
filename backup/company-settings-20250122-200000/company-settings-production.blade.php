@php
// Feature badge component for disabled features
if (!function_exists('featureBadge')) {
function featureBadge($label) {
return '<span class="badge bg-secondary fs-7">' . e($label) . ' - Coming Soon</span>';
}
}

// Method status badge component
if (!function_exists('methodStatusBadge')) {
function methodStatusBadge($status, $label) {
$badgeClass = match($status) {
'ready' => 'bg-success',
'development' => 'bg-warning',
'not_applicable' => 'bg-secondary',
default => 'bg-light text-dark'
};

return '<span class="badge ' . $badgeClass . ' fs-7">' . e($label) . '</span>';
}
}
@endphp

<div>
    {{-- Company Settings Production Version --}}

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-6">
        <div>
            <h1 class="page-heading d-flex text-dark fw-bold fs-2 flex-column justify-content-center my-0">
                Company Settings
            </h1>
            <p class="text-muted fs-6 mb-0">Configure company-wide settings and operational preferences</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-light btn-sm" wire:click="debug" title="View debug information">
                <i class="bi bi-bug"></i> Debug
            </button>
            <button type="button" class="btn btn-info btn-sm" wire:click="resetLoadingState"
                title="Reset component state">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Status Bar --}}
    <div class="card bg-light-primary mb-6">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3">
                        <div class="symbol-label bg-primary">
                            <i class="bi bi-gear text-white fs-4"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Configuration Status</div>
                        <div class="text-muted fs-7">
                            Settings: {{ count($settings) }} sections |
                            Methods: {{ count($availableMethods) }} types |
                            Company: {{ $company->name ?? 'N/A' }}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="badge badge-light-success fs-8">
                        <i class="bi bi-check-circle me-1"></i>Ready
                    </div>
                    <div class="text-muted fs-8 mt-1">{{ now()->format('H:i:s') }}</div>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="saveSettings">
        {{-- Purchasing Settings --}}
        @if(isset($purchasingSettings) && is_array($purchasingSettings))
        <div class="card mb-6">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-30px me-3">
                            <div class="symbol-label bg-success">
                                <i class="bi bi-cart text-white fs-6"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0">Purchasing Settings</h3>
                            <p class="text-muted fs-7 mb-0">Configure purchasing and procurement options</p>
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <div class="badge badge-light-success">{{ count($purchasingSettings) }} options</div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">
                                <span class="required">Feed Usage Method</span>
                                <span class="ms-1" data-bs-toggle="tooltip"
                                    title="Method for tracking feed usage in purchasing">
                                    <i class="bi bi-question-circle text-muted fs-7"></i>
                                </span>
                            </label>
                            <select class="form-select form-select-solid"
                                wire:model="purchasingSettings.feed_usage_method">
                                <option value="">Select Method</option>
                                <option value="fifo">FIFO (First In First Out)</option>
                                <option value="lifo">LIFO (Last In First Out) - Development</option>
                                <option value="manual">Manual Selection - Development</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">Auto Purchase Alerts</label>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="purchasingSettings.auto_alerts" id="purchase_alerts">
                                <label class="form-check-label" for="purchase_alerts">
                                    Enable automatic purchase alerts
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Livestock Settings --}}
        @if(isset($livestockSettings) && is_array($livestockSettings))
        <div class="card mb-6">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-30px me-3">
                            <div class="symbol-label bg-warning">
                                <i class="bi bi-house text-white fs-6"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0">Livestock Settings</h3>
                            <p class="text-muted fs-7 mb-0">Configure livestock management and tracking</p>
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <div class="badge badge-light-warning">{{ count($livestockSettings) }} options</div>
                </div>
            </div>
            <div class="card-body">
                {{-- Debug Info - Remove after testing --}}
                @if(app()->environment('local'))
                <div class="alert alert-info mb-4">
                    <strong>Debug Info:</strong>
                    Livestock Settings Count: {{ count($livestockSettings) }} |
                    Keys: {{ implode(', ', array_keys($livestockSettings)) }}
                </div>
                @endif

                {{-- Basic Settings Row --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">
                                <span class="required">Recording Method</span>
                                <span class="ms-1" data-bs-toggle="tooltip" title="Method for recording livestock data">
                                    <i class="bi bi-question-circle text-muted fs-7"></i>
                                </span>
                            </label>
                            <select class="form-select form-select-solid"
                                wire:model="livestockSettings.recording_method">
                                <option value="">Select Method</option>
                                <option value="batch">Batch Recording</option>
                                <option value="individual">Individual Recording</option>
                            </select>
                            @error('livestockSettings.recording_method')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">Mutation Method</label>
                            <select class="form-select form-select-solid"
                                wire:model="livestockSettings.mutation_method">
                                <option value="">Select Method</option>
                                <option value="fifo">FIFO (First In First Out)</option>
                                <option value="lifo">LIFO (Last In First Out) - Development</option>
                                <option value="manual">Manual Selection - Development</option>
                            </select>
                            @error('livestockSettings.mutation_method')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">Default Mortality Rate (%)</label>
                            <input type="number" class="form-control form-control-solid"
                                wire:model="livestockSettings.default_mortality_rate" placeholder="Enter mortality rate"
                                min="0" max="100" step="0.1">
                            @error('livestockSettings.default_mortality_rate')
                            <div class="text-danger fs-7 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">Batch Settings</label>
                            <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.allow_multiple_batches" id="multiple_batches">
                                <label class="form-check-label" for="multiple_batches">
                                    Allow Multiple Batches
                                </label>
                            </div>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.auto_generate_batch" id="auto_batch">
                                <label class="form-check-label" for="auto_batch">
                                    Auto Generate Batch Numbers
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tracking Options --}}
                <div class="separator separator-dashed my-6"></div>
                <h5 class="fw-bold text-dark mb-4">
                    <i class="bi bi-graph-up me-2"></i>Tracking Options
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="fv-row mb-5">
                            <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.weight_tracking" id="weight_tracking">
                                <label class="form-check-label" for="weight_tracking">
                                    <span class="fw-semibold">Weight Tracking</span>
                                    <span class="text-muted d-block fs-7">Track livestock weight changes</span>
                                </label>
                            </div>
                            <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.age_tracking" id="age_tracking">
                                <label class="form-check-label" for="age_tracking">
                                    <span class="fw-semibold">Age Tracking</span>
                                    <span class="text-muted d-block fs-7">Track livestock age progression</span>
                                </label>
                            </div>
                            <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.health_monitoring" id="health_monitoring">
                                <label class="form-check-label" for="health_monitoring">
                                    <span class="fw-semibold">Health Monitoring</span>
                                    <span class="text-muted d-block fs-7">Monitor livestock health status</span>
                                </label>
                            </div>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.vaccination_schedule" id="vaccination_schedule">
                                <label class="form-check-label" for="vaccination_schedule">
                                    <span class="fw-semibold">Vaccination Schedule</span>
                                    <span class="text-muted d-block fs-7">Track vaccination schedules</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-5">
                            <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.breeding_records" id="breeding_records">
                                <label class="form-check-label" for="breeding_records">
                                    <span class="fw-semibold">Breeding Records</span>
                                    <span class="text-muted d-block fs-7">Maintain breeding history</span>
                                </label>
                            </div>
                            <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.feed_conversion_tracking"
                                    id="feed_conversion_tracking">
                                <label class="form-check-label" for="feed_conversion_tracking">
                                    <span class="fw-semibold">Feed Conversion Tracking</span>
                                    <span class="text-muted d-block fs-7">Track feed conversion ratio (FCR)</span>
                                </label>
                            </div>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="livestockSettings.performance_metrics" id="performance_metrics">
                                <label class="form-check-label" for="performance_metrics">
                                    <span class="fw-semibold">Performance Metrics</span>
                                    <span class="text-muted d-block fs-7">Calculate performance indicators</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Available Methods Display --}}
                @if(!empty($availableMethods))
                <div class="separator separator-dashed my-6"></div>
                <h5 class="fw-bold text-dark mb-4">
                    <i class="bi bi-gear me-2"></i>Available Methods Configuration
                </h5>
                <div class="row">
                    @foreach($availableMethods as $type => $methods)
                    <div class="col-md-4 mb-4">
                        <div class="card card-bordered">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3">{{ ucwords(str_replace('_', ' ', $type)) }}</h6>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted fs-7">Available Methods</span>
                                    <span class="badge badge-light-primary">{{ count($methods) }}</span>
                                </div>
                                <div class="d-flex flex-column gap-1">
                                    @foreach($methods as $method)
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle text-success fs-8 me-2"></i>
                                        <span class="fs-8 text-muted">{{ strtoupper($method) }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Settings Summary --}}
                <div class="separator separator-dashed my-6"></div>
                <div class="d-flex justify-content-between align-items-center p-4 bg-light-info rounded">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Livestock Configuration Summary</h6>
                        <p class="text-muted fs-7 mb-0">
                            Recording: <span class="fw-semibold">{{ $livestockSettings['recording_method'] ?? 'Not Set'
                                }}</span> |
                            Mutation: <span class="fw-semibold">{{ $livestockSettings['mutation_method'] ?? 'Not Set'
                                }}</span> |
                            Mortality Rate: <span class="fw-semibold">{{ $livestockSettings['default_mortality_rate'] ??
                                0 }}%</span>
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="badge badge-light-warning">{{ count($livestockSettings) }} Options</div>
                    </div>
                </div>
            </div>
        </div>
        @else
        {{-- Debug: Show if livestock settings not found --}}
        @if(app()->environment('local'))
        <div class="card mb-6">
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Debug:</strong> Livestock settings not found or not array.
                    Type: {{ gettype($livestockSettings ?? 'undefined') }} |
                    Isset: {{ isset($livestockSettings) ? 'yes' : 'no' }} |
                    Is Array: {{ is_array($livestockSettings ?? null) ? 'yes' : 'no' }}
                </div>
            </div>
        </div>
        @endif
        @endif

        {{-- Other Settings Sections --}}
        @foreach(['mutation', 'usage', 'notification', 'reporting'] as $section)
        @if(isset($this->{$section . 'Settings'}) && is_array($this->{$section . 'Settings'}))
        <div class="card mb-6">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-30px me-3">
                            <div
                                class="symbol-label bg-{{ $section === 'mutation' ? 'info' : ($section === 'usage' ? 'primary' : ($section === 'notification' ? 'success' : 'secondary')) }}">
                                <i
                                    class="bi bi-{{ $section === 'mutation' ? 'arrow-repeat' : ($section === 'usage' ? 'graph-up' : ($section === 'notification' ? 'bell' : 'file-text')) }} text-white fs-6"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0">{{ ucwords($section) }} Settings</h3>
                            <p class="text-muted fs-7 mb-0">Configure {{ strtolower($section) }} related options</p>
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <div
                        class="badge badge-light-{{ $section === 'mutation' ? 'info' : ($section === 'usage' ? 'primary' : ($section === 'notification' ? 'success' : 'secondary')) }}">
                        {{ count($this->{$section . 'Settings'}) }} options
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">Feed Usage Method</label>
                            <select class="form-select form-select-solid"
                                wire:model="{{ $section }}Settings.feed_usage_method">
                                <option value="">Select Method</option>
                                <option value="fifo">FIFO (First In First Out)</option>
                                <option value="lifo">LIFO (Last In First Out) - Development</option>
                                <option value="manual">Manual Selection - Development</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">Enable {{ ucwords($section) }}</label>
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="{{ $section }}Settings.enabled" id="{{ $section }}_enabled">
                                <label class="form-check-label" for="{{ $section }}_enabled">
                                    Enable {{ strtolower($section) }} functionality
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endforeach

        {{-- Action Buttons --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted fs-7">
                        <i class="bi bi-info-circle me-1"></i>
                        Changes will be applied immediately after saving
                    </div>
                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-light" onclick="window.history.back()">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2"></i> Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Debug Panel (Collapsible) --}}
    <div class="card mt-6" x-data="{ debugOpen: false }">
        <div class="card-header border-0 pt-6 cursor-pointer" @click="debugOpen = !debugOpen">
            <div class="card-title">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-bug me-2"></i>Debug Information
                </h5>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-icon btn-light" @click="debugOpen = !debugOpen">
                    <i class="bi bi-chevron-down" x-show="!debugOpen"></i>
                    <i class="bi bi-chevron-up" x-show="debugOpen"></i>
                </button>
            </div>
        </div>
        <div class="card-body" x-show="debugOpen" x-transition>
            <div class="row">
                <div class="col-md-4">
                    <h6 class="fw-bold text-dark mb-3">Component Status</h6>
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-gray-100 gy-4">
                            <tbody>
                                <tr>
                                    <td class="fw-bold">Company ID</td>
                                    <td>{{ $company->id ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Loading State</td>
                                    <td>
                                        <span class="badge badge-light-{{ $isLoading ? 'warning' : 'success' }}">
                                            {{ $isLoading ? 'Loading' : 'Ready' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Settings Count</td>
                                    <td>{{ count($settings) }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Methods Count</td>
                                    <td>{{ count($availableMethods) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-dark mb-3">Settings Sections</h6>
                    <div class="d-flex flex-column gap-2">
                        @foreach(array_keys($settings) as $section)
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            <span class="fw-semibold">{{ ucwords($section) }}</span>
                            <span class="badge badge-light-primary ms-auto">
                                {{ count($this->{$section . 'Settings'} ?? []) }} options
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-dark mb-3">System Info</h6>
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-gray-100 gy-4">
                            <tbody>
                                <tr>
                                    <td class="fw-bold">Last Updated</td>
                                    <td>{{ now()->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Template</td>
                                    <td>Production</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Version</td>
                                    <td>1.0.0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    @push('scripts')
    <script>
        document.addEventListener('livewire:init', function () {
            console.log('🚀 Company Settings Production initialized');
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Success notifications
            Livewire.on('success', function (message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Settings Saved',
                    text: message,
                    buttonsStyling: false,
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    timer: 3000,
                    timerProgressBar: true
                });
            });
            
            // Error notifications
            Livewire.on('error', function (message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    buttonsStyling: false,
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            });

            // Form validation feedback
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.matches('[wire\\:submit]')) {
                    // Add loading state to submit button
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                        submitBtn.disabled = true;
                        
                        // Reset after 5 seconds as fallback
                        setTimeout(() => {
                            submitBtn.innerHTML = '<i class="bi bi-check2"></i> Save Settings';
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                }
            });
        });
    </script>
    @endpush

    {{-- Styles --}}
    @push('styles')
    <style>
        .card {
            transition: all 0.3s ease;
            border: 1px solid #E4E6EA;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .form-select:focus,
        .form-control:focus {
            border-color: #009EF7;
            box-shadow: 0 0 0 0.2rem rgba(0, 158, 247, 0.25);
        }

        .form-check-input:checked {
            background-color: #009EF7;
            border-color: #009EF7;
        }

        .badge {
            font-weight: 500;
        }

        .symbol-label {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .separator {
            border-bottom: 1px dashed #E4E6EA;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .table th,
        .table td {
            border-color: #E4E6EA;
        }
    </style>
    @endpush
</div>