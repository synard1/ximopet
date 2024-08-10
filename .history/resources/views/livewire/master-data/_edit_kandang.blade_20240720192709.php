<div class="modal d-block" tabindex="-1" role="dialog" id="kt_modal_master_kandang" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $kandang_id ? 'Ubah Data Kandang' : 'Buat Data Kandang' }}</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-dismiss="modal" aria-label="Close" wire:click="closeModal()">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <form>
                    <div class="fv-row mb-7">
                        <label for="kode" class="fw-semibold fs-6 mb-2">Kode</label>
                        <input type="text" class="form-control" wire:model="kode" id="kode" {{ $kandang_id ? 'disabled' : '' }}>
                        @error('kode') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label for="nama" class="required fw-semibold fs-6 mb-2">Nama</label>
                        <input type="text" class="form-control" wire:model="nama" id="nama">
                        @error('nama') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label for="kapasitas" class="required fw-semibold fs-6 mb-2">Kapasitas</label>
                        <input type="text" class="form-control" wire:model="kapasitas" id="kapasitas">
                        @error('kapasitas') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Status</label>
                        <!--end::Label-->
                        <!--begin::Select2-->
                        <select id="status" name="status" wire:model="status" class="js-select2 form-control">
                            <option value="">=== Pilih ===</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                        <!--end::Select2-->
                        @error('farm_id')
                        <span class="text-danger">{{ $message }}</span> @enderror
                        @error('farm_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group-->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeModal()">Close</button>
                <button type="button" class="btn btn-primary" wire:click="store()">Save changes</button>
            </div>
        </div>
    </div>
</div>
