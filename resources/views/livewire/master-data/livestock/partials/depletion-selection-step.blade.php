@if($isEditing)
<div class="alert alert-warning d-flex align-items-center p-3 mb-5">
    <i class="fas fa-edit fs-2hx text-warning me-4"></i>
    <div>
        <h5 class="text-warning mb-0">Mode Edit</h5>
        <div class="text-muted">
            Anda sedang mengedit data deplesi untuk tanggal <strong>{{ \Carbon\Carbon::parse($depletionDate)->format('d
                F Y') }}</strong>.
            @if(!empty($existingDepletionIds) && count($existingDepletionIds) > 1)
            <br><small class="text-info">
                <i class="fas fa-info-circle me-1"></i>
                Menggabungkan {{ count($existingDepletionIds) }} record deplesi menjadi satu form edit.
            </small>
            @endif
        </div>
    </div>
</div>
@endif

@if($livestock)
<!-- Livestock Info -->
<div class="card bg-light-primary mb-5">
    <div class="card-body p-5">
        <div class="d-flex align-items-center">
            <i class="fas fa-horse-head fs-2tx text-primary me-4"></i>
            <div>
                <h5 class="mb-1">{{ $livestock->name }}</h5>
                <span class="text-muted">ID: {{ $livestock->id }}</span>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Depletion Configuration -->
<div class="row mb-8">
    <div class="col-md-4">
        <label class="required form-label">Tipe Depletion</label>
        <select class="form-select" wire:model.live="depletionType">
            <option value="mortality">Kematian</option>
            {{-- <option value="sales">Penjualan</option> --}}
            {{-- <option value="mutation">Mutasi</option> --}}
            <option value="culling">Afkir</option>
            {{-- <option value="other">Lainnya</option> --}}
        </select>
        @error('depletionType') <div class="text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="required form-label">Tanggal</label>
        <input type="date" class="form-control" wire:model.live="depletionDate" @if($isEditing) disabled @endif>
        @error('depletionDate') <div class="text-danger mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Alasan</label>
        <input type="text" class="form-control" wire:model.live="reason" placeholder="Alasan depletion (opsional)">
        @error('reason') <div class="text-danger mt-1">{{ $message }}</div> @enderror
    </div>
</div>

<!-- Available Batches -->
<div class="mb-8">
    <h5 class="mb-4">Available Batches</h5>
    @if(count($availableBatches) > 0)
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @foreach($availableBatches as $batch)
        <div class="col">
            <div
                class="card h-100 border @if(collect($selectedBatches)->contains('batch_id', $batch['batch_id'])) border-primary shadow @else border-gray-300 @endif">
                <div class="card-body p-4 d-flex flex-column">
                    <h6 class="mb-2">{{ $batch['batch_name'] }}</h6>
                    <div class="text-muted small mb-3">
                        <div>Age: {{ $batch['age_days'] }} days</div>
                        <div>Available: <span class="fw-bold text-success">{{
                                number_format($batch['available_quantity']) }}</span></div>
                        <div>Utilization: {{ $batch['utilization_rate'] }}%</div>
                    </div>
                    <div class="mt-auto">
                        <button type="button"
                            class="btn btn-sm w-100 @if(collect($selectedBatches)->contains('batch_id', $batch['batch_id'])) btn-light-primary @else btn-primary @endif"
                            wire:click="addBatch('{{ $batch['batch_id'] }}')"
                            @if(collect($selectedBatches)->contains('batch_id', $batch['batch_id'])) disabled @endif>
                            @if(collect($selectedBatches)->contains('batch_id', $batch['batch_id']))
                            <i class="fas fa-check"></i> Selected
                            @else
                            <i class="fas fa-plus"></i> Select
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
        <i class="fas fa-exclamation-triangle me-2"></i> Tidak ada batch yang tersedia untuk depletion.
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
                    <small class="text-muted">Available: {{ number_format($selectedBatch['available_quantity'])
                        }}</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control" wire:model.live="selectedBatches.{{ $index }}.quantity"
                        min="1" max="{{ $selectedBatch['available_quantity'] }}">
                    @error("selectedBatches.{$index}.quantity") <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Note (Optional)</label>
                    <input type="text" class="form-control" wire:model.live="selectedBatches.{{ $index }}.note"
                        placeholder="Catatan untuk batch ini">
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-sm btn-danger" wire:click="removeBatch({{ $index }})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif