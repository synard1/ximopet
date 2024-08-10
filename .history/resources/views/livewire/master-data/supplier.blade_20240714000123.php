<div class="modal fade" id="kt_modal_master_supplier" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header" id="kt_modal_master_supplier_header">
                <!--begin::Modal title-->
                {{-- <h2 class="fw-bold">Tambah Data Supplier</h2> --}}
                <h2 class="fw-bold">{{ $edit_mode === true ? 'Ubah Data Supplier' : 'Tambah Data Supplier' }}</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" aria-label="Close">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body px-5 my-7">
                <!--begin::Form-->
                {{-- <form id="kt_modal_master_supplier_form" class="form"  action="#" wire:submit="submit" enctype="multipart/form-data"> --}}
                <form id="kt_modal_master_supplier_form" class="form"  action="#" wire:submit.prevent="{{ $edit_mode === true ? 'update' : 'submit' }}" enctype="multipart/form-data">
                    <input type="hidden" wire:model="supplier_id" name="supplier_id" value="{{ $supplier_id }}"/>
                    <!--begin::Scroll-->
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_master_supplier_scroll"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                        data-kt-scroll-dependencies="#kt_modal_master_supplier_header"
                        data-kt-scroll-wrappers="#kt_modal_master_supplier_scroll" data-kt-scroll-offset="300px">
                        <!--begin::Input group-->
                        <div class="fv-row mb-7">
                            <!--begin::Label-->
                            <label class="required fw-semibold fs-6 mb-2">Kode Supplier</label>
                            <!--end::Label-->
                            <!--begin::Input-->
                            <input type="text" wire:model="kode_supplier" name="kode_supplier" value="{{ $kode_supplier }}"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kode Supplier" />
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
                            <label class="required fw-semibold fs-6 mb-2">Nama Supplier</label>
                            <!--end::Label-->
                            <!--begin::Input-->
                            <input type="text" wire:model="name" name="name" id="name" value="{{ $name }}"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama Supplier"/>
                            <!--end::Input-->
                            @error('name')
                            <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row mb-7">
                            <!--begin::Label-->
                            <label class="required fw-semibold fs-6 mb-2">Alamat Supplier</label>
                            <!--end::Label-->
                            <!--begin::Input-->
                            <input type="text" id="alamat" name="alamat" wire:model="alamat"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Alamat Supplier" />
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
                            <input type="text" id="telp" name="telp" wire:model="telp" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Telp"/>
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
                            <input type="text" wire:model="pic" name="pic"
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
                            <input type="text" wire:model="telp_pic" name="telp_pic"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Telp. Contact Person" />
                            <!--end::Input-->
                            @error('telp_pic')
                            <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <!--end::Input group-->
                        <div class="mb-4">
                            <label for="email" class="required fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" id="email" wire:model="email" class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Email">
                            @error('email')
                            <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <!--end::Scroll-->
                    <!--begin::Actions-->
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal" aria-label="Close"
                            wire:loading.attr="disabled">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-suppliers-modal-action="submit">
                                <span class="indicator-label" wire:loading.remove>{{ $edit_mode === true ? 'Update' : 'Submit' }}</span>
                                <span class="indicator-progress" wire:loading wire:target="submit">
                                    Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                    </div>
                    <!--end::Actions-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('success', function () {
            $('#kt_modal_master_supplier').modal('hide');

            // Reset the form using JavaScript (replace with your actual form ID)
            const form = document.getElementById('kt_modal_master_supplier_form');
            if (form) {
                form.reset();
            }
        });
        
        Livewire.on('validation-errors', event => {
            console.error('Validation Errors:', event.detail.errors);
        });

        // New event listener for validation errors
        // Livewire.on('validation-error', function (errors) {
        //     console.error('Validation Errors:', event.detail.errors);
        //     console.log('Validation Errors log:', event.detail.errors);
            
        //     // console.table('Validation Errors:', errors); // Log the entire errors object to the console

        //     // alert(errors[0]); 
        //     // console.log('ini errornya' + errors[0]);
        //     for (const field in errors) {
        //         console.error(`Error for ${field}:`, errors[field]);

        //         // if (field === 'kode_supplier') {
        //         //     console.error(`Error for ${field}:`, errors[field]);
        //         // }

        //         // const inputElement = document.querySelector(`input[wire:model="${field}"]`);
        //         // if (inputElement) {
        //         //     inputElement.classList.add('is-invalid'); // Add a class to style the input
        //         // }

        //         // console.log(errors[field][0]);

        //         // // Display error message next to the field
        //         // const errorSpan = document.createElement('span');
        //         // errorSpan.textContent = errors[field][0];
        //         // errorSpan.classList.add('error', 'text-danger');
        //         // inputElement.parentNode.insertBefore(errorSpan, inputElement.nextSibling);
        //     }
        // });

        Livewire.on('error', function (message) {
            // Handle error gracefully, display an error message to the user
            // alert(message); // Or use a better way to display errors
        });

        // $('#kt_modal_master_supplier').on('show.bs.modal', function () {
        //     Livewire.dispatch('resetForm');
        // });
    });

    $('#kt_modal_master_supplier').on('hidden.bs.modal', function () {
        Livewire.dispatch('new_supplier');
        // console.log('new_supplier');
    });
</script>
@endpush