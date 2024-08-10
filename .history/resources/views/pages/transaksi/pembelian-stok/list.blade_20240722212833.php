<x-default-layout>

    @section('title')
        Data Pembelian Stok
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="stokTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Data DOC" id="mySearchInput"/>
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" data-kt-button="create_new">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pembelian
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->

                <!--begin::Modal-->
                <livewire:master-data.kandang-list />
                <!--end::Modal-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    <div class="card" id="stokFormCard" style="display: none;">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
            </div>
            <!--begin::Card title-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <form action="" id="kt_pembelian_stok_form">
                <!--begin::Wrapper-->
                <div class="d-flex flex-column align-items-start flex-xxl-row">
                    <!--begin::Input group-->
                    <div class="d-flex align-items-center flex-equal fw-row me-4 order-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Specify invoice date" data-kt-initialized="1">
                        <!--begin::Date-->
                        <div class="fs-6 fw-bold text-gray-700 text-nowrap">Tanggal Pembelian:</div>  
                        <!--end::Date-->                    
    
                        <!--begin::Input-->
                        <div class="position-relative d-flex align-items-center w-150px">
                            <!--begin::Datepicker-->
                            <input class="form-control form-control-transparent fw-bold pe-5 flatpickr-input" placeholder="Select date" name="invoice_date" type="text" readonly="readonly">
                            <!--end::Datepicker-->
                            
                            <!--begin::Icon-->
                            <i class="ki-outline ki-down fs-4 position-absolute ms-4 end-0"></i>                        <!--end::Icon-->
                        </div>
                        <!--end::Input-->                
                    </div>                
                    <!--end::Input group--> 
    
                    <!--begin::Input group-->
                    <div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Enter invoice number" data-kt-initialized="1">
                        <span class="fs-2x fw-bold text-gray-800">Invoice #</span> 
                        <input type="text" class="form-control form-control-flush fw-bold text-muted fs-3 w-125px" value="2021001" placehoder="...">
                    </div>                
                    <!--end::Input group--> 
    
                    <!--begin::Input group-->
                    <div class="d-flex align-items-center justify-content-end flex-equal order-3 fw-row" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Specify invoice due date" data-kt-initialized="1">
                        <!--begin::Date-->
                        <div class="fs-6 fw-bold text-gray-700 text-nowrap">Supplier:</div>  
                        <!--end::Date-->                    
    
                        <!--begin::Input-->
                        <div class="position-relative d-flex align-items-center w-150px">
                            <!--begin::Datepicker-->
                            <input class="form-control form-control-transparent fw-bold pe-5 flatpickr-input" placeholder="Select date" name="invoice_due_date" type="text" readonly="readonly">
                            <!--end::Datepicker-->
                            
                            <!--begin::Icon-->
                            <i class="ki-outline ki-down fs-4 position-absolute end-0 ms-4"></i>                        <!--end::Icon-->
                        </div>
                        <!--end::Input-->                
                    </div>                
                    <!--end::Input group--> 
                </div>                
                <!--end::Top-->       
    
                <!--begin::Separator-->
                <div class="separator separator-dashed my-10"></div>
                <!--end::Separator-->         
    
                <!--begin::Wrapper-->
                <div class="mb-0">
                    <!--begin::Table wrapper-->
                    <div class="table-responsive mb-10">
                         <!--begin::Table-->
                        <table class="table g-5 gs-0 mb-0 fw-bold text-gray-700" data-kt-element="items">
                            <!--begin::Table head-->
                            <thead>
                                <tr class="border-bottom fs-7 fw-bold text-gray-700 text-uppercase">
                                    <th class="min-w-300px w-475px">Item</th>
                                    <th class="min-w-100px w-100px">QTY</th>
                                    <th class="min-w-150px w-150px">Price</th>
                                    <th class="min-w-100px w-150px text-end">Total</th>
                                    <th class="min-w-75px w-75px text-end">Action</th>
                                </tr>
                            </thead>
                            <!--end::Table head-->
    
                            <!--begin::Table body-->
                            <tbody>
                                <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                    <td class="pe-7">                                            
                                        <input type="text" class="form-control form-control-solid mb-2" name="name[]" placeholder="Item name">
                                    </td>
    
                                    <td class="ps-0">                                            
                                        <input type="text" class="form-control form-control-solid" min="1" name="quantity[]" placeholder="1" value="1" data-kt-element="quantity">
                                    </td>
    
                                    <td>   
                                        <input type="text" class="form-control form-control-solid text-end" name="price[]" placeholder="0.00" value="0.00" data-kt-element="price">
                                    </td>
                                    
                                    <td class="pt-8 text-end text-nowrap">
                                        $<span data-kt-element="total">0.00</span>
                                    </td>
    
                                    <td class="pt-5 text-end">
                                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-kt-element="remove-item">
                                            <i class="ki-outline ki-trash fs-3"></i>                                    </button>                             
                                    </td>
                                </tr>          
                            </tbody>
                            <!--end::Table body-->
    
                            <!--begin::Table foot-->
                            <tfoot>
                                <tr class="border-top border-top-dashed align-top fs-6 fw-bold text-gray-700">
                                    <th class="text-primary">
                                        <button class="btn btn-link py-1" data-kt-element="add-item">Add item</button>
                                    </th>  
    
                                    <th colspan="2" class="border-bottom border-bottom-dashed ps-0">
                                        <div class="d-flex flex-column align-items-start">
                                            <div class="fs-5">Subtotal</div> 
    
                                            <button class="btn btn-link py-1" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Coming soon" data-kt-initialized="1">Add tax</button>
    
                                            <button class="btn btn-link py-1" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Coming soon" data-kt-initialized="1">Add discount</button>
                                        </div>
                                    </th> 
    
                                    <th colspan="2" class="border-bottom border-bottom-dashed text-end">
                                        $<span data-kt-element="sub-total">0.00</span>
                                    </th> 
                                </tr>    
                                
                                <tr class="align-top fw-bold text-gray-700">
                                    <th></th>
                                    
                                    <th colspan="2" class="fs-4 ps-0">Total</th> 
    
                                    <th colspan="2" class="text-end fs-4 text-nowrap">
                                        $<span data-kt-element="grand-total">0.00</span>
                                    </th> 
                                </tr>          
                            </tfoot>
                            <!--end::Table foot-->
                        </table>
                    </div>  
                    <!--end::Table-->
    
                    <!--begin::Item template-->
                    <table class="table d-none" data-kt-element="item-template">
                        <tbody><tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                            <td class="pe-7">                                            
                                <input type="text" class="form-control form-control-solid mb-2" name="name[]" placeholder="Item name">
    
                                <input type="text" class="form-control form-control-solid" name="description[]" placeholder="Description">     
                            </td>
    
                            <td class="ps-0">                                            
                                <input type="text" class="form-control form-control-solid" min="1" name="quantity[]" placeholder="1" data-kt-element="quantity">
                            </td>
    
                            <td>   
                                <input type="text" class="form-control form-control-solid text-end" name="price[]" placeholder="0.00" data-kt-element="price">
                            </td>
                            
                            <td class="pt-8 text-end">
                                $<span data-kt-element="total">0.00</span>
                            </td>
    
                            <td class="pt-5 text-end">
                                <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-kt-element="remove-item">
                                    <i class="ki-outline ki-trash fs-3"></i>                            </button>                             
                            </td>
                        </tr>
                    </tbody></table>
    
                    <table class="table d-none" data-kt-element="empty-template">
                        <tbody><tr data-kt-element="empty">
                            <th colspan="5" class="text-muted text-center py-10">
                                No items
                            </th>
                        </tr>
                    </tbody></table>
                    <!--end::Item template-->
                </div>   
                <!--end::Wrapper-->          
            </form>
            
        </div>
        <!--end::Card body-->
        <div class="d-flex justify-content-end">
            <!--begin::Button-->
            <a href="/metronic8/demo60/apps/ecommerce/catalog/products.html" id="kt_ecommerce_add_product_cancel" class="btn btn-light me-5">
                Cancel
            </a>
            <!--end::Button-->

            <!--begin::Button-->
            <button type="submit" id="kt_ecommerce_add_product_submit" class="btn btn-primary">
                <span class="indicator-label">
                    Save Changes
                </span>
                <span class="indicator-progress">
                    Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
            <!--end::Button-->
        </div>
    </div>

    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            document.querySelectorAll('[data-kt-button="create_new"]').forEach(function (element) {
			element.addEventListener('click', function () {
				// Simulate delete request -- for demo purpose only
				Swal.fire({
					html: `Preparing Form`,
					icon: "info",
					buttonsStyling: false,
					showConfirmButton: false,
					timer: 2000
				}).then(function () {
                    console.log('form loaded');

                    const cardList = document.getElementById(`stokTableCard`);
                    cardList.style.display = 'none';
                    // cardList.classList.toggle('d-none');

                    const cardForm = document.getElementById(`stokFormCard`);
                    cardForm.style.display = 'block';
                    // cardList.classList.toggle('d-none');
					// fetchFarm();
				});
				
			});

		});
            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['kandangs-table'].search(this.value).draw();
            });
            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    $('#kt_modal_add_user').modal('hide');
                    window.LaravelDataTables['kandangs-table'].ajax.reload();
                });
            });
            $('#kt_modal_add_user').on('hidden.bs.modal', function () {
                Livewire.dispatch('new_user');
            });
        </script>
    @endpush
</x-default-layout>

