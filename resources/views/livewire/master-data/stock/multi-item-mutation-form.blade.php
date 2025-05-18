<div>
    @if ($showForm)
    <div class="row g-3">
        <x-input.group col="6" label="Tanggal">
            <input type="date" class="form-control" wire:model="date">
            <x-input.error for="date" />
        </x-input.group>
    </div>

    <div class="row g-3">
        <x-input.group col="6" label="Asal">
            <select wire:model="from_livestock_id" class="form-control" id="from_livestock_id" name="from_livestock_id" required>
                <option value="">Pilih</option>
                @foreach($livestocks ?? [] as $livestock)
                    <option value="{{ $livestock->id }}">{{ $livestock->name }} ({{ $livestock->farm->name ?? 'Unknown' }})</option>
                @endforeach
            </select>
            <x-input.error for="from_livestock_id" />
        </x-input.group>

        <x-input.group col="6" label="Tujuan">
            <select wire:model="to_livestock_id" class="form-control" id="to_livestock_id" name="to_livestock_id" required>
                <option value="">Pilih</option>
                @foreach($livestocks ?? [] as $livestock)
                    <option value="{{ $livestock->id }}">{{ $livestock->name }} ({{ $livestock->farm->name ?? 'Unknown' }})</option>
                @endforeach
            </select>
            <x-input.error for="to_livestock_id" />
        </x-input.group>
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
    @endif
</div>
