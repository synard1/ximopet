<div>
    @if ($showForm)
    <h5 class="mb-4 fw-bold">Form Mutasi Ayam</h5>
    <form wire:submit.prevent="save">
        <div class="row g-3">
            <x-input.group col="md-6" label="Asal" for="source_livestock_id">
                <select wire:model.live="source_livestock_id" class="form-select" required @disabled($edit_mode)>
                    <option value="">-- Pilih Asal --</option>
                    @foreach($srcLivestocks ?? [] as $livestock)
                    <option value="{{ $livestock->id }}" @selected($livestock->id == $source_livestock_id)>
                        {{ $livestock->name }} (Stok: {{ $livestock->currentLivestock?->quantity ?? 0 }} ekor)
                    </option>
                    @endforeach
                </select>
                <x-input.error for="source_livestock_id" />
            </x-input.group>

            <x-input.group col="md-6" label="Tujuan" for="destination_livestock_id">
                <select wire:model="destination_livestock_id" class="form-select" required @disabled($edit_mode)>
                    <option value="">-- Pilih Tujuan --</option>
                    @foreach($dstLivestocks ?? [] as $livestock)
                    <option value="{{ $livestock->id }}" @selected($livestock->id == $destination_livestock_id)>
                        {{ $livestock->name }}
                    </option>
                    @endforeach
                </select>
                <x-input.error for="destination_livestock_id" />
            </x-input.group>

            <x-input.group col="md-6" label="Tanggal Mutasi" for="tanggal">
                <input type="date" wire:model="tanggal" class="form-control" required @disabled($edit_mode)>
                <x-input.error for="tanggal" />
            </x-input.group>

            <x-input.group col="md-6" label="Catatan" for="notes">
                <textarea class="form-control" wire:model="notes" rows="3"
                    placeholder="Tambahkan catatan (opsional)"></textarea>
                <x-input.error for="notes" />
            </x-input.group>
        </div>

        <h6 class="mb-3 text-gray-700">Detail Mutasi Ayam</h6>

        @foreach($items as $index => $item)
        <div class="item-row d-flex flex-wrap align-items-end mb-3 gap-2">
            @if (!empty($errorItems[$index]))
            <div class="alert alert-danger py-1 px-2 mb-2 w-100">{{ $errorItems[$index] }}</div>
            @endif

            <div class="flex-grow-1" style="min-width:220px;max-width:260px;">
                <label class="form-label">Jumlah Mutasi</label>
                <input type="number" class="form-control @error('items.'.$index.'.quantity') is-invalid @enderror"
                    step="1" wire:model.live="items.{{ $index }}.quantity" placeholder="0" min="1"
                    max="{{ $item['available_quantity'] }}">
                @error('items.'.$index.'.quantity')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Stok tersedia: {{ $item['available_quantity'] }} ekor</small>
            </div>

            <div class="flex-grow-1" style="min-width:220px;max-width:260px;">
                <label class="form-label">Berat Total (gram)</label>
                <input type="number" class="form-control @error('items.'.$index.'.weight') is-invalid @enderror"
                    step="0.01" wire:model="items.{{ $index }}.weight" placeholder="0" min="0.01"
                    max="{{ $item['available_weight'] }}" readonly>
                @error('items.'.$index.'.weight')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">
                    Berat rata-rata per ekor:
                    {{ $item['quantity'] > 0 ? number_format($item['weight'] / $item['quantity'], 2) : '-' }} gram
                </small>
            </div>

            <div class="flex-grow-1" style="min-width:220px;max-width:260px;">
                <a href="#" class="btn btn-light-danger w-100 mb-1">
                    <i class="bi bi-trash"></i> Hapus
                </a>
                {{-- <button type="button" wire:click="removeItem({{ $index }})"
                    class="btn btn-light-danger w-100 mb-1">
                    <i class="bi bi-trash"></i> Hapus
                </button> --}}
                <small class="text-muted">
                    Estimasi Berat Total:
                    {{ formatNumber($total_weight_estimation / 1000, 2) }} Kg
                    {{-- {{ $item['quantity'] > 0 ? number_format($item['weight'] / $item['quantity'], 2) : '-' }} gram
                    --}}

                </small>
            </div>
        </div>
        @endforeach

        <!--begin::Error Messages at Bottom-->
        <div class="mt-3">
            @if(session()->has('validation-errors'))
            <div class="alert alert-danger">
                <ul>
                    @foreach(session('validation-errors')['errors'] as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        <!--end::Error Messages at Bottom-->

        <div class="mb-4">
            <button type="button" wire:click="addItem" class="btn btn-light-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Item
            </button>
        </div>

        <div class="d-flex justify-content-end">
            <button type="button" wire:click="cancel" class="btn btn-secondary me-2">
                <i class="bi bi-x-lg"></i> Batal
            </button>
            <button type="submit" class="btn btn-warning text-white">
                <i class="bi bi-save me-1"></i> Simpan Mutasi
            </button>
        </div>
    </form>

    @error('save_error')
    <div class="alert alert-danger mt-3">{{ $message }}</div>
    @enderror
    @endif
</div>