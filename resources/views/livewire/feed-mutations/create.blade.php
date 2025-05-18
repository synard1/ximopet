<div>
    <div class="mb-3">
        <label class="form-label">Tanggal</label>
        <input type="date" class="form-control" wire:model="date">
    </div>

    <div class="mb-3">
        <label class="form-label">Dari Ternak</label>
        <select class="form-select" wire:model.live="from_ternak_id">
            <option value="">-- Pilih Ternak --</option>
            @foreach($livestocks as $livestock)
                <option value="{{ $livestock->id }}">{{ $livestock->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Ke Ternak</label>
        <select class="form-select" wire:model="to_ternak_id">
            <option value="">-- Pilih Ternak --</option>
            @foreach($livestocks as $livestock)
                <option value="{{ $livestock->id }}">{{ $livestock->name }}</option>
            @endforeach
        </select>
    </div>

    <h5>Detail Mutasi Pakan & Supply</h5>
    @foreach($items as $index => $item)
        <div class="row mb-2">
            <div class="col-md-4">
                <select class="form-select" wire:model.live="items.{{ $index }}.item_id">
                    <option value="">-- Pilih Item --</option>
                    @foreach($availableItems as $available)
                        <option value="{{ $available['id'] }}">{{ $available['name'] }} ({{ $available['type'] }})</option>
                    @endforeach
                </select>
                <span class="text-muted small">Stok: {{ $items[$index]['available_stock'] ?? '-' }}</span>
            </div>

            <div class="col-md-3">
                <select class="form-select" wire:model="items.{{ $index }}.unit_id">
                    <option value="">-- Pilih Unit --</option>
                    @foreach($items[$index]['units'] ?? [] as $unit)
                        <option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <input type="number" step="0.01" class="form-control" wire:model="items.{{ $index }}.quantity" placeholder="Jumlah">
            </div>

            <div class="col-md-1">
                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-danger btn-sm">🗑</button>
            </div>
        </div>
    @endforeach

    <button type="button" wire:click="addItem" class="btn btn-outline-primary mb-3">+ Tambah Item</button>


    <div class="mt-3">
        <button type="button" class="btn btn-secondary" wire:click="close()">Close</button>
        <button type="button" wire:click="save" class="btn btn-success">💾 Simpan Mutasi</button>
    </div>

    @error('save_error') <div class="alert alert-danger mt-2">{{ $message }}</div> @enderror
</div>
