<div class="modal d-block" tabindex="-1" role="dialog" id="kt_modal_master_supplier" tabindex="-1" aria-hidden="true"
    wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $isEdit ? 'Ubah Data Supplier' : 'Buat Data Supplier' }}</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-dismiss="modal" aria-label="Close"
                    wire:click="closeModal()">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="store" wire:key="{{ $formKey }}">
                    <div class="fv-row mb-7">
                        <label for="kode" class="fw-semibold fs-6 mb-2">Kode</label>
                        <input type="text" class="form-control" wire:model="code" id="kode" {{ $isEdit ? 'disabled' : ''
                            }}>
                        @error('code') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label for="nama" class="required fw-semibold fs-6 mb-2">Nama</label>
                        <input type="text" class="form-control" wire:model="name" id="nama">
                        @error('name') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label for="alamat" class="required fw-semibold fs-6 mb-2">Alamat</label>
                        <input type="text" class="form-control" wire:model="address" id="alamat">
                        @error('address') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Telp</label>
                        <input type="text" id="telp" name="telp" wire:model="phone_number" class="form-control"
                            placeholder="Telp" />
                        @error('phone_number') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Contact Person</label>
                        <input type="text" wire:model="contact_person" name="pic" class="form-control"
                            placeholder="Contact Person" />
                        @error('contact_person') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Telp. Contact Person</label>
                        <input type="text" wire:model="phone_number" name="telp_pic" class="form-control"
                            placeholder="Telp. Contact Person" />
                        @error('phone_number') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label for="email" class="required fw-semibold fs-6 mb-2">Email</label>
                        <input type="email" class="form-control" wire:model="email" id="email">
                        @error('email') <span class="text-danger error">{{ $message}}</span>@enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeModal()">Batal</button>
                <button type="button" class="btn btn-primary" wire:click="store()">{{ $isEdit ? 'Simpan Perubahan' :
                    'Simpan' }}</button>
            </div>
        </div>
    </div>
</div>