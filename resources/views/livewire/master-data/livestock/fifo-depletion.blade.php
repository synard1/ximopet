<div>
    <div wire:ignore.self class="modal fade" id="kt_modal_fifo_depletion" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-sort-amount-down me-2"></i>
                        FIFO Depletion - {{ $livestock ? $livestock->name : 'Loading...' }}
                        @if($isEditMode)
                        <span class="badge bg-warning ms-2">EDIT MODE</span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="closeModal"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <!-- Success Messages for Edit Mode -->
                    @if($successMessage && $isEditMode && $step == 1)
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="fas fa-edit me-2"></i>
                        <strong>Edit Mode!</strong>
                        <p class="mb-0 mt-2">{{ $successMessage }}</p>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-warning me-2" wire:click="forceCreateNew">
                                <i class="fas fa-plus me-1"></i>Create New Instead
                            </button>
                            <button type="button" class="btn btn-sm btn-danger"
                                wire:click="deleteAllExistingDepletions">
                                <i class="fas fa-trash me-1"></i>Delete All Existing
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Error Messages -->
                    @if($errors->any() || !empty($customErrors))
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Error!</strong>
                        <ul class="mb-0 mt-2">
                            @if($errors->any())
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                            @endif
                            @if(!empty($customErrors))
                            @foreach($customErrors as $error)
                            @if(is_array($error))
                            @foreach($error as $err)
                            <li>{{ $err }}</li>
                            @endforeach
                            @else
                            <li>{{ $error }}</li>
                            @endif
                            @endforeach
                            @endif
                        </ul>
                    </div>
                    @endif

                    <!-- Step Indicator -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="step-item {{ $step >= 1 ? 'active' : '' }}">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Input</div>
                                </div>
                                <div class="step-line {{ $step >= 2 ? 'active' : '' }}"></div>
                                <div class="step-item {{ $step >= 2 ? 'active' : '' }}">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Preview</div>
                                </div>
                                <div class="step-line {{ $step >= 3 ? 'active' : '' }}"></div>
                                <div class="step-item {{ $step >= 3 ? 'active' : '' }}">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Result</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1: Input Form -->
                    @if($step == 1)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-edit me-2"></i>
                                        Depletion Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Depletion Type -->
                                    <div class="mb-3">
                                        <label for="depletionType" class="form-label">Tipe Depletion <span
                                                class="text-danger">*</span></label>
                                        <select wire:model="depletionType" class="form-select">
                                            <option value="">Pilih Tipe Depletion</option>
                                            @foreach($this->depletionTypes as $type => $label)
                                            <option value="{{ $type }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('depletionType')
                                        <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Depletion Date -->
                                    <div class="mb-3">
                                        <label for="depletionDate" class="form-label">Tanggal Depletion <span
                                                class="text-danger">*</span></label>
                                        <input type="date" wire:model.live="depletionDate" class="form-control">
                                        @error('depletionDate')
                                        <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Total Quantity -->
                                    <div class="mb-3">
                                        <label for="totalQuantity" class="form-label">Total Quantity <span
                                                class="text-danger">*</span></label>
                                        <input type="number" wire:model="totalQuantity" class="form-control" min="1"
                                            placeholder="Masukkan jumlah">
                                        @error('totalQuantity')
                                        <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Sistem akan otomatis mendistribusikan quantity ini
                                            menggunakan metode FIFO (First In, First Out)</div>
                                    </div>

                                    <!-- Reason -->
                                    <div class="mb-3">
                                        <label for="reason" class="form-label">Alasan</label>
                                        <textarea wire:model="reason" class="form-control" rows="3"
                                            placeholder="Masukkan alasan depletion (opsional)"></textarea>
                                        @error('reason')
                                        <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Notes -->
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Catatan</label>
                                        <textarea wire:model="notes" class="form-control" rows="2"
                                            placeholder="Catatan tambahan (opsional)"></textarea>
                                        @error('notes')
                                        <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        FIFO Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($livestock)
                                    <div class="mb-3">
                                        <strong>Livestock:</strong> {{ $livestock->name }}
                                    </div>
                                    <div class="mb-3">
                                        <strong>Farm:</strong> {{ $livestock->farm->name ?? 'Unknown' }}
                                    </div>
                                    <div class="mb-3">
                                        <strong>Kandang:</strong> {{ $livestock->kandang->name ?? 'Unknown' }}
                                    </div>
                                    <div class="mb-3">
                                        <strong>Active Batches:</strong> {{ $livestock->getActiveBatchesCount() }} batch
                                    </div>
                                    @endif

                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-lightbulb me-2"></i>Tentang FIFO Depletion</h6>
                                        <ul class="mb-0">
                                            <li>Sistem akan mengambil dari batch tertua terlebih dahulu</li>
                                            <li>Distribusi otomatis berdasarkan urutan tanggal masuk</li>
                                            <li>Quantity akan didistribusikan ke multiple batch jika diperlukan</li>
                                            <li>Preview akan menampilkan detail distribusi sebelum eksekusi</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Step 2: Preview -->
                    @if($step == 2)
                    <div class="row">
                        <div class="col-12">
                            <!-- Edit Mode Information -->
                            @if($isEditMode && isset($previewData['edit_mode_info']))
                            <div class="alert alert-warning mb-4">
                                <h6><i class="fas fa-edit me-2"></i>Edit Mode - Replacing Existing Data</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Existing Records:</strong> {{
                                            $previewData['edit_mode_info']['existing_depletions_count'] }} records</p>
                                        <p><strong>Existing Total Quantity:</strong> {{
                                            $previewData['edit_mode_info']['existing_total_quantity'] }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>New Total Quantity:</strong> {{ $totalQuantity }}</p>
                                        <p><strong>Date:</strong> {{ $previewData['edit_mode_info']['edit_date'] }}</p>
                                    </div>
                                </div>
                                <p class="mb-0"><i class="fas fa-info-circle me-1"></i><strong>Note:</strong> Processing
                                    will delete all existing records for this date and create new ones based on current
                                    form data.</p>
                            </div>
                            @endif

                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-eye me-2"></i>
                                        Preview FIFO Distribution
                                        @if($isEditMode)
                                        <span class="badge bg-warning ms-2">EDIT MODE</span>
                                        @endif
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($previewData)
                                    <!-- Summary -->
                                    <div class="row mb-4">
                                        <div class="col-md-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ $totalQuantity }}</h4>
                                                    <small>Total Quantity</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-info text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ count($fifoDistribution) }}</h4>
                                                    <small>Batches Affected</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{ $canProcess ? 'Yes' : 'No' }}</h4>
                                                    <small>Can Fulfill</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-warning text-white">
                                                <div class="card-body text-center">
                                                    <h4>{{
                                                        $previewData['distribution']['validation']['total_distributed']
                                                        ?? 0 }}</h4>
                                                    <small>Total Available</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Distribution Details -->
                                    @if(!empty($fifoDistribution))
                                    <h6>Distribution Details:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Sequence</th>
                                                    <th>Batch Name</th>
                                                    <th>Start Date</th>
                                                    <th>Age (Days)</th>
                                                    <th>Available</th>
                                                    <th>Will Take</th>
                                                    <th>Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($fifoDistribution as $batch)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $batch['batch_name'] ?? 'Unknown' }}</td>
                                                    <td>{{ $batch['start_date'] ?? 'Unknown' }}</td>
                                                    <td>{{ $batch['age_days'] ?? 0 }}</td>
                                                    <td>{{ $batch['available_quantity'] ?? 0 }}</td>
                                                    <td><strong>{{ $batch['quantity_to_take'] ?? 0 }}</strong></td>
                                                    <td>{{ $batch['remaining_after'] ?? 0 }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                    <!-- Recording Information -->
                                    @if(isset($previewData['recording_info']))
                                    <div class="alert alert-info mt-3">
                                        <h6><i class="fas fa-clipboard me-2"></i>Recording Information</h6>
                                        <p><strong>Date:</strong> {{ $previewData['recording_info']['recording_date'] }}
                                        </p>
                                        <p><strong>Current Stock:</strong> {{
                                            $previewData['recording_info']['current_stock'] }}</p>
                                        <p><strong>Current Mortality:</strong> {{
                                            $previewData['recording_info']['mortality'] }}</p>
                                        <p class="mb-0"><strong>Current Culling:</strong> {{
                                            $previewData['recording_info']['culling'] }}</p>
                                    </div>
                                    @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Step 3: Result -->
                    @if($step == 3)
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        @if($isEditMode)
                                        FIFO Depletion Updated Successfully
                                        @else
                                        FIFO Depletion Completed
                                        @endif
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-success">
                                        <h5><i class="fas fa-check-circle me-2"></i>Success!</h5>
                                        <p>{{ $successMessage }}</p>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button type="button" class="btn btn-primary me-2" wire:click="resetForm">
                                            <i class="fas fa-plus me-2"></i>Process Another Depletion
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                            wire:click="closeModal">
                                            <i class="fas fa-times me-2"></i>Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    @if($step == 1)
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeModal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    @if($isEditMode)
                    <button type="button" class="btn btn-primary" wire:click="previewEditMode" {{ $isLoading
                        ? 'disabled' : '' }}>
                        @if($isLoading)
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        @else
                        <i class="fas fa-eye me-2"></i>
                        @endif
                        Preview Edit Changes
                    </button>
                    @else
                    <button type="button" class="btn btn-primary" wire:click="previewDepletion" {{ $isLoading
                        ? 'disabled' : '' }}>
                        @if($isLoading)
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        @else
                        <i class="fas fa-eye me-2"></i>
                        @endif
                        Preview Distribution
                    </button>
                    @endif
                    @elseif($step == 2)
                    <button type="button" class="btn btn-secondary" wire:click="backToInput">
                        <i class="fas fa-arrow-left me-2"></i>Back to Input
                    </button>
                    @if($canProcess)
                    @if($isEditMode)
                    <button type="button" class="btn btn-warning" wire:click="processDepletion" {{ $isLoading
                        ? 'disabled' : '' }}>
                        @if($isLoading)
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        @else
                        <i class="fas fa-save me-2"></i>
                        @endif
                        Update FIFO Depletion
                    </button>
                    @else
                    <button type="button" class="btn btn-success" wire:click="processDepletion" {{ $isLoading
                        ? 'disabled' : '' }}>
                        @if($isLoading)
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        @else
                        <i class="fas fa-check me-2"></i>
                        @endif
                        Process FIFO Depletion
                    </button>
                    @endif
                    @else
                    <button type="button" class="btn btn-danger" disabled>
                        <i class="fas fa-exclamation-triangle me-2"></i>Cannot Process
                    </button>
                    @endif
                    @elseif($step == 3)
                    <button type="button" class="btn btn-primary" wire:click="resetForm">
                        <i class="fas fa-plus me-2"></i>Process Another
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeModal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <!-- Custom Styles -->
    <style>
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .step-item.active .step-number {
            background-color: #0d6efd;
            color: white;
        }

        .step-label {
            font-size: 12px;
            color: #6c757d;
            font-weight: 500;
        }

        .step-item.active .step-label {
            color: #0d6efd;
            font-weight: bold;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background-color: #e9ecef;
            margin: 0 20px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .step-line.active {
            background-color: #0d6efd;
        }

        .modal-xl {
            max-width: 1200px;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .alert {
            border: none;
            border-radius: 0.5rem;
        }

        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
        }

        .form-control,
        .form-select {
            border-radius: 0.375rem;
        }
    </style>

    <!-- Modal Close JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('kt_modal_fifo_depletion');
            
            if (modal) {
                // Listen for modal hidden event
                modal.addEventListener('hidden.bs.modal', function () {
                    // Trigger Livewire closeModal method when modal is hidden
                    @this.call('closeModal');
                });
                
                // Ensure modal can be closed with ESC key
                modal.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                });
            }
        });
    </script>
</div>