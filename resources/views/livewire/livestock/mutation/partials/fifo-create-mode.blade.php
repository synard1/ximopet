<!-- FIFO Create Mode -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-plus-circle me-2"></i>
                Buat Mutasi FIFO
            </h4>
            <button type="button" class="btn btn-sm btn-light" wire:click="closeModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="card-body">
        <!-- Alerts -->
        @include('livewire.livestock.mutation.partials.fifo-alerts')

        <form wire:submit.prevent="generateFifoPreview" class="row g-3">
            <!-- Basic Info -->
            <div class="col-md-6">
                <label class="form-label fw-bold">Tanggal Mutasi</label>
                <input type="date" class="form-control @error('mutationDate') is-invalid @enderror"
                    wire:model.live="mutationDate" wire:change="checkMutations" required>
                @error('mutationDate')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Sumber Ternak</label>
                <div class="input-group">
                    <select class="form-select @error('sourceLivestockId') is-invalid @enderror"
                        wire:model.live="sourceLivestockId" wire:change="checkMutations" required>
                        <option value="">Pilih Sumber Ternak</option>
                        @foreach($livestockOptions as $livestock)
                        <option value="{{ $livestock['id'] }}">
                            {{ $livestock['display_name'] }} ({{ number_format($livestock['current_quantity']) }} ekor)
                        </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-secondary" wire:click="loadSourceLivestock"
                        wire:loading.attr="disabled">
                        <i class="fas fa-sync-alt" wire:loading.remove></i>
                        <i class="fas fa-spinner fa-spin" wire:loading></i>
                    </button>
                </div>
                @error('sourceLivestockId')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Kuantitas</label>
                <div class="input-group">
                    <input type="number" class="form-control @error('quantity') is-invalid @enderror"
                        wire:model="quantity" min="1" required placeholder="0">
                    <span class="input-group-text">ekor</span>
                </div>
                @error('quantity')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if($totalAvailableQuantity > 0)
                <small class="text-muted">Tersedia: {{ number_format($totalAvailableQuantity) }} ekor</small>
                @endif
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Jenis Mutasi</label>
                <select class="form-select @error('type') is-invalid @enderror" wire:model="type" required>
                    <option value="internal">Internal Transfer</option>
                    <option value="external">External Transfer</option>
                    <option value="farm_transfer">Farm Transfer</option>
                </select>
                @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Arah Mutasi</label>
                <select class="form-select @error('direction') is-invalid @enderror" wire:model="direction" required>
                    <option value="out">Keluar (Out)</option>
                    <option value="in">Masuk (In)</option>
                </select>
                @error('direction')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Destination -->
            @if($direction === 'out')
            <div class="col-md-6">
                <label class="form-label">Ternak Tujuan (Opsional)</label>
                <select class="form-select @error('destinationLivestockId') is-invalid @enderror"
                    wire:model="destinationLivestockId">
                    <option value="">Pilih Ternak Tujuan</option>
                    @foreach($livestockOptions as $livestock)
                    @if($livestock['id'] !== $sourceLivestockId)
                    <option value="{{ $livestock['id'] }}">
                        {{ $livestock['display_name'] }}
                    </option>
                    @endif
                    @endforeach
                </select>
                @error('destinationLivestockId')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Kandang Tujuan</label>
                <select class="form-select @error('destinationCoopId') is-invalid @enderror"
                    wire:model="destinationCoopId">
                    <option value="">Pilih Kandang Tujuan</option>
                    @foreach($coopOptions as $coop)
                    <option value="{{ $coop['id'] }}">
                        {{ $coop['display_name'] }}
                    </option>
                    @endforeach
                </select>
                @error('destinationCoopId')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endif

            <div class="col-12">
                <label class="form-label">Alasan Mutasi (Opsional)</label>
                <textarea class="form-control @error('reason') is-invalid @enderror" wire:model="reason" rows="2"
                    placeholder="Masukkan alasan mutasi"></textarea>
                @error('reason')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- FIFO Info -->
            @if($sourceLivestock)
            <div class="col-12">
                <div class="alert alert-info">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="fw-bold text-primary">{{ $availableBatches->count() }}</div>
                            <small>Batch</small>
                        </div>
                        <div class="col-3">
                            <div class="fw-bold text-success">{{ number_format($totalAvailableQuantity) }}</div>
                            <small>Tersedia</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-info">{{ $sourceLivestock->name }}</div>
                            <small>{{ $sourceLivestock->farm->name ?? '' }} - {{ $sourceLivestock->coop->name ?? ''
                                }}</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Submit Button -->
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled"
                    wire:target="generateFifoPreview">
                    <span wire:loading.remove wire:target="generateFifoPreview">
                        <i class="fas fa-eye me-2"></i>
                        Preview Mutasi FIFO
                    </span>
                    <span wire:loading wire:target="generateFifoPreview">
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        Generating Preview...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>