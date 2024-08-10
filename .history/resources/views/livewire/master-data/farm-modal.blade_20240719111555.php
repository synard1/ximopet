<div class="modal" tabindex="-1" role="dialog" id="kt_modal_new_farm" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Data Farm</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <!--begin::Form-->
                <form id="kt_modal_master_farm_form" class="form" enctype="multipart/form-data">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_master_farm_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_master_farm_header"
                            data-kt-scroll-wrappers="#kt_modal_master_farm_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Kode Farm</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" wire:model="kode_farm" id="kode_farm"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kode Farm" />
                                <!--end::Input-->
                                @error('kode_farm')
                                <span class="text-danger">{{ $message }}</span> @enderror
                                @error('kode_farm')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Nama Farm</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" wire:model="nama" name="nama" id="nama" value="{{ $nama }}"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama Farm"/>
                                <!--end::Input-->
                                @error('nama')
                                <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Alamat Farm</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" id="alamat" name="alamat" wire:model="alamat" value="{{ $alamat }}"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Alamat Farm" />
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

                        </div>
                        <!--end::Scroll-->
                    </form>
                    <!--end::Form-->
                {{-- <form>
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" wire:model="nama" id="nama">
                        @error('nama') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                </form> --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="storeFarm()">Save changes</button>
            </div>
        </div>
    </div>
</div>
