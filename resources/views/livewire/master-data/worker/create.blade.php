<div>
    @if ($showForm)
        <form wire:submit.prevent="save">
            {{-- === Informasi Utama Pekerja === --}}
            <div class="row g-3">
                <x-input.group col="6" label="Nama Pekerja">
                    <input type="text" wire:model="name" class="form-control" placeholder="Masukkan Nama Pekerja">
                    <x-input.error for="name" />
                </x-input.group>

                <x-input.group col="6" label="Nomor Telepon">
                    <input type="text" wire:model="phone" class="form-control" placeholder="Masukkan Nomor Telepon">
                    <x-input.error for="phone" />
                </x-input.group>

                <x-input.group col="12" label="Alamat">
                    <textarea wire:model="address" class="form-control" rows="3" placeholder="Masukkan Alamat"></textarea>
                    <x-input.error for="address" />
                </x-input.group>

                <x-input.group col="6" label="Status">
                    <select wire:model="status" class="form-select">
                        <option value="">-- Select Status --</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="on_leave">On Leave</option>
                        {{-- <option value="suspended">Suspended</option>
                        <option value="terminated">Terminated</option>
                        <option value="probation">Probation</option>
                        <option value="part_time">Part-Time</option>
                        <option value="full_time">Full-Time</option>
                        <option value="contract">Contract</option>
                        <option value="temporary">Temporary</option>
                        <option value="resigned">Resigned</option>
                        <option value="retired">Retired</option>
                        <option value="deceased">Deceased</option> --}}
                        <option value="blacklist">Blacklist</option>
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