<div class="modal" tabindex="-1" role="dialog" id="kt_modal_new_stok" tabindex="-1" aria-hidden="true" wire:ignore.self>
    {{-- <div class="modal" tabindex="-1" role="dialog"> --}}
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Data Stok</h5>
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                        {!! getIcon('cross','fs-1') !!}
                    </div>
                    <!--end::Close-->
                </div>
                <div class="modal-body">
                    <!--begin::Form-->
                    <form id="kt_modal_master_stok_form" class="form" enctype="multipart/form-data">
                            <!--begin::Scroll-->
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_master_stok_scroll"
                                data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                                data-kt-scroll-dependencies="#kt_modal_master_stok_header"
                                data-kt-scroll-wrappers="#kt_modal_master_stok_scroll" data-kt-scroll-offset="300px">
    
                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Kode Stok</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" wire:model="kode_stok" id="kode_stok"
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
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kapasitas Stok"/>
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
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kapasitas Stok"/>
                                    <!--end::Input-->
                                    @error('satuan_kecil')
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
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kapasitas Stok"/>
                                    <!--end::Input-->
                                    @error('satuan_kecil')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Scroll-->
                        </form>
                        <!--end::Form-->
                    {{-- <p>Your Dynamic Number: {{ $dynamicNumber }}</p>
                    <p>URL: {{ $currentUrl }}</p> --}}
    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    {{-- <button type="button" class="btn btn-secondary" wire:click="closeModalStok()">Close</button> --}}
                    <button type="button" class="btn btn-primary" wire:click="storeStok()">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    