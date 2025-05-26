<div>
    @if ($showForm)
    <form wire:submit.prevent="save">
        {{-- === Informasi Utama Customer === --}}
        <div class="row g-3">
            <x-input.group col="6" label="Kode">
                <input type="text" wire:model="code" class="form-control" placeholder="Masukkan Kode Customer">
                <x-input.error for="code" />
            </x-input.group>

            <x-input.group col="6" label="Nama Customer">
                <input type="text" wire:model="name" class="form-control" placeholder="Masukkan Nama Customer">
                <x-input.error for="name" />
            </x-input.group>

            <x-input.group col="12" label="Alamat">
                <textarea wire:model="address" class="form-control" rows="3" placeholder="Masukkan Alamat"></textarea>
                <x-input.error for="address" />
            </x-input.group>

            <x-input.group col="6" label="Nomor Telepon">
                <input type="text" wire:model="phone_number" class="form-control" placeholder="Masukkan Nomor Telepon">
                <x-input.error for="phone_number" />
            </x-input.group>

            <x-input.group col="6" label="Contact Person">
                <input type="text" wire:model="contact_person" class="form-control"
                    placeholder="Masukkan Contact Person">
                <x-input.error for="contact_person" />
            </x-input.group>

            <x-input.group col="6" label="Email">
                <input type="email" wire:model="email" class="form-control" placeholder="Masukkan Email">
                <x-input.error for="email" />
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
            <button type="button" class="btn btn-secondary me-2" wire:click="closeModalCustomer">Batal</button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan
            </button>
        </div>
    </form>
    @endif
</div>