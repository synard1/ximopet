    <div id="pemakaianStokFormCard" class="app-content  flex-column-fluid " tabindex="-1" style="display: none;" wire:ignore.self>
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container  container-fluid ">
            <!--begin::Layout-->
            <div class="d-flex flex-column flex-lg-row">
                <!--begin::Content-->
                {{-- <div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10" style="display: none;" id="formDiv"> --}}
                <div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10" id="formDiv">
                    <!--begin::Card-->
                    <div class="card" id="formDiva">
                        <!--begin::Card body-->
                        <div class="card-body p-12">
                            <!--begin::Form-->
                            <form action="" id="kt_pembelian_stok_form">
                                <!--begin::Wrapper-->
                                <div class="d-flex flex-column align-items-start flex-xxl-row">
                                    <!--begin::Input group-->
                                    <div class="d-flex align-items-center flex-equal fw-row me-4 order-2"
                                        data-bs-toggle="tooltip" data-bs-trigger="hover"
                                        data-bs-original-title="Specify invoice date" data-kt-initialized="1">
                                        <!--begin::Date-->
                                        <div class="fs-6 fw-bold text-gray-700 text-nowrap">Tanggal:</div>
                                        <!--end::Date-->
    
                                        <!--begin::Input-->
                                        <div class="position-relative d-flex align-items-center w-150px">
                                            <!--begin::Datepicker-->
                                            <input class="form-control form-control-transparent fw-bold pe-5 flatpickr-input" placeholder="Pilih Tanggal" wire:model="tanggal" name="tanggal" id="tanggal" type="text" readonly="readonly"
                                            x-data 
                                            x-init="flatpickr($el, {
                                                enableTime: false,
                                                dateFormat: 'Y-m-d H:i',
                                            })">
                                            <!--end::Datepicker-->
                                            
                                            <!--begin::Icon-->
                                            <i class="ki-outline ki-down fs-4 position-absolute ms-4 end-0"></i>                        
                                            <!--end::Icon-->
                                        </div>
                                        <!--end::Input-->  
                                        @error('tanggal')
                                        <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('tanggal')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror 
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
                                                @foreach($items as $index => $item)
                                                    <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                                        <td>
                                                            <select wire:model="items.{{ $index }}.name" class="form-select select2 item-select" id="itemsSelect">
                                                                <option value="">Select Item</option>
                                                                @foreach($allItems as $availableItem)
                                                                    <option value="{{ $availableItem->id }}">{{ $availableItem->nama }}</option>
                                                                @endforeach
                                                            </select>
                                                            <input type="hidden" wire:model="items.{{ $index }}.item_id">
                                                        </td>
                                                        <td class="ps-0">
                                                            <input type="number" class="form-control form-control-solid" min="1" wire:model="items.{{ $index }}.qty" placeholder="1" data-kt-element="quantity">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-solid text-end" wire:model="items.{{ $index }}.harga" placeholder="0.00" data-kt-element="harga" >
                                                        </td>
                                                        <td class="pt-8 text-end text-nowrap">
                                                            Rp<span data-kt-element="total"></span>
                                                        </td>
                                                        <td class="pt-5 text-end">
                                                            <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" wire:click="removeItem({{ $index }})">
                                                                <i class="ki-outline ki-trash fs-3"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <!--end::Table body-->
    
                                            <!--begin::Table foot-->
                                            <tfoot>
                                                <tr
                                                    class="border-top border-top-dashed align-top fs-6 fw-bold text-gray-700">
                                                    <th class="text-primary">
                                                        {{-- <button class="btn btn-link py-1" data-kt-element="add-item">Add item</button> --}}
                                                        <button class="btn btn-link py-1" data-kt-element="add-item" wire:click="addItem" id="addItem">Add item</button>
                                                    </th>
    
                                                    <th colspan="2" class="border-bottom border-bottom-dashed ps-0">
                                                        <div class="d-flex flex-column align-items-start">
                                                            <div class="fs-5">Subtotal</div>
                                                        </div>
                                                    </th>
    
                                                    <th colspan="2" class="border-bottom border-bottom-dashed text-end">
                                                        Rp<span data-kt-element="sub-total">0.00</span>
                                                    </th>
                                                </tr>
    
                                                <tr class="align-top fw-bold text-gray-700">
                                                    <th></th>
    
                                                    <th colspan="2" class="fs-4 ps-0">Total</th>
    
                                                    <th colspan="2" class="text-end fs-4 text-nowrap">
                                                        Rp<span data-kt-element="grand-total">0.00</span>
                                                    </th>
                                                </tr>
                                            </tfoot>
                                            <!--end::Table foot-->
                                        </table>
                                    </div>
                                    <!--end::Table-->
    
                                    <!--begin::Item template-->
                                    <table class="table d-none" data-kt-element="item-template">
                                        <tbody>
                                            <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                                <td class="pe-7">
                                                    <input type="text" class="form-control form-control-solid mb-2"
                                                        name="name[]" placeholder="Item name">
                                                </td>
    
                                                <td class="ps-0">
                                                    <input type="text" class="form-control form-control-solid" min="1"
                                                        name="quantity[]" placeholder="1" data-kt-element="quantity">
                                                </td>
    
                                                <td>
                                                    <input type="text" class="form-control form-control-solid text-end"
                                                        name="harga[]" placeholder="0.00" data-kt-element="harga">
                                                </td>
    
                                                <td class="pt-8 text-end">
                                                    Rp<span data-kt-element="total">0.00</span>
                                                </td>
    
                                                <td class="pt-5 text-end">
                                                    <button type="button"
                                                        class="btn btn-sm btn-icon btn-active-color-primary"
                                                        data-kt-element="remove-item">
                                                        <i class="ki-outline ki-trash fs-3"></i> </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
    
                                    <table class="table d-none" data-kt-element="empty-template">
                                        <tbody>
                                            <tr data-kt-element="empty">
                                                <th colspan="5" class="text-muted text-center py-10">
                                                    No items
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!--end::Item template-->
    
                                    {{-- <!--begin::Notes-->
                                    <div class="mb-0">
                                        <label class="form-label fs-6 fw-bold text-gray-700">Notes</label>
    
                                        <textarea name="notes" class="form-control form-control-solid" rows="3"
                                            placeholder="Thanks for your business"></textarea>
                                    </div>
                                    <!--end::Notes--> --}}
                                </div>
                                <!--end::Wrapper-->
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Content-->
    
                <!--begin::Sidebar-->
                <div class="flex-lg-auto min-w-lg-300px">
                    <!--begin::Card-->
                    <div class="card" data-kt-sticky="true" data-kt-sticky-name="invoice"
                        data-kt-sticky-offset="{default: false, lg: '200px'}"
                        data-kt-sticky-width="{lg: '250px', lg: '300px'}" data-kt-sticky-left="auto"
                        data-kt-sticky-top="150px" data-kt-sticky-animation="false" data-kt-sticky-zindex="95" style="">
    
                        <!--begin::Card body-->
                        <div class="card-body p-10">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-bold fs-6 text-gray-700">Farm</label>
                                <!--end::Label-->
    
                                <select id="selectedFarm" name="selectedFarm" class="form-control" wire:model="selectedFarm">
                                    <option value="">=== Pilih Farm ===</option>
                                    @foreach($farms as $farm)
                                        <option value="{{ $farm->farm_id }}">{{ $farm->nama_farm }}</option>
    
                                    @endforeach
                                </select>
                                @error('selectedFarm')
                                <span class="text-danger">{{ $message }}</span> @enderror
                                @error('selectedFarm')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror 
                            </div>
                            <!--end::Input group-->
    
                            <!--begin::Separator-->
                            <div class="separator separator-dashed mb-8"></div>
                            <!--end::Separator-->
    
                            <!--begin::Actions-->
                            <div class="mb-0">
                                <button type="button" class="btn btn-secondary" wire:click="close()">Close</button>
                                <button type="submit" href="#" class="btn btn-primary" wire:click="store()" id="saveChangesButton"><i class="ki-outline ki-triangle fs-3"></i> Save Changes
                                </button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Sidebar-->
            </div>
            <!--end::Layout-->
        </div>
        <!--end::Content container-->
    </div>
    @push('styles')
    <style>
        .grey-block {
            background-color: lightgrey; 
            pointer-events: none; 
        }
    
        .warning {
            color: red;
            font-weight: bold;
            padding: 10px;
        }
    </style>
    @endpush
    @push('scripts')
    <script>
        $(document).ready(function() {
            var updateArea = $('#formDiva'); 
            updateArea.addClass('grey-block'); 

            // Get the button element
            const saveChangesButton = document.getElementById('saveChangesButton');

            // Disable the button
            saveChangesButton.disabled = true;

            $('#selectedFarm').change(function() {
                var farmId = $(this).val();
                const updateArea = $('#formDiva'); 
                // const div = document.getElementById('formDiv');

                Swal.fire({
                html: `Memuat Data`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000
                }).then(function () {
                    $.ajax({
                            url: '/api/v1/get-farm-stocks/' + farmId, 
                            type: 'GET',
                            success: function(data) {
                                // div.style.display = 'block'
                                updateArea.removeClass('grey-block'); 

                                let minDat = data.parameter.oldestDate;

                                flatpickr("#tanggal", {
                                    minDate: minDat, // Assuming $minDate is available in your Blade view
                                    // Other Flatpickr options...
                                });

                                // Get the Select2 instance
                                const itemSelect = $('#items');
    
                                // Clear existing options
                                itemSelect.empty();

                                // Add new options based on the fetched data
                                data.stock.forEach(item => {
                                    const option = new Option(item.nama, item.item_id);
                                    itemSelect.append(option);
                                });

                                // Disable the button
                                saveChangesButton.disabled = false;
                            },
                            error: function(xhr) { 
                                Swal.fire({
                                    html: `Error: <b>`+ xhr.responseJSON.error +`</b>`,
                                    icon: "error",
                                    buttonsStyling: true,
                                    showConfirmButton: true,
                                })
                                // div.style.display = 'none';
                                updateArea.addClass('grey-block'); 

                                // Disable the button
                                saveChangesButton.disabled = true;
                            }
                        });

                });
            });
        });

        $(document).ready(function() {





// // When adding a new item dynamically
// $('#add-item-btn').on('click', function() {
//     // Your logic to add a new row with a dropdown
//     // After adding, ensure the new dropdown has the .item-select class
//     updateDropdowns();
// });

// // When adding a new item dynamically
// $('#addItem').on('click', function() {
//     console.log('button click');
//     // Your logic to add a new row with a dropdown
//     // After adding, ensure the new dropdown has the .item-select class
//     updateDropdowns();
// });
});

