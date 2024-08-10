<div class="modal d-block" tabindex="-1" role="dialog" id="kt_modal_master_stok" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $stok_id ? 'Ubah Data Kandang' : 'Buat Data Kandang' }}</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-dismiss="modal" aria-label="Close" wire:click="closeModal()">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <form>
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Jenis</label>
                        <!--end::Label-->
                        <!--begin::Select2-->
                        <select id="status" name="jenis" wire:model="jenis" class="js-select2 form-control">
                            <option value="" selected>=== Pilih ===</option>
                                <option value="DOC">DOC</option>
                                <option value="Pakan">Pakan</option>
                                <option value="Obat">Obat</option>
                                <option value="Vaksin">Vaksin</option>
                                <option value="Lainnya">Lainnya</option>
                        </select>
                        <!--end::Select2-->
                        @error('jenis')
                        <span class="text-danger">{{ $message }}</span> @enderror
                        @error('jenis')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Kode Stok</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="kode" id="kode"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kode Stok" />
                        <!--end::Input-->
                        @error('kode_stok')
                        <span class="text-danger">{{ $message }}</span> @enderror
                        @error('kode_stok')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Nama Stok</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="nama" name="nama" id="nama" value="{{ $nama }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama Stok"/>
                        <!--end::Input-->
                        @error('nama')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Satuan Besar</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="satuan_besar" name="satuan_besar" id="satuan_besar" value="{{ $satuan_besar }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Satuan Terbesar"/>
                        <!--end::Input-->
                        @error('satuan_besar')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Satuan Kecil</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="satuan_kecil" name="satuan_kecil" id="satuan_kecil" value="{{ $satuan_kecil }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Satuan Terkecil"/>
                        <!--end::Input-->
                        @error('satuan_kecil')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Konversi</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="konversi" name="konversi" id="konversi" value="{{ $konversi }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Konversi Satuan Besar ke Kecil"/>
                        <!--end::Input-->
                        @error('konversi')
                        <span class="text-danger">{{ $message }}</span> @enderror
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
