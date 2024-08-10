<div class="modal" tabindex="-1" role="dialog" id="kt_modal_new_supplier" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Data Customer</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <!--begin::Form-->
                <form id="kt_modal_master_supplier_form" class="form" enctype="multipart/form-data">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_master_supplier_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_master_supplier_header"
                            data-kt-scroll-wrappers="#kt_modal_master_supplier_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Kode Customer</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" wire:model="kode_supplier" id="kode_supplier"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kode Customer" />
                                <!--end::Input-->
                                @error('kode_supplier')
                                <span class="text-danger">{{ $message }}</span> @enderror
                                @error('kode_supplier')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Nama Customer</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" wire:model="name" name="name" id="name" value="{{ $name }}"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama Customer"/>
                                <!--end::Input-->
                                @error('name')
                                <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Alamat Customer</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" id="alamat" name="alamat" wire:model="alamat" value="{{ $alamat }}"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Alamat Customer" />
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
                            <div class="mb-4">
                                <label for="email" class="required fw-semibold fs-6 mb-2">Email</label>
                                <input type="email" id="email" wire:model="email" value="{{ $email }}" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Email">
                                @error('email')
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
                <button type="button" class="btn btn-primary" wire:click="storeCustomer()">Save changes</button>
            </div>
        </div>
    </div>
</div>
