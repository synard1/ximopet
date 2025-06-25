<!-- FIFO Edit Mode -->
<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-edit me-2"></i>
                Edit Mutasi FIFO
            </h4>
            <div>
                <button type="button" class="btn btn-sm btn-outline-dark me-2" wire:click="cancelEditMode">
                    <i class="fas fa-arrow-left me-1"></i>
                    Kembali ke Create
                </button>
                <button type="button" class="btn btn-sm btn-dark" wire:click="closeModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Edit Mode Alert -->
        <div class="alert alert-warning mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <div class="flex-grow-1">
                    <strong>{{ $editModeMessage }}</strong>
                    @if(!empty($existingMutationIds))
                    <br><small class="text-muted">ID Mutasi: {{ implode(', ', $existingMutationIds) }}</small>
                    @endif
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @include('livewire.livestock.mutation.partials.fifo-alerts')

        <!-- Quick Info -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center p-3">
                        <div class="fw-bold text-primary">{{ $mutationDate }}</div>
                        <small class="text-muted">Tanggal</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center p-3">
                        <div class="fw-bold text-success">{{ $sourceLivestock->name ?? 'N/A' }}</div>
                        <small class="text-muted">Sumber</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center p-3">
                        <div class="fw-bold text-info">{{ number_format($quantity) }}</div>
                        <small class="text-muted">Total Ekor</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center p-3">
                        <div class="fw-bold text-warning">{{ count($existingMutationItems) }}</div>
                        <small class="text-muted">Item</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Items Table -->
        @if(!empty($existingMutationItems))
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Data Item Mutasi
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Batch</th>
                                <th width="15%">Umur</th>
                                <th width="20%">Kuantitas</th>
                                <th width="15%">Tersedia</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($existingMutationItems as $index => $item)
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ $index + 1 }}</span>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-bold">{{ $item['batch_name'] }}</div>
                                        @if($item['batch_start_date'])
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($item['batch_start_date'])->format('d/m/Y') }}
                                        </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $item['age_days'] }} hari</span>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number"
                                            class="form-control @error('existingItem.'.$index.'.quantity') is-invalid @enderror"
                                            value="{{ $item['quantity'] }}"
                                            wire:change="updateExistingItemQuantity({{ $index }}, $event.target.value)"
                                            min="0"
                                            max="{{ $item['available_quantity'] + $item['original_quantity'] }}">
                                        <span class="input-group-text">ekor</span>
                                    </div>
                                    @error('existingItem.'.$index.'.quantity')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <span class="text-success fw-bold">
                                        {{ number_format($item['available_quantity'] + $item['original_quantity']) }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="removeExistingItem({{ $index }})"
                                        wire:confirm="Yakin ingin menghapus item ini?">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add New Item -->
        @if($sourceLivestock && $availableBatches->count() > 0)
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Tambah Item Baru
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Pilih Batch</label>
                        <select class="form-select" wire:model="newItemBatchId">
                            <option value="">Pilih Batch</option>
                            @foreach($availableBatches as $batch)
                            @php
                            $availableQty = $batch->initial_quantity - $batch->quantity_depletion -
                            $batch->quantity_sales - $batch->quantity_mutated;
                            @endphp
                            @if($availableQty > 0)
                            <option value="{{ $batch->id }}">
                                {{ $batch->name }} ({{ number_format($availableQty) }} ekor)
                            </option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kuantitas</label>
                        <div class="input-group">
                            <input type="number" class="form-control" wire:model="newItemQuantity" min="1"
                                placeholder="0">
                            <span class="input-group-text">ekor</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-success w-100"
                            wire:click="addNewItemToExisting($wire.newItemBatchId, $wire.newItemQuantity)"
                            @if(!$newItemBatchId || !$newItemQuantity) disabled @endif>
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif

        <!-- Action Buttons -->
        <div class="d-grid gap-2 mt-4">
            <button type="button" class="btn btn-warning" wire:click="generateFifoPreview" wire:loading.attr="disabled"
                wire:target="generateFifoPreview">
                <span wire:loading.remove wire:target="generateFifoPreview">
                    <i class="fas fa-eye me-2"></i>
                    Preview Edit FIFO
                </span>
                <span wire:loading wire:target="generateFifoPreview">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Generating Preview...
                </span>
            </button>
        </div>
    </div>
</div>