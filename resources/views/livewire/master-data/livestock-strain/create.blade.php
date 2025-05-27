<div>
    @if ($showForm)
    <form wire:submit.prevent="save">
        {{-- === Informasi Utama Livestock Strain === --}}
        <div class="row g-3">
            <x-input.group col="6" label="Kode">
                <input type="text" wire:model="code" class="form-control" placeholder="Masukkan Kode Strain" {{
                    $edit_mode ? 'readonly disabled' : '' }}>
                <x-input.error for="code" />
            </x-input.group>

            <x-input.group col="6" label="Nama Strain">
                <input type="text" wire:model="name" class="form-control" placeholder="Masukkan Nama Strain">
                <x-input.error for="name" />
            </x-input.group>

            <x-input.group col="12" label="Deskripsi">
                <textarea wire:model="description" class="form-control" rows="3"
                    placeholder="Masukkan Deskripsi"></textarea>
                <x-input.error for="description" />
            </x-input.group>

            <x-input.group col="6" label="Status">
                <select wire:model="status" class="form-select">
                    <option value="">-- Pilih Status --</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <x-input.error for="status" />
            </x-input.group>
        </div>

        {{-- === Action Buttons === --}}
        <div class="d-flex justify-content-end my-4">
            <button type="button" class="btn btn-secondary me-2" wire:click="close">Batal</button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan
            </button>
        </div>
    </form>
    @endif
</div>