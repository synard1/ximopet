<div>
    @if ($showForm)
    <form wire:submit.prevent="save">
        {{-- === Informasi Utama Unit === --}}
        <div class="row g-3">
            <x-input.group col="6" label="Tipe Unit">
                <select wire:model="type" class="form-select">
                    @foreach($unitTypes as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
                <x-input.error for="type" />
            </x-input.group>

            <x-input.group col="6" label="Kode Unit">
                <input type="text" wire:model="code" class="form-control" placeholder="Masukkan Kode Unit" @if($edit_mode) readonly @endif>
                <x-input.error for="code" />
            </x-input.group>

            <x-input.group col="12" label="Simbol Unit">
                <input type="text" wire:model="symbol" class="form-control" placeholder="Masukkan Simbol Unit">
                <x-input.error for="symbol" />
            </x-input.group>

            <x-input.group col="6" label="Nama Unit">
                <input type="text" wire:model="name" class="form-control" placeholder="Masukkan Nama Unit">
                <x-input.error for="name" />
            </x-input.group>

            <x-input.group col="6" label="Status">
                <select wire:model="status" class="form-select">
                    <option value="">-- Select Status --</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    {{-- You can add other statuses as needed --}}
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