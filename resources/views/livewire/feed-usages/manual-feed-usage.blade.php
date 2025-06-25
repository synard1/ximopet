<!-- Manual Feed Usage Modal -->
<div class="modal fade" id="manual-feed-usage-modal" tabindex="-1" aria-labelledby="manualFeedUsageModalLabel"
    aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="manualFeedUsageModalLabel">
                    <i class="ki-duotone ki-nutrition fs-2 me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                        <span class="path6"></span>
                        <span class="path7"></span>
                        <span class="path8"></span>
                        <span class="path9"></span>
                        <span class="path10"></span>
                        <span class="path11"></span>
                        <span class="path12"></span>
                    </i>
                    Manual Feed Usage
                    @if($livestock)
                    - {{ $livestock->name }}
                    @endif
                    @if($isEditMode)
                    <span class="badge badge-warning ms-2">
                        <i class="ki-duotone ki-pencil fs-6 me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        EDIT MODE
                    </span>
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    wire:click="closeModal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- Progress Steps -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="stepper stepper-pills stepper-column d-flex flex-stack flex-wrap flex-md-row">
                            <!-- Step 1: Batch Selection -->
                            <div class="stepper-item mx-8 flex-wrapper {{ $step >= 1 ? 'current' : '' }}">
                                <div class="stepper-wrapper d-flex align-items-center">
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="stepper-check fas fa-check"></i>
                                        <span class="stepper-number">1</span>
                                    </div>
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">Batch Selection</h3>
                                        <div class="stepper-desc">Choose livestock batch</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Stock Selection -->
                            <div class="stepper-item mx-8 flex-wrapper {{ $step >= 2 ? 'current' : '' }}">
                                <div class="stepper-wrapper d-flex align-items-center">
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="stepper-check fas fa-check"></i>
                                        <span class="stepper-number">2</span>
                                    </div>
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">Stock Selection</h3>
                                        <div class="stepper-desc">Choose feed stocks</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Preview -->
                            <div class="stepper-item mx-8 flex-wrapper {{ $step >= 3 ? 'current' : '' }}">
                                <div class="stepper-wrapper d-flex align-items-center">
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="stepper-check fas fa-check"></i>
                                        <span class="stepper-number">3</span>
                                    </div>
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">Preview</h3>
                                        <div class="stepper-desc">Review usage details</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Complete -->
                            <div class="stepper-item mx-8 flex-wrapper {{ $step >= 4 ? 'current' : '' }}">
                                <div class="stepper-wrapper d-flex align-items-center">
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="stepper-check fas fa-check"></i>
                                        <span class="stepper-number">4</span>
                                    </div>
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">Complete</h3>
                                        <div class="stepper-desc">Usage processed</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Mode Alert -->
                @if($isEditMode)
                <div class="alert alert-warning d-flex align-items-center p-5 mb-6">
                    <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div class="d-flex flex-column flex-grow-1">
                        <h4 class="mb-1 text-warning">Edit Mode Active</h4>
                        <span>You are editing existing feed usage data for {{ $usageDate }}. All changes will update the
                            existing records.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-light-warning" wire:click="cancelEditMode">
                        <i class="ki-duotone ki-cross fs-6 me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Cancel Edit
                    </button>
                </div>
                @endif

                <!-- Error Messages -->
                @if (!empty($errors))
                <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                    <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-danger">Validation Errors</h4>
                        @foreach ($errors as $field => $messages)
                        @if (is_array($messages))
                        @foreach ($messages as $message)
                        <span>{{ $message }}</span><br>
                        @endforeach
                        @else
                        <span>{{ $messages }}</span><br>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Loading State -->
                @if ($isLoading)
                <div class="d-flex justify-content-center align-items-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-3">Processing...</span>
                </div>
                @endif

                <!-- No Livestock Selected State -->
                @if (!$livestock && !$isLoading)
                <div class="text-center py-10">
                    <i class="ki-duotone ki-information-2 fs-3x text-muted mb-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <h3 class="text-muted">No Livestock Selected</h3>
                    <p class="text-muted">Please select a livestock to continue.</p>
                </div>
                @endif

                <!-- Step 1: Batch Selection -->
                @if ($step === 1 && $livestock)
                <div class="row">
                    <div class="col-12">
                        <div class="card card-flush">
                            <div class="card-header">
                                <div class="card-title">
                                    <h3 class="fw-bold text-gray-800">
                                        <i class="ki-duotone ki-abstract-39 fs-2 me-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Select Livestock Batch
                                    </h3>
                                </div>
                            </div>
                            <div class="card-body pt-5">
                                @if (!empty($availableBatches))
                                <div class="row">
                                    @foreach ($availableBatches as $batch)
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card card-flush border border-gray-300 cursor-pointer hover-elevate-up"
                                            wire:click="selectBatch('{{ $batch['batch_id'] }}')">
                                            <div class="card-body p-5">
                                                <div class="d-flex align-items-center mb-3">
                                                    <i class="ki-duotone ki-abstract-39 fs-2x text-primary me-3">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                    <div class="flex-grow-1">
                                                        <h4 class="fw-bold text-gray-800 mb-1">
                                                            {{ $batch['batch_name'] }}
                                                        </h4>
                                                        <div class="text-muted fs-7">
                                                            {{ $batch['livestockStrain'] }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="separator separator-dashed my-3"></div>

                                                <div class="row g-3">
                                                    <div class="col-6">
                                                        <div class="fw-semibold text-gray-600 fs-7">Population</div>
                                                        <div class="fw-bold text-gray-800">
                                                            {{ number_format($batch['current_quantity']) }}
                                                            <span class="text-muted fs-8">
                                                                / {{ number_format($batch['initial_quantity']) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="fw-semibold text-gray-600 fs-7">Age</div>
                                                        <div class="fw-bold text-gray-800">
                                                            {{ $batch['age_days'] }} days
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="fw-semibold text-gray-600 fs-7">Start Date</div>
                                                        <div class="fw-bold text-gray-800">
                                                            {{ $batch['start_date'] }}
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="fw-semibold text-gray-600 fs-7">Coop</div>
                                                        <div class="fw-bold text-gray-800">
                                                            {{ $batch['coop_name'] }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Selection Indicator -->
                                                <div class="position-absolute top-0 end-0 m-3">
                                                    <i class="ki-duotone ki-arrow-right fs-2x text-primary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-information-2 fs-3x text-muted mb-5">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    <h3 class="text-muted">No Active Batches</h3>
                                    <p class="text-muted">No active batches found for this livestock.</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Step 2: Stock Selection -->
                @if ($step === 2)
                <div class="row g-3" style="height: calc(100vh - 280px);">
                    <!-- Form Fields -->
                    <div class="col-lg-3">
                        <div class="card card-flush h-100">
                            <div class="card-header py-3">
                                <div class="card-title">
                                    <h4 class="fw-bold text-gray-800 fs-6">Usage Details</h4>
                                </div>
                            </div>
                            <div class="card-body pt-3 pb-3" style="overflow-y: auto;">
                                <!-- Livestock Info -->
                                <div class="mb-4">
                                    <label class="fs-7 fw-semibold form-label mb-1">Livestock</label>
                                    <div class="form-control form-control-sm form-control-solid">
                                        {{ $livestock->name ?? 'N/A' }}
                                    </div>
                                </div>

                                <!-- Selected Batch Info -->
                                @if($selectedBatch)
                                <div class="mb-4">
                                    <label class="fs-7 fw-semibold form-label mb-1">Selected Batch</label>
                                    <div class="card border border-primary">
                                        <div class="card-body p-2">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="ki-duotone ki-abstract-39 fs-2 text-primary me-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <div>
                                                    <div class="fw-bold text-gray-800 fs-7">{{
                                                        $selectedBatch['batch_name'] ?? 'Unknown Batch' }}</div>
                                                    <div class="text-muted fs-8">{{ $selectedBatch['livestockStrain'] ??
                                                        'Unknown Strain' }}</div>
                                                </div>
                                            </div>
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <div class="fs-8 text-muted">Population</div>
                                                    <div class="fw-bold text-gray-800 fs-8">{{
                                                        number_format($selectedBatch['current_quantity'] ?? 0) }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="fs-8 text-muted">Age</div>
                                                    <div class="fw-bold text-gray-800 fs-8">{{
                                                        $selectedBatch['age_days'] ?? 0 }} days</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Usage Date -->
                                <div class="mb-4">
                                    <label class="fs-7 fw-semibold form-label mb-1 required">Usage Date</label>
                                    <input type="date" class="form-control form-control-sm form-control-solid"
                                        wire:model.live="usageDate" required>
                                </div>

                                <!-- Usage Purpose -->
                                <div class="mb-4">
                                    <label class="fs-7 fw-semibold form-label mb-1 required">Purpose</label>
                                    <select class="form-select form-select-sm form-select-solid"
                                        wire:model="usagePurpose" required>
                                        <option value="">Select Purpose</option>
                                        <option value="feeding">Regular Feeding</option>
                                        <option value="medication">Medication</option>
                                        <option value="supplement">Supplement</option>
                                        <option value="treatment">Treatment</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <!-- Notes -->
                                <div class="mb-4">
                                    <label class="fs-7 fw-semibold form-label mb-1">Notes</label>
                                    <textarea class="form-control form-control-sm form-control-solid" wire:model="notes"
                                        rows="2" placeholder="Optional notes..."></textarea>
                                </div>

                                <!-- Quick Summary -->
                                @if (!empty($selectedStocks))
                                <div class="mb-4">
                                    <div class="card bg-light-primary">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="ki-duotone ki-chart-simple fs-2 text-primary me-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                </i>
                                                <div>
                                                    <div class="fw-bold text-gray-800 fs-7">Quick Summary</div>
                                                    <div class="text-muted fs-8">Current selection</div>
                                                </div>
                                            </div>
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <div class="fs-8 text-muted">Stocks</div>
                                                    <div class="fw-bold text-primary fs-7">{{ count($selectedStocks) }}
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="fs-8 text-muted">Est. Cost</div>
                                                    <div class="fw-bold text-primary fs-7">
                                                        Rp {{
                                                        number_format(collect($selectedStocks)->sum(function($stock) {
                                                        return ($stock['quantity'] ?? 0) * $stock['cost_per_unit'];
                                                        }), 0) }}
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="fs-8 text-muted">Total Qty</div>
                                                    <div class="fw-bold text-primary fs-7">
                                                        {{ number_format(collect($selectedStocks)->sum('quantity'), 2)
                                                        }}
                                                        {{ $selectedStocks[0]['unit'] ?? 'kg' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Error Display -->
                                @if (!empty($errors))
                                <div class="alert alert-danger p-3 mb-4">
                                    <div class="alert-text fs-8">
                                        <strong>Please fix errors:</strong>
                                        <ul class="mb-0 mt-1">
                                            @foreach ($errors as $field => $fieldErrors)
                                            @if (is_array($fieldErrors))
                                            @foreach ($fieldErrors as $error)
                                            <li>{{ $error }}</li>
                                            @endforeach
                                            @else
                                            <li>{{ $fieldErrors }}</li>
                                            @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @endif
                            </div>

                            <!-- Fixed Action Buttons at Bottom -->
                            <div class="card-footer py-3 bg-light">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-light-secondary btn-sm flex-fill"
                                        wire:click="backToBatchSelection">
                                        <i class="ki-duotone ki-arrow-left fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Back
                                    </button>
                                    @if (!empty($selectedStocks))
                                    <button type="button" class="btn btn-primary btn-sm flex-fill"
                                        wire:click="previewUsage">
                                        <i class="ki-duotone ki-eye fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        Preview
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Feed Stocks -->
                    <div class="col-lg-4">
                        <div class="card card-flush h-100">
                            <div class="card-header py-3">
                                <div class="card-title">
                                    <h4 class="fw-bold text-gray-800 fs-6">Available Feed Stocks</h4>
                                </div>
                                @if (!empty($availableFeeds))
                                <div class="card-toolbar">
                                    <span class="badge badge-light-info fs-8">
                                        {{ collect($availableFeeds)->sum('stock_count') }} available
                                    </span>
                                </div>
                                @endif
                            </div>
                            <div class="card-body pt-3 pb-3" style="overflow-y: auto;">
                                @if (!empty($availableFeeds))
                                <div class="pe-3">
                                    @foreach ($availableFeeds as $feed)
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <h5 class="fs-7 fw-bold text-gray-800 mb-0">
                                                {{ $feed['feed_name'] }}
                                            </h5>
                                            <span class="badge badge-light-primary fs-8">
                                                {{ $feed['stock_count'] }} stocks
                                            </span>
                                        </div>

                                        @foreach ($feed['stocks'] as $stock)
                                        <div class="card card-dashed border-gray-300 mb-2 hover-elevate-up">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="flex-grow-1">
                                                        <h6 class="fs-8 fw-bold text-gray-800 mb-0">
                                                            {{ $stock['stock_name'] }}
                                                        </h6>
                                                        <div class="text-muted fs-9">
                                                            {{ $stock['batch_info'] ?? 'No batch info' }}
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-sm btn-light-primary"
                                                        wire:click="addStock('{{ $stock['stock_id'] }}')">
                                                        <i class="ki-duotone ki-plus fs-3">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </button>
                                                </div>

                                                <div class="row g-1">
                                                    <div class="col-6">
                                                        <div class="fs-9 text-muted">Available</div>
                                                        <div class="fw-bold text-success fs-8">
                                                            {{ number_format($stock['available_quantity'], 2) }}
                                                            {{ $stock['unit'] }}
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="fs-9 text-muted">Cost/Unit</div>
                                                        <div class="fw-bold text-gray-800 fs-8">
                                                            Rp {{ number_format($stock['cost_per_unit'], 0) }}
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div>
                                                                <span class="badge badge-light-info fs-9">
                                                                    {{ $stock['age_days'] }} days old
                                                                </span>
                                                            </div>
                                                            <div class="fs-9 text-muted">
                                                                Total: Rp {{ number_format($stock['available_quantity']
                                                                * $stock['cost_per_unit'], 0) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="text-center py-8">
                                    <i class="ki-duotone ki-information-2 fs-2x text-muted mb-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    <h4 class="text-muted fs-6">No Feed Stocks</h4>
                                    <p class="text-muted fs-8">No feed stocks available for this livestock.</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Selected Stocks -->
                    <div class="col-lg-5">
                        <div class="card card-flush h-100">
                            <div class="card-header py-3">
                                <div class="card-title">
                                    <h4 class="fw-bold text-gray-800 fs-6">Selected Stocks</h4>
                                </div>
                                @if (!empty($selectedStocks))
                                <div class="card-toolbar">
                                    <span class="badge badge-light-success fs-8">
                                        {{ count($selectedStocks) }} selected
                                    </span>
                                </div>
                                @endif
                            </div>
                            <div class="card-body pt-3 pb-3" style="overflow-y: auto;">
                                @if (!empty($selectedStocks))
                                <div class="pe-3">
                                    @foreach ($selectedStocks as $index => $selectedStock)
                                    <div class="card border border-primary mb-3 hover-elevate-up">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="ki-duotone ki-abstract-39 fs-2 text-primary me-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        <div>
                                                            <h6 class="fs-8 fw-bold text-gray-800 mb-0">
                                                                {{ $selectedStock['feed_name'] }}
                                                            </h6>
                                                            <div class="text-muted fs-9">
                                                                {{ $selectedStock['stock_name'] }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if($selectedStock['batch_info'])
                                                    <div class="text-muted fs-9 ms-5">
                                                        {{ $selectedStock['batch_info'] }}
                                                    </div>
                                                    @endif
                                                </div>
                                                <button class="btn btn-sm btn-light-danger"
                                                    wire:click="removeStock({{ $index }})">
                                                    <i class="ki-duotone ki-trash fs-3">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                        <span class="path4"></span>
                                                        <span class="path5"></span>
                                                    </i>
                                                </button>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <div class="fs-9 text-muted">Available</div>
                                                    <div class="fw-bold text-success fs-8">
                                                        {{ number_format($selectedStock['available_quantity'], 2) }}
                                                        {{ $selectedStock['unit'] }}
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="fs-9 text-muted">Cost/Unit</div>
                                                    <div class="fw-bold text-gray-800 fs-8">
                                                        Rp {{ number_format($selectedStock['cost_per_unit'], 0) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Quantity Input -->
                                            <div class="mb-2">
                                                <label class="fs-9 fw-semibold form-label mb-1">Quantity ({{
                                                    $selectedStock['unit'] }})</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control"
                                                        wire:model.live="selectedStocks.{{ $index }}.quantity"
                                                        step="0.01" min="0.01"
                                                        max="{{ $selectedStock['available_quantity'] }}"
                                                        placeholder="0.00">
                                                    <span class="input-group-text fs-9">{{ $selectedStock['unit']
                                                        }}</span>
                                                </div>
                                                <div class="form-text fs-9">
                                                    Max: {{ number_format($selectedStock['available_quantity'], 2) }} {{
                                                    $selectedStock['unit'] }}
                                                </div>
                                            </div>

                                            <!-- Estimated Cost -->
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="fs-9 text-muted">Estimated Cost</div>
                                                    <div class="fw-bold text-primary fs-7">
                                                        Rp {{ number_format(($selectedStock['quantity'] ?? 0) *
                                                        $selectedStock['cost_per_unit'], 0) }}
                                                    </div>
                                                </div>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                        style="width: {{ $selectedStock['available_quantity'] > 0 ? (($selectedStock['quantity'] ?? 0) / $selectedStock['available_quantity']) * 100 : 0 }}%">
                                                    </div>
                                                </div>
                                                <div class="fs-9 text-muted mt-1">
                                                    {{ number_format($selectedStock['available_quantity'] > 0 ?
                                                    (($selectedStock['quantity'] ?? 0) /
                                                    $selectedStock['available_quantity']) * 100 : 0, 1) }}% of available
                                                    stock
                                                </div>
                                            </div>

                                            <!-- Note -->
                                            <div class="mb-0">
                                                <label class="fs-9 fw-semibold form-label mb-1">Note</label>
                                                <textarea class="form-control form-control-sm"
                                                    wire:model="selectedStocks.{{ $index }}.note" rows="1"
                                                    placeholder="Optional note..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="text-center py-8">
                                    <i class="ki-duotone ki-basket fs-2x text-muted mb-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                    <h4 class="text-muted fs-6">No Stocks Selected</h4>
                                    <p class="text-muted fs-8">Select feed stocks from the available list to get
                                        started.</p>
                                    <div class="mt-4">
                                        <div class="d-flex justify-content-center">
                                            <div class="border border-dashed border-primary rounded p-4 text-center">
                                                <i class="ki-duotone ki-arrow-left fs-1 text-primary mb-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                                <div class="text-primary fw-bold fs-8">Click "Add" button</div>
                                                <div class="text-muted fs-9">on any available stock</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Step 3: Preview -->
                @if ($step === 3 && $previewData)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <h3 class="fw-bold text-gray-800">Usage Preview</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Summary -->
                                <div class="row mb-5">
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-5">
                                                <span class="symbol-label bg-light-primary">
                                                    <i class="ki-duotone ki-weight fs-2x text-primary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="fs-7 text-muted">Total Quantity</span>
                                                <div class="fs-5 fw-bold text-gray-800">
                                                    {{ number_format($previewData['total_quantity'], 2) }}
                                                    @if(!empty($selectedStocks))
                                                    {{ $selectedStocks[0]['unit'] ?? 'kg' }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-5">
                                                <span class="symbol-label bg-light-success">
                                                    <i class="ki-duotone ki-dollar fs-2x text-success">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="fs-7 text-muted">Total Cost</span>
                                                <div class="fs-5 fw-bold text-gray-800">
                                                    Rp {{ number_format($previewData['total_cost'], 0) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-5">
                                                <span class="symbol-label bg-light-info">
                                                    <i class="ki-duotone ki-calculator fs-2x text-info">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="fs-7 text-muted">Average Cost/Unit</span>
                                                <div class="fs-5 fw-bold text-gray-800">
                                                    Rp {{ number_format($previewData['average_cost_per_unit'], 0) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-50px me-5">
                                                <span class="symbol-label bg-light-warning">
                                                    <i class="ki-duotone ki-package fs-2x text-warning">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                    </i>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="fs-7 text-muted">Stocks Used</span>
                                                <div class="fs-5 fw-bold text-gray-800">
                                                    {{ count($previewData['stocks']) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stock Details Table -->
                                <div class="table-responsive">
                                    <table class="table table-rounded table-striped border gy-7 gs-7">
                                        <thead>
                                            <tr class="fw-semibold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                                <th>Feed</th>
                                                <th>Stock</th>
                                                <th>Requested</th>
                                                <th>Available</th>
                                                <th>Remaining</th>
                                                <th>Cost/Unit</th>
                                                <th>Total Cost</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($previewData['stocks'] as $stock)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $stock['feed_name'] }}</div>
                                                </td>
                                                <td>
                                                    <div>{{ $stock['stock_name'] }}</div>
                                                    @if ($stock['batch_info'])
                                                    <div class="fs-8 text-muted">
                                                        {{ $stock['batch_info'] }}
                                                    </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="fw-bold">
                                                        {{ number_format($stock['requested_quantity'], 2) }}
                                                    </span>
                                                    <span class="text-muted">{{ $stock['unit'] }}</span>
                                                </td>
                                                <td>
                                                    {{ number_format($stock['available_quantity'], 2) }}
                                                    <span class="text-muted">{{ $stock['unit'] }}</span>
                                                </td>
                                                <td>
                                                    {{ number_format($stock['remaining_after_usage'], 2) }}
                                                    <span class="text-muted">{{ $stock['unit'] }}</span>
                                                </td>
                                                <td>
                                                    Rp {{ number_format($stock['cost_per_unit'], 0) }}
                                                </td>
                                                <td>
                                                    <span class="fw-bold">
                                                        Rp {{ number_format($stock['stock_cost'], 0) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($stock['can_fulfill'])
                                                    <span class="badge badge-light-success">✓ OK</span>
                                                    @else
                                                    <span class="badge badge-light-danger">✗ Insufficient</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Step 4: Complete -->
                @if ($step === 4)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-20">
                                <div class="mb-10">
                                    <i class="ki-duotone ki-check-circle fs-5x text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <h1 class="fw-bolder fs-2qx text-gray-900 mb-4">Success!</h1>
                                <div class="fw-semibold fs-6 text-gray-500 mb-7">
                                    {{ $successMessage }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                @if ($step === 1)
                <!-- Step 1: Batch Selection -->
                <button type="button" class="btn btn-light" wire:click="closeModal">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Cancel
                </button>
                <div class="flex-grow-1 text-center">
                    <span class="text-muted fs-7">Select a batch to continue</span>
                </div>
                @elseif ($step === 2)
                <!-- Step 2: Stock Selection - Actions moved to card body -->
                <button type="button" class="btn btn-light" wire:click="closeModal">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Cancel
                </button>
                <div class="flex-grow-1 text-center">
                    @if (!empty($selectedStocks))
                    <span class="text-success fs-7">
                        <i class="ki-duotone ki-check-circle fs-5 text-success me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ count($selectedStocks) }} stock(s) selected • Ready to preview
                    </span>
                    @else
                    <span class="text-muted fs-7">Add stocks to continue</span>
                    @endif
                </div>
                @elseif ($step === 3)
                <!-- Step 3: Preview -->
                <button type="button" class="btn btn-light me-3" wire:click="backToSelection">
                    <i class="ki-duotone ki-arrow-left fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Back to Selection
                </button>
                <div class="flex-grow-1 text-center">
                    @if($canProcess)
                    <span class="text-success fs-7">
                        <i class="ki-duotone ki-check-circle fs-5 text-success me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        All validations passed • Ready to process
                    </span>
                    @else
                    <span class="text-danger fs-7">
                        <i class="ki-duotone ki-cross-circle fs-5 text-danger me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Please fix validation errors
                    </span>
                    @endif
                </div>
                <button type="button" class="btn btn-success" wire:click="processUsage" @if(!$canProcess) disabled
                    @endif>
                    @if($isEditMode)
                    <i class="ki-duotone ki-pencil fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Update Usage
                    @else
                    <i class="ki-duotone ki-check fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Process Usage
                    @endif
                </button>
                @elseif ($step === 4)
                <!-- Step 4: Complete -->
                <button type="button" class="btn btn-light me-3" wire:click="resetForm">
                    <i class="ki-duotone ki-plus fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Add Another
                </button>
                <div class="flex-grow-1 text-center">
                    <span class="text-success fs-7">
                        <i class="ki-duotone ki-check-circle fs-5 text-success me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Feed usage {{ $isEditMode ? 'updated' : 'processed' }} successfully
                    </span>
                </div>
                <button type="button" class="btn btn-primary" wire:click="closeModal">
                    <i class="ki-duotone ki-check fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Done
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .manual-feed-usage-modal .stepper-item.current .stepper-wrapper .stepper-icon {
        background-color: var(--bs-primary);
        color: white;
    }

    .manual-feed-usage-modal .stepper-item.current .stepper-wrapper .stepper-title {
        color: var(--bs-primary);
    }

    .manual-feed-usage-modal .card-dashed {
        border-style: dashed !important;
        transition: all 0.2s ease;
    }

    .manual-feed-usage-modal .card-dashed:hover {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 0.1rem rgba(var(--bs-primary-rgb), 0.25);
    }

    .manual-feed-usage-modal .hover-elevate-up:hover {
        transform: translateY(-2px);
        transition: all 0.2s ease;
        box-shadow: 0 0.5rem 1.5rem 0.5rem rgba(0, 0, 0, 0.075);
    }

    .manual-feed-usage-modal .scroll-y {
        overflow-y: auto;
    }

    .manual-feed-usage-modal .mh-400px {
        max-height: 400px;
    }

    .manual-feed-usage-modal .overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1000;
    }

    .manual-feed-usage-modal .progress {
        height: 6px;
    }

    .manual-feed-usage-modal .input-group-sm .form-control {
        font-weight: 600;
    }

    .manual-feed-usage-modal .badge {
        font-size: 0.75rem;
    }

    .manual-feed-usage-modal .card-flush {
        box-shadow: 0 0 50px 0 rgba(82, 63, 105, 0.15);
    }

    .manual-feed-usage-modal .bg-light-primary {
        background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
    }

    .manual-feed-usage-modal .text-primary {
        color: var(--bs-primary) !important;
    }

    .manual-feed-usage-modal .border-primary {
        border-color: var(--bs-primary) !important;
    }

    .manual-feed-usage-modal .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    .manual-feed-usage-modal .card-header {
        background-color: #fff;
        border-bottom: 1px solid #e9ecef;
    }

    /* Animation for quantity changes */
    .manual-feed-usage-modal .progress-bar {
        transition: width 0.3s ease;
    }

    /* Pulse animation for real-time updates */
    @keyframes pulse-primary {
        0% {
            box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(var(--bs-primary-rgb), 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0);
        }
    }

    .manual-feed-usage-modal .pulse-primary {
        animation: pulse-primary 2s infinite;
    }

    /* Optimized for 19" Monitor (1440x900 / 1366x768) */
    @media (min-width: 1200px) and (max-width: 1600px) {

        /* Compact spacing for better space utilization */
        .card-body {
            padding: 0.75rem !important;
        }

        .card-header {
            padding: 0.5rem 0.75rem !important;
        }

        .card-footer {
            padding: 0.5rem 0.75rem !important;
        }

        /* Improved scrolling behavior */
        .card-body[style*="overflow-y: auto"] {
            max-height: calc(100vh - 350px);
            padding-right: 0.5rem;
        }

        /* Better form controls sizing */
        .form-control-sm,
        .form-select-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Optimized button sizing */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Compact badge sizing */
        .badge {
            padding: 0.25rem 0.5rem;
            font-size: 0.65rem;
        }

        /* Better card spacing */
        .card.mb-3 {
            margin-bottom: 0.75rem !important;
        }

        .card.mb-4 {
            margin-bottom: 1rem !important;
        }

        /* Improved progress bar visibility */
        .progress-sm {
            height: 0.375rem;
        }

        /* Better input group sizing */
        .input-group-sm .input-group-text {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        /* Optimized textarea sizing */
        textarea.form-control-sm {
            min-height: calc(1.5em + 0.5rem + 2px);
        }

        /* Better alert sizing */
        .alert {
            padding: 0.5rem 0.75rem;
        }

        /* Improved empty state sizing */
        .text-center.py-8 {
            padding: 2rem 1rem !important;
        }

        /* Better icon sizing for compact view */
        .ki-duotone.fs-2x {
            font-size: 1.5rem !important;
        }

        .ki-duotone.fs-3x {
            font-size: 2rem !important;
        }
    }

    /* Enhanced hover effects */
    .hover-elevate-up:hover {
        transform: translateY(-2px);
        transition: all 0.15s ease-in-out;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
    }

    /* Smooth transitions for all interactive elements */
    .card,
    .btn,
    .form-control,
    .form-select {
        transition: all 0.15s ease-in-out;
    }

    /* Better focus states */
    .form-control:focus,
    .form-select:focus {
        border-color: #009ef7;
        box-shadow: 0 0 0 0.2rem rgba(0, 158, 247, 0.25);
    }

    /* Improved scrollbar styling */
    .card-body[style*="overflow-y: auto"]::-webkit-scrollbar {
        width: 6px;
    }

    .card-body[style*="overflow-y: auto"]::-webkit-scrollbar-track {
        background: #f1f3f6;
        border-radius: 3px;
    }

    .card-body[style*="overflow-y: auto"]::-webkit-scrollbar-thumb {
        background: #d1d3e0;
        border-radius: 3px;
    }

    .card-body[style*="overflow-y: auto"]::-webkit-scrollbar-thumb:hover {
        background: #a1a5b7;
    }

    /* Better responsive behavior for smaller 19" monitors */
    @media (max-width: 1366px) {
        .row[style*="height: calc(100vh - 280px)"] {
            height: calc(100vh - 250px) !important;
        }

        .card-body[style*="overflow-y: auto"] {
            max-height: calc(100vh - 320px);
        }

        /* More compact font sizes */
        .fs-6 {
            font-size: 0.875rem !important;
        }

        .fs-7 {
            font-size: 0.75rem !important;
        }

        .fs-8 {
            font-size: 0.65rem !important;
        }

        .fs-9 {
            font-size: 0.6rem !important;
        }
    }

    /* Enhanced loading states */
    .card.loading {
        opacity: 0.7;
        pointer-events: none;
    }

    /* Better error state styling */
    .alert-danger {
        border-left: 4px solid #f1416c;
    }

    /* Improved success state styling */
    .alert-success {
        border-left: 4px solid #50cd89;
    }

    /* Better progress bar animations */
    .progress-bar {
        transition: width 0.3s ease-in-out;
    }

    /* Enhanced card border effects */
    .card.border-primary {
        border-width: 2px !important;
    }

    .card.hover-elevate-up {
        border: 1px solid #e4e6ef;
    }

    .card.hover-elevate-up:hover {
        border-color: #009ef7;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
        console.log('🔥 Manual Feed Usage component initialized');

        // Handle modal show event
        Livewire.on('show-manual-feed-usage', function (data) {
            console.log('🔥 show-manual-feed-usage event received', data);
            var modal = new bootstrap.Modal(document.getElementById('manual-feed-usage-modal'));
            modal.show();
        });

        // Handle direct modal open event
        Livewire.on('openManualFeedUsageModal', function (livestockId, feedId) {
            console.log('🔥 openManualFeedUsageModal event received', { livestockId, feedId });
            var modal = new bootstrap.Modal(document.getElementById('manual-feed-usage-modal'));
            modal.show();
        });

        // Handle feed usage completed event
        Livewire.on('feed-usage-completed', function (data) {
            console.log('Feed usage completed:', data);
            
            // Show success notification
            if (typeof toastr !== 'undefined') {
                toastr.success('Feed usage processed successfully!');
            }
            
            // Dispatch custom event for parent components
            window.dispatchEvent(new CustomEvent('feed-usage-completed', {
                detail: data
            }));
        });

        // Handle modal close event
        Livewire.on('close-manual-feed-usage-modal', function () {
            console.log('🔥 close-manual-feed-usage-modal event received');
            var modal = bootstrap.Modal.getInstance(document.getElementById('manual-feed-usage-modal'));
            if (modal) {
                modal.hide();
            } else {
                // Fallback: hide modal using jQuery if bootstrap instance not found
                $('#manual-feed-usage-modal').modal('hide');
            }
        });

        // Handle Bootstrap modal events
        var modalElement = document.getElementById('manual-feed-usage-modal');
        if (modalElement) {
            // When modal is hidden by Bootstrap (X button, ESC key, backdrop click)
            modalElement.addEventListener('hidden.bs.modal', function (event) {
                console.log('🔥 Bootstrap modal hidden event triggered');
                // Call Livewire closeModalSilent method to reset component state without loop
                Livewire.find('{{ $this->getId() }}').call('closeModalSilent');
            });

            // When modal is about to be hidden
            modalElement.addEventListener('hide.bs.modal', function (event) {
                console.log('🔥 Bootstrap modal hide event triggered');
            });
        }

        // Auto-hide modal when step changes to 4 (success)
        Livewire.on('step-changed', function (step) {
            if (step === 4) {
                setTimeout(function () {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('manual-feed-usage-modal'));
                    if (modal) {
                        modal.hide();
                    }
                }, 3000); // Auto-hide after 3 seconds
            }
        });
    });

    // Handle manual modal show with debugging
    function showManualFeedUsageModal(livestockId, feedId = null) {
        console.log('🔥 showManualFeedUsageModal called', { livestockId, feedId });
        
        // Try multiple methods
        try {
            Livewire.dispatch('openManualFeedUsageModal', livestockId, feedId);
        } catch (error) {
            console.error('Error with openManualFeedUsageModal:', error);
            
            // Fallback method
            try {
                Livewire.dispatch('show-manual-feed-usage', {
                    livestock_id: livestockId,
                    feed_id: feedId
                });
            } catch (fallbackError) {
                console.error('Error with show-manual-feed-usage:', fallbackError);
            }
        }
    }
</script>
@endpush