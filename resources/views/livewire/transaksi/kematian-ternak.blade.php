<div class="modal" tabindex="-1" role="dialog" id="kt_modal_kternak" tabindex="-1" aria-hidden="true" wire:ignore.self>
    {{-- <div class="modal" tabindex="-1" role="dialog"> --}}
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Data Kematian Ternak</h5>
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                        {!! getIcon('cross','fs-1') !!}
                    </div>
                    <!--end::Close-->
                </div>
                <div class="modal-body">
                    <!--begin::Form-->
                    <form id="kt_modal_kternak_form" class="form" enctype="multipart/form-data">
                            <!--begin::Scroll-->
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_kternak_scroll"
                                data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                                data-kt-scroll-dependencies="#kt_modal_kternak_header"
                                data-kt-scroll-wrappers="#kt_modal_kternak_scroll" data-kt-scroll-offset="300px">

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Pilih Farm</label>
                                    <!--end::Label-->
                                    <!--begin::Select2-->
                                    <select id="farmSelect" name="farmSelect" wire:model="selectedFarm" class="js-select2 form-control">
                                        <option value="">=== Pilih ===</option>

                                        {{-- {{ dd($farms)}} --}}
                                        @foreach ($farms as $farm)
                                        {{-- abc {{ $farm->id}} --}}
                                            <option value="{{ $farm->id }}">{{ $farm->nama }}</option>
                                        @endforeach
                                    </select>
                                    <!--end::Select2-->
                                    @error('selectedFarm')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('selectedFarm')
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
                                    <select id="kandangSelect" name="kandangSelect" wire:model="selectedKandang" class="js-select2 form-control" disabled>
                                        <option value="">=== Pilih Kandang ===</option>
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
                                    <label class="required fw-semibold fs-6 mb-2">Tanggal</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input class="form-control" wire:model="tanggal" placeholder="Pilih Tanggal" id="tanggal" disabled/>
                                    <!--end::Input-->
                                    @error('tanggal')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                    @error('tanggal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!--end::Input group-->
    
                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Jumlah Ternak</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="number" wire:model="jumlah" name="jumlah" id="jumlah" value="{{ $jumlah }}"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Jumlah"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"/>
                                    <!--end::Input-->
                                    @error('jumlah')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Total Berat Ternak ( Gram )</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="number" wire:model="total_berat" name="total_berat" id="total_berat" value="{{ $total_berat }}"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Total Berat Ternak" oninput="this.value = this.value.replace(/[^0-9]/g, '')"//>
                                    <!--end::Input-->
                                    @error('total_berat')
                                    <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Penyebab</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" wire:model="penyebab" name="penyebab" id="penyebab" value="{{ $penyebab }}"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Penyebab">
                                    <!--end::Input-->
                                    @error('penyebab')
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
                    {{-- <button type="button" class="btn btn-secondary" wire:click="closeModalKandang()">Close</button> --}}
                    <button type="button" class="btn btn-primary" wire:click="store()">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    