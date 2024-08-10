<div class="modal d-block" tabindex="-1" role="dialog" id="kt_modal_master_supplier" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $supplier_id ? 'Ubah Data Supplier' : 'Buat Data Supplier' }}</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-dismiss="modal" aria-label="Close" wire:click="closeModal()">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="kode">Kode</label>
                        <input type="text" class="form-control" wire:model="kode" id="kode" {{ $supplier_id ? 'disabled' : '' }}>
                        @error('kode') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" wire:model="nama" id="nama">
                        @error('nama') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <input type="text" class="form-control" wire:model="alamat" id="alamat">
                        @error('alamat') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Telp</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" id="telp" name="telp" wire:model="telp" value="{{ $telp }}" class="form-control" placeholder="Telp"/>
                        <!--end::Input-->
                        @error('telp')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Contact Person</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="pic" name="pic" value="{{ $pic }}"
                        class="form-control" placeholder="Contact Person" />
                        <!--end::Input-->
                        @error('pic')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Telp. Contact Person</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="telp_pic" name="telp_pic" value="{{ $telp_pic }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Telp. Contact Person" />
                        <!--end::Input-->
                        @error('telp_pic')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" wire:model="email" id="email">
                        @error('email') <span class="text-danger error">{{ $message}}</span>@enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeModal()">Close</button>
                <button type="button" class="btn btn-primary" wire:click="store()">Save changes</button>
            </div>
        </div>
    </div>
</div>
