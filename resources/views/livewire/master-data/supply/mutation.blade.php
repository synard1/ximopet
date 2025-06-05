<div>
    @if ($showForm)
    <h5 class="mb-4 fw-bold">Form Mutasi Supply</h5>
    <form wire:submit.prevent="save">
        <div class="row g-3">
            <x-input.group col="md-6" label="Asal" for="source_farm_id">
                <select wire:model.live="source_farm_id" class="form-select" required @disabled($edit_mode)>
                    <option value="">-- Pilih Asal --</option>
                    @foreach($farms ?? [] as $farm)
                    <option value="{{ $farm->id }}" @selected($farm->id == $source_farm_id)>{{ $farm->name }}</option>
                    @endforeach
                </select>
                <x-input.error for="source_farm_id" />
            </x-input.group>

            <x-input.group col="md-6" label="Tujuan" for="destination_farm_id">
                <select wire:model="destination_farm_id" class="form-select" required @disabled($edit_mode)>
                    <option value="">-- Pilih Tujuan --</option>
                    @foreach($dstFarms ?? [] as $farm)
                    <option value="{{ $farm->id }}" @selected($farm->id == $destination_farm_id)>{{ $farm->name }}
                    </option>
                    @endforeach
                </select>
                <x-input.error for="destination_farm_id" />
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

        <h6 class="mb-3 text-gray-700">Detail Mutasi Supply</h6>

        @foreach($items as $index => $item)
        <div class="row align-items-end mb-3">
            @if (!empty($errorItems[$index]))
            <div class="alert alert-danger py-1 px-2 mb-2">{{ $errorItems[$index] }}</div>
            @endif
            <div class="col-md-3">
                <label class="form-label">Item</label>
                <select class="form-select @error('items.'.$index.'.item_id') is-invalid @enderror"
                    wire:model.live="items.{{ $index }}.item_id">
                    <option value="">-- Pilih Item --</option>
                    @foreach($availableItems as $available)
                    <option value="{{ $available['id'] }}">{{ $available['name'] }} ({{ $available['type'] }})</option>
                    @endforeach
                </select>
                @error('items.'.$index.'.item_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-2">
                <label class="form-label">Unit</label>
                <select class="form-select @error('items.'.$index.'.unit_id') is-invalid @enderror"
                    wire:model="items.{{ $index }}.unit_id">
                    <option value="">-- Pilih Unit --</option>
                    @foreach($items[$index]['units'] ?? [] as $unit)
                    <option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>
                    @endforeach
                </select>
                @error('items.'.$index.'.unit_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-2">
                <label class="form-label">Jumlah</label>
                <input type="number" class="form-control @error('items.'.$index.'.quantity') is-invalid @enderror"
                    step="0.01" wire:model="items.{{ $index }}.quantity" placeholder="0">
                @error('items.'.$index.'.quantity')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-2">
                <label class="form-label">Stock ({{ $items[$index]['smallest_unit_name'] ?? '' }})</label>
                <div class="d-flex justify-content-between">
                    <input type="number" class="form-control me-2"
                        value="{{ $items[$index]['available_stock'] ?? '0' }}" readonly disabled>
                </div>
            </div>

            <div class="col-md-2">
                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-light-danger w-100 mt-4">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
        </div>
        @endforeach


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