<div>
    <!-- Manual Depletion Modal -->
    <div wire:ignore.self class="modal fade" id="kt_modal_manual_depletion" tabindex="-1" aria-hidden="true"
        @if($showModal) style="display: block;" aria-modal="true" role="dialog" @endif>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Manual Batch Depletion</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" wire:click="closeModal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>

                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    @if($livestock)
                    <!-- Livestock Info -->
                    <div class="card bg-light-primary mb-5">
                        <div class="card-body p-5">
                            <div class="d-flex align-items-center">
                                <i class="ki-duotone ki-abstract-39 fs-2tx text-primary me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div>
                                    <h5 class="mb-1">{{ $livestock->name }}</h5>
                                    <span class="text-muted">Livestock ID: {{ $livestock->id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Error Messages -->
                    @if(!empty($errors) && is_array($errors))
                    <div class="alert alert-danger mb-5">
                        <h6><i class="ki-duotone ki-warning fs-2 me-2"></i>Error:</h6>
                        <ul class="mb-0">
                            @foreach($errors as $key => $error)
                            <li>{{ is_array($error) ? implode(', ', $error) : $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- Success Message -->
                    @if($successMessage)
                    <div class="alert alert-success mb-5">
                        <i class="ki-duotone ki-check-circle fs-2 me-2"></i>
                        {{ $successMessage }}
                    </div>
                    @endif

                    <!-- Step 1: Batch Selection -->
                    @if($step === 1)
                    <div class="stepper-content">
                        <h4 class="mb-5">Step 1: Pilih Batch dan Konfigurasi</h4>

                        <!-- Depletion Configuration -->
                        <div class="row mb-8">
                            <div class="col-md-4">
                                <label class="required form-label">Tipe Depletion</label>
                                <select class="form-select" wire:model="depletionType">
                                    <option value="mortality">Kematian</option>
                                    <option value="sales">Penjualan</option>
                                    <option value="mutation">Mutasi</option>
                                    <option value="culling">Afkir</option>
                                    <option value="other">Lainnya</option>
                                </select>
                                @if($errors && isset($errors['depletionType']))
                                <div class="text-danger mt-1">{{ is_array($errors['depletionType']) ? implode(', ',
                                    $errors['depletionType']) : $errors['depletionType'] }}</div>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="required form-label">Tanggal</label>
                                <input type="date" class="form-control" wire:model="depletionDate">
                                @if($errors && isset($errors['depletionDate']))
                                <div class="text-danger mt-1">{{ is_array($errors['depletionDate']) ? implode(', ',
                                    $errors['depletionDate']) : $errors['depletionDate'] }}</div>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Alasan</label>
                                <input type="text" class="form-control" wire:model="reason"
                                    placeholder="Alasan depletion (opsional)">
                                @if($errors && isset($errors['reason']))
                                <div class="text-danger mt-1">{{ is_array($errors['reason']) ? implode(', ',
                                    $errors['reason']) : $errors['reason'] }}</div>
                                @endif
                            </div>
                        </div>

                        <!-- Available Batches -->
                        <div class="mb-8">
                            <h5 class="mb-4">Available Batches</h5>

                            @if(count($availableBatches) > 0)
                            <div class="row">
                                @foreach($availableBatches as $batch)
                                <div class="col-md-6 mb-4">
                                    <div
                                        class="card border border-gray-300 @if(collect($selectedBatches)->contains('batch_id', $batch['batch_id'])) border-primary @endif">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-2">{{ $batch['batch_name'] }}</h6>
                                                    <div class="text-muted small mb-2">
                                                        <div>Age: {{ $batch['age_days'] }} days</div>
                                                        <div>Available: <span class="fw-bold text-success">{{
                                                                number_format($batch['available_quantity']) }}</span>
                                                        </div>
                                                        <div>Utilization: {{ $batch['utilization_rate'] }}%</div>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    wire:click="addBatch('{{ $batch['batch_id'] }}')"
                                                    @if(collect($selectedBatches)->contains('batch_id',
                                                    $batch['batch_id'])) disabled @endif>
                                                    @if(collect($selectedBatches)->contains('batch_id',
                                                    $batch['batch_id']))
                                                    <i class="ki-duotone ki-check fs-2"></i> Selected
                                                    @else
                                                    <i class="ki-duotone ki-plus fs-2"></i> Select
                                                    @endif
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="alert alert-warning">
                                <i class="ki-duotone ki-information fs-2 me-2"></i>
                                Tidak ada batch yang tersedia untuk depletion.
                            </div>
                            @endif
                        </div>

                        <!-- Selected Batches -->
                        @if(count($selectedBatches) > 0)
                        <div class="mb-8">
                            <h5 class="mb-4">Selected Batches ({{ count($selectedBatches) }})</h5>

                            @foreach($selectedBatches as $index => $selectedBatch)
                            <div class="card bg-light-success mb-3">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <strong>{{ $selectedBatch['batch_name'] }}</strong><br>
                                            <small class="text-muted">Available: {{
                                                number_format($selectedBatch['available_quantity']) }}</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" class="form-control"
                                                wire:model="selectedBatches.{{ $index }}.quantity" min="1"
                                                max="{{ $selectedBatch['available_quantity'] }}">
                                            @if($errors && isset($errors["selectedBatches.{$index}.quantity"]))
                                            <div class="text-danger mt-1">{{
                                                is_array($errors["selectedBatches.{$index}.quantity"]) ? implode(', ',
                                                $errors["selectedBatches.{$index}.quantity"]) :
                                                $errors["selectedBatches.{$index}.quantity"] }}</div>
                                            @endif
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Note (Optional)</label>
                                            <input type="text" class="form-control"
                                                wire:model="selectedBatches.{{ $index }}.note"
                                                placeholder="Catatan untuk batch ini">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                wire:click="removeBatch({{ $index }})">
                                                <i class="ki-duotone ki-trash fs-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light me-3" wire:click="closeModal">Cancel</button>
                            <button type="button" class="btn btn-primary" wire:click="previewDepletion"
                                @if(count($selectedBatches)===0 || $isLoading) disabled @endif>
                                @if($isLoading)
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                @endif
                                Preview Depletion
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Step 2: Preview -->
                    @if($step === 2 && $previewData)
                    <div class="stepper-content">
                        <h4 class="mb-5">Step 2: Preview Depletion</h4>

                        <!-- Preview Summary -->
                        <div class="card bg-light-info mb-5">
                            <div class="card-body p-5">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="fs-2 fw-bold text-info">{{ $previewData['total_quantity'] }}
                                            </div>
                                            <div class="text-muted">Total Quantity</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="fs-2 fw-bold text-info">{{ $previewData['batches_count'] }}
                                            </div>
                                            <div class="text-muted">Batches Affected</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div
                                                class="fs-2 fw-bold @if($previewData['can_fulfill']) text-success @else text-danger @endif">
                                                @if($previewData['can_fulfill']) ✓ @else ✗ @endif
                                            </div>
                                            <div class="text-muted">Can Fulfill</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="fs-2 fw-bold">{{ ucfirst($depletionType) }}</div>
                                            <div class="text-muted">Depletion Type</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Batch Preview Details -->
                        <h5 class="mb-4">Batch Details</h5>
                        @if(isset($previewData['batches_preview']))
                        @foreach($previewData['batches_preview'] as $batchPreview)
                        <div
                            class="card mb-3 @if($batchPreview['can_fulfill']) border-success @else border-danger @endif">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <strong>{{ $batchPreview['batch_name'] }}</strong><br>
                                        <small class="text-muted">Age: {{ $batchPreview['batch_age_days'] }}
                                            days</small>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="fw-bold">{{ number_format($batchPreview['available_quantity'])
                                                }}</div>
                                            <small class="text-muted">Available</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="fw-bold">{{ number_format($batchPreview['requested_quantity'])
                                                }}</div>
                                            <small class="text-muted">Requested</small>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div
                                                class="fw-bold @if($batchPreview['can_fulfill']) text-success @else text-danger @endif">
                                                @if($batchPreview['can_fulfill']) ✓ @else ✗ @endif
                                            </div>
                                            <small class="text-muted">Status</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        @if($batchPreview['note'])
                                        <small class="text-muted">{{ $batchPreview['note'] }}</small>
                                        @endif
                                        @if($batchPreview['shortfall'] > 0)
                                        <div class="text-danger small">Shortfall: {{ $batchPreview['shortfall'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endif

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-light" wire:click="backToSelection">
                                <i class="ki-duotone ki-arrow-left fs-2"></i> Back to Selection
                            </button>
                            <div>
                                <button type="button" class="btn btn-light me-3" wire:click="closeModal">Cancel</button>
                                <button type="button" class="btn btn-success" wire:click="processDepletion"
                                    @if(!$canProcess || $isLoading) disabled @endif>
                                    @if($isLoading)
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    @endif
                                    Process Depletion
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Step 3: Result -->
                    @if($step === 3)
                    <div class="stepper-content text-center">
                        <div class="mb-5">
                            <i class="ki-duotone ki-check-circle fs-5x text-success mb-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h4 class="mb-3">Depletion Completed Successfully!</h4>
                            <p class="text-muted">{{ $successMessage }}</p>
                        </div>

                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-light me-3" wire:click="resetForm">
                                Process Another
                            </button>
                            <button type="button" class="btn btn-primary" wire:click="closeModal">
                                Close
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Backdrop -->
    @if($showModal)
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

@push('scripts')
<script>
    // Handle modal show/hide with Livewire
    document.addEventListener('livewire:init', function () {
        Livewire.on('show-manual-depletion', (data) => {
            @this.openModal(data[0].livestock_id);
        });
    });

    // Handle modal backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            @this.closeModal();
        }
    });
</script>
@endpush