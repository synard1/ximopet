<div>
    <div class="mb-3">
        <label for="date" class="form-label">Tanggal</label>
        <input type="date" class="form-control" wire:model="date">
    </div>

    <div class="mb-3">
        <label for="from_ternak_id" class="form-label">Dari Ternak</label>
        <select class="form-select" wire:model="from_ternak_id">
            <option value="">-- Pilih Ternak --</option>
            @foreach($livestocks as $livestock)
                <option value="{{ $livestock->id }}">{{ $livestock->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="to_ternak_id" class="form-label">Ke Ternak</label>
        <select class="form-select" wire:model="to_ternak_id">
            <option value="">-- Pilih Ternak --</option>
            @foreach($livestocks as $livestock)
                <option value="{{ $livestock->id }}">{{ $livestock->name }}</option>
            @endforeach
        </select>
    </div>

    <h5>Detail Mutasi Pakan</h5>
    @foreach($items as $index => $item)
        <div class="row mb-2">
            <div class="col-md-6">
                <select class="form-select" wire:model="items.{{ $index }}.feed_id">
                    <option value="">-- Pilih Pakan --</option>
                    @foreach($feeds as $feed)
                        <option value="{{ $feed->id }}">{{ $feed->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" step="0.01" class="form-control" wire:model="items.{{ $index }}.quantity" placeholder="Jumlah (Kg)">
            </div>
            <div class="col-md-2">
                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-danger btn-sm">Hapus</button>
            </div>
        </div>
    @endforeach

    <button type="button" wire:click="addItem" class="btn btn-outline-primary mb-3">+ Tambah Pakan</button>

    <div>
        <button type="button" class="btn btn-secondary" wire:click="close()">Close</button> 

    
        <button type="button" wire:click="save" class="btn btn-success">💾 Simpan Mutasi</button>
    </div>

    @error('save_error') <div class="alert alert-danger mt-2">{{ $message }}</div> @enderror
</div>