document.addEventListener('livewire:init', function () {
    function updateDropdowns() {
    var selectedItems = [];

    // Gather all selected items
    $('select.item-select').each(function() {
        var selectedValue = $(this).val();
        if (selectedValue !== "") {
            selectedItems.push(selectedValue);
        }
    });

    // Update options in all dropdowns
    $('select.item-select').each(function() {
        var currentSelect = $(this);

        currentSelect.find('option').each(function() {
            var optionValue = $(this).val();

            // Disable option if it's selected in another dropdown
            if (optionValue !== "" && selectedItems.includes(optionValue) && optionValue !== currentSelect.val()) {
                $(this).attr('disabled', true);
            } else {
                $(this).attr('disabled', false);
            }
        });
    });
}

// Event listener for change on the dropdowns
// $(document).on('change', 'select.item-select', function() {
//     updateDropdowns();
// });

// Initial call to disable already selected options
// updateDropdowns();
                // Livewire.on('closeForm', function () {
                //     showLoadingSpinner();
                //     const cardList = document.getElementById(`stokTableCard`);
                //     cardList.style.display = 'block';

                //     const cardForm = document.getElementById(`stokFormCard`);
                //     cardForm.style.display = 'none';

                //     // Reload DataTables
                //     $('.table').each(function() {
                //         if ($.fn.DataTable.isDataTable(this)) {
                //             $(this).DataTable().ajax.reload();
                //         }
                //     });

                    
                // });
                Livewire.on('reinitialize-select2-pemakaianStok', function () {


        // Update the dropdowns to reflect the current selections
        updateDropdowns();
                        console.log('test update dropdown');
                        // $('#itemsSelect').select2();
                        
                    });
            });



    </script>
    @endpush