<div class="modal fade" tabindex="-1" data-bs-backdrop="static" role="dialog" id="kt_modal_add_kternak" tabindex="-1"
    aria-hidden="true" wire:ignore.self>
    {{-- <div class="modal" tabindex="-1" role="dialog"> --}}
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Kematian Ternak</h5>
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                        {!! getIcon('cross','fs-1') !!}
                    </div>
                    <!--end::Close-->
                </div>
                <div class="modal-body">
                    <!--begin::Form-->
                    <form id="kt_modal_master_doc_form" class="form" enctype="multipart/form-data">
                        <input type="hidden" wire:model="parent_id" name="parent_id" />
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_master_doc_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_master_doc_header"
                            data-kt-scroll-wrappers="#kt_modal_master_doc_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Row-->
                            <div class="row gx-10 mb-5">
                                <!--begin::Col-->
                                <div class="col-lg-6">
                                    <label class="form-label fs-6 fw-bold text-gray-700 mb-3">Pembelian</label>

                                    <!--begin::Input group-->
                                    <div class="mb-5">
                                        <label class="form-label fs-8 mb-3">No. Faktur</label>
                                        <input type="text" wire:model="faktur" id="faktur"
                                            class="form-control form-control-solid" placeholder="No. Faktur" @if ($edit_mode == true) readonly disabled
                                                
                                            @endif>
                                        @error('faktur')
                                        <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('faktur')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Input group-->
                                    <div class="mb-5">
                                        <label class="form-label fs-8 mb-3">Tanggal</label>
                                        <input wire:model="tanggal" id="tanggal" class="form-control form-control-solid"
                                            placeholder="Tanggal" @if ($edit_mode == true) readonly disabled
                                                
                                            @endif x-data 
                                            x-init="flatpickr($el, {
                                                enableTime: true,
                                                dateFormat: 'Y-m-d',
                                                defaultDate: '{{ $tanggal }}', // Set initial date from Livewire
                                            })">
                                        @error('tanggal')
                                        <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('tanggal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Input group-->
                                    <div class="mb-5">
                                        <label class="form-label fs-8 mb-3">Supplier</label>
                                        <!--begin::Select2-->
                                        <select id="supplierSelect" name="supplierSelect" wire:model="supplierSelect"
                                            class="js-select2 form-control">
                                            <option value="">=== Pilih Supplier ===</option>
                                            @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->kode }} -- {{ $supplier->nama }}</option>
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
                                </div>
                                <!--end::Col-->

                                <!--begin::Col-->
                                <div class="col-lg-6">
                                    <label class="form-label fs-6 fw-bold text-gray-700 mb-3">Tujuan</label>

                                    <!--begin::Input group-->
                                    <div class="mb-5">
                                        <label class="form-label fs-8 mb-3">Kandang</label>
                                        <!--begin::Select2-->
                                        <select id="selectedKandang" name="selectedKandang" wire:model="selectedKandang"
                                            class="js-select2 form-control">
                                            <option value="">=== Pilih Kandang ===</option>
                                            @foreach ($kandangs as $kandang)
                                            <option value="{{ $kandang->id }}" @if($selectedKandang == $kandang->id) selected @endif>{{ $kandang->farms->nama }} - {{
                                                $kandang->kode
                                                }} - {{ $kandang->nama }}</option>
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
                                    <div class="mb-5">
                                        <label class="form-label fs-8 mb-3">Periode</label>
                                        <!--begin::Input-->
                                        <input type="text" wire:model="periode" id="periode"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Periode" />
                                        <!--end::Input-->
                                        @error('periode')
                                        <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('periode')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <!--end::Input group-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->

                            <!--begin::Separator-->
                            <div class="separator separator-dashed my-5"></div>
                            <!--end::Separator-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Pilih DOC</label>
                                <!--end::Label-->
                                <!--begin::Select2-->
                                <select id="docSelect" name="docSelect" wire:model="docSelect"
                                    class="js-select2 form-control">
                                    <option value="">=== Pilih ===</option>
                                    @foreach ($docs as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->kode }} -- {{ $doc->nama }}</option>
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
                                <label class="required fw-semibold fs-6 mb-2">Harga Satuan</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="number" wire:model="harga" id="harga"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Harga Satuan" />
                                <!--end::Input-->
                                @error('harga')
                                <span class="text-danger">{{ $message }}</span> @enderror
                                @error('harga')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Total Berat ( Gram )</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="number" wire:model="berat" id="berat"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Total Berat ( Gram )" />
                                <!--end::Input-->
                                @error('berat')
                                <span class="text-danger">{{ $message }}</span> @enderror
                                @error('berat')
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
                    {{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> --}}
                    <button type="button" class="btn btn-secondary" id="closeModalBtn" data-bs-dismiss="modal" wire:click="resetFormAndErrors" >Close</button>

                    <button type="button" class="btn btn-primary" wire:click="storeDOC()">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $("#tanggal").flatpickr();


        const yourModal = document.getElementById('kt_modal_new_doc');
        const kandangSelect = document.getElementById('selectedKandang');
    </script>
    @endpush