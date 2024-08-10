<div>
<div class="modal" tabindex="-1" role="dialog" id="kt_modal_new_kandang" tabindex="-1" aria-hidden="true" wire:ignore.self>

{{-- <div class="modal" tabindex="-1" role="dialog"> --}}
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Data Kandang</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                @if ($noFarmMessage)
                    <div class="alert alert-warning">{{ $noFarmMessage }}</div>
                @else
                <!--begin::Form-->
                <form id="kt_modal_master_kandang_form" class="form" enctype="multipart/form-data">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_master_kandang_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_master_kandang_header"
                            data-kt-scroll-wrappers="#kt_modal_master_kandang_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Pilih Farm</label>
                                <!--end::Label-->
                                <!--begin::Select2-->
                                <select id="category" wire:model="selectedFarm" name="farms" class="js-select2 form-control">
                                    <option value="">=== Pilih ===</option>
                                    @foreach ($farms as $farm)
                                        <option value="{{ $farm->id }}">{{ $farm->nama }}</option>
                                    @endforeach
                                </select>
                                <!--end::Select2-->
                                @error('kode_kandang')
                                <span class="text-danger">{{ $message }}</span> @enderror
                                @error('kode_kandang')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Kode Kandang</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" wire:model="kode_kandang" id="kode_kandang"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kode Kandang" />
                                <!--end::Input-->
                                @error('kode_kandang')
                                <span class="text-danger">{{ $message }}</span> @enderror
                                @error('kode_kandang')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Nama Kandang</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" wire:model="nama" name="nama" id="nama" value="{{ $nama }}"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama Kandang"/>
                                <!--end::Input-->
                                @error('nama')
                                <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Alamat Kandang</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" id="alamat" name="alamat" wire:model="alamat" value="{{ $alamat }}"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Alamat Kandang" />
                                <!--end::Input-->
                                @error('alamat')
                                <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Telp</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" id="telp" name="telp" wire:model="telp" value="{{ $telp }}" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Telp"/>
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
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Contact Person" />
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
                        </div>
                        <!--end::Scroll-->
                    </form>
                    <!--end::Form-->
                @endif
                <p>Your Dynamic Number: {{ $dynamicNumber }}</p>
                <p>URL: {{ $currentUrl }}</p>

            </div>
            <div class="modal-footer">
                {{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> --}}
                <button type="button" class="btn btn-secondary" wire:click="closeModal()">Close</button>
                <button type="button" class="btn btn-primary" wire:click="storeKandang()">Save changes</button>
            </div>
        </div>
    </div>
</div>
</div>
