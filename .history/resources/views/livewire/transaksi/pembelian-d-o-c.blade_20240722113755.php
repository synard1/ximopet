<div class="modal" tabindex="-1" role="dialog" id="kt_modal_new_doc" tabindex="-1" aria-hidden="true" wire:ignore.self>
    {{-- <div class="modal" tabindex="-1" role="dialog"> --}}
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Data DOC</h5>
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                        {!! getIcon('cross','fs-1') !!}
                    </div>
                    <!--end::Close-->
                </div>
                <div class="modal-body">
                    <!--begin::Form-->
                    <form id="kt_modal_master_doc_form" class="form" enctype="multipart/form-data">
                            <!--begin::Scroll-->
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_master_doc_scroll"
                                data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                                data-kt-scroll-dependencies="#kt_modal_master_doc_header"
                                data-kt-scroll-wrappers="#kt_modal_master_doc_scroll" data-kt-scroll-offset="300px">
                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">No. Faktur</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" wire:model="faktur" id="faktur"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="No. Faktur" />
                                    <!--end::Input-->
                                    @error('faktur')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('faktur')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Tanggal Pembelian</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input class="form-control" placeholder="Pilih Tanggal Pembelian" id="kt_datepicker_1"/>
                                    {{-- <input type="datetime" wire:model="tanggal_pembelian" id="tanggal_pembelian"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Tanggal Pembelian" /> --}}
                                    <!--end::Input-->
                                    @error('tanggal_pembelian')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('tanggal_pembelian')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Pilih Supplier</label>
                                    <!--end::Label-->
                                    <!--begin::Select2-->
                                    <select id="supplierSelect" name="supplierSelect" wire:model="supplierSelect" class="js-select2 form-control">
                                        <option value="">=== Pilih ===</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->nama }}</option>
                                        @endforeach
                                    </select>
                                    <!--end::Select2-->
                                    @error('supplierSelect')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('supplierSelect')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Pilih DOC</label>
                                    <!--end::Label-->
                                    <!--begin::Select2-->
                                    <select id="docSelect" name="docSelect" wire:model="docSelect" class="js-select2 form-control">
                                        <option value="">=== Pilih ===</option>
                                        @foreach ($docs as $doc)
                                            <option value="{{ $doc->id }}">{{ $doc->nama }}</option>
                                        @endforeach
                                    </select>
                                    <!--end::Select2-->
                                    @error('docSelect')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('docSelect')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Jumlah</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="number" wire:model="qty" id="qty"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Jumlah" />
                                    <!--end::Input-->
                                    @error('qty')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('qty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Pilih Kandang</label>
                                    <!--end::Label-->
                                    <!--begin::Select2-->
                                    <select id="selectedKandang" name="selectedKandang" wire:model="selectedKandang" class="js-select2 form-control">
                                        <option value="">=== Pilih ===</option>
                                        @foreach ($kandangs as $kandang)
                                            <option value="{{ $kandang->id }}">{{ $kandang->farms->nama }} - {{ $kandang->kode }} - {{ $kandang->nama }}</option>
                                        @endforeach
                                    </select>
                                    <!--end::Select2-->
                                    @error('selectedKandang')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('selectedKandang')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Periode</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" wire:model="periode" id="periode"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Periode" />
                                    <!--end::Input-->
                                    @error('periode')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('periode')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                    {{-- <button type="button" class="btn btn-secondary" wire:click="closeModalDOC()">Close</button> --}}
                    <button type="button" class="btn btn-primary" wire:click="storeDOC()">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    
    @push('name')
        
    @endpush