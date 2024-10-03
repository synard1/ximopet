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
                            <form action="" id="kt_pemakaian_stok_form">
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
                                        {{-- <table class="table g-5 gs-0 mb-0 fw-bold text-gray-700" data-kt-element="items">
                                            <!--begin::Table head-->
                                            <thead>
                                                <tr class="border-bottom fs-7 fw-bold text-gray-700 text-uppercase">
                                                    <th class="min-w-300px w-475px">Item</th>
                                                    <th class="min-w-100px w-100px">QTY</th>
                                                    <th class="min-w-150px w-150px">Stock</th>
                                                    <th class="min-w-100px w-150px text-end">Sub Total</th>
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
                                                            <input type="text" class="form-control form-control-solid text-end" wire:model="items.{{ $index }}.total"  data-kt-element="harga" >
                                                        </td>
                                                        <td class="pt-8 text-end text-nowrap">
                                                            <span data-kt-element="total"></span>
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
                                        </table> --}}
                                        <div id="formDiva"> 
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Nama Barang</th>
                                                        <th>Stok Tersedia</th>
                                                        <th>Digunakan</th>
                                                        <th>Sisa Stok</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="stockTableBody">
                                                    {{-- Stock data will be populated here dynamically --}} 
                                                </tbody>
                                            </table>
                                        </div>
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
    
                                <select id="selectedFarm" name="selectedFarm" class="form-control" wire:model="selectedFarm" data-control="select2">
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

                            <!--begin::Input group-->
                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-bold fs-6 text-gray-700">Kandang</label>
                                <!--end::Label-->
    
                                <select wire:model="selectedKandang" class="js-select2 form-control" id="kandangs">
                                    <option value="">=== Pilih Kandang ===</option>
                                    @foreach ($kandangs as $kandang)
                                    <option value="{{ $kandang->id }}">{{ $kandang->nama }}</option>
                                    @endforeach
                                </select>
                                @error('selectedKandang') <span class="text-danger error">{{ $message }}</span>@enderror
                            </div>
                            <!--end::Input group-->
    
                            <!--begin::Separator-->
                            <div class="separator separator-dashed mb-8"></div>
                            <!--end::Separator-->
    
                            <!--begin::Actions-->
                            <div class="mb-0">
                                <button type="button" class="btn btn-secondary" wire:click="close()">Close</button>
                                <button type="submit" href="#" class="btn btn-primary" id="saveChangesButton"><i class="ki-outline ki-triangle fs-3"></i> Save Changes
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
        function getDetailPemakaian(param) {
            console.log(param);
            new DataTable('#itemsTable', {
                ajax: `/api/v1/transaksi/details/${param}`,
                columns: [
                    { data: '#',
                        render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                        } 
                    },
                    { data: 'jenis_barang' },
                    { data: 'nama' },
                    { data: 'qty', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'terpakai', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'sisa', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'harga', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) },
                    { data: 'sub_total', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) }
                ]
            });
        }

        function closeDetailsPurchasing() {
            var table = new DataTable('#itemsTable');
            table.destroy();
        }
        
    </script>
    <script>
        let farmId, kandangId = '';
        $(document).ready(function() {
            var updateArea = $('#formDiva'); 
            updateArea.addClass('grey-block'); 

            // Get the button element
            const saveChangesButton = document.getElementById('saveChangesButton');
            let jsonData = '';
            const farmSelect = document.getElementById('farms');


            // let farmId = '';

            // Disable the button
            saveChangesButton.disabled = true;

            $('#selectedFarm').change(function() {

                farmId = $(this).val();
                if(farmId == ''){
                    // break;
                }
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
                                jsonData = data;



                                // div.style.display = 'block'
                                updateArea.removeClass('grey-block'); 

                                let minDat = data.parameter.oldestDate;

                                flatpickr("#tanggal", {
                                    minDate: minDat, // Assuming $minDate is available in your Blade view
                                    // Other Flatpickr options...
                                });

                                // Get the Select2 instance
                                const itemSelect = $('#itemsSelect');
    
                                // Clear existing options
                                itemSelect.empty();

                                // Add new options based on the fetched data
                                data.stock.forEach(item => {
                                    const option = new Option(item.item_nama, item.item_id);
                                    itemSelect.append(option);
                                });

                                // Disable the button
                                saveChangesButton.disabled = false;

                                // Parse the JSON string into a JavaScript object
                                // const tempData = JSON.parse(jsonData);

                                // Now you can access and work with the data
                                // console.log(jsonData.stock[0].nama); // Output: Nama StokObat

                                updateTableBody(data); 
                                updateKandang(farmId);

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

    // Function to update the table body with stock data
    function updateKandang(farmId) {
        console.log('cek data kandang');
        const kandangSelect = document.getElementById('kandangs');

        // Clear existing options in the farmSelect dropdown
        while (kandangSelect.options.length > 0) {
                kandangSelect.remove(0);
        }

        kandangSelect.innerHTML = '<option value="">=== Pilih Kanndang ===</option>';


        // Fetch operators for the selected farm via AJAX
        fetch(`/api/v1/get-kandangs/${farmId}/used`)
                .then(response => response.json())
                .then(data => {
                    if (data.kandangs && data.kandangs.length > 0) {
                        // console.log(data.kandangs);

                        data.kandangs.forEach(kandang => {
                            const option = document.createElement('option');
                            option.value = kandang.id;
                            option.text = kandang.nama;
                            kandangSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching kandangs:', error));
    }

    // Function to update the table body with stock data
    function updateTableBody(jsonData) {
        const tableBody = $('#stockTableBody');
        tableBody.empty(); // Clear existing rows

        jsonData.stock.forEach(item => {
            const newRow = `
                <tr>
                    <td>${item.item_nama}</td>
                    <td id="availableStock_${item.item_id}">${item.total}</td>
                    <td>
                        <input type="number" class="form-control qty-input" data-item-id="${item.item_id}" min="0" max="${item.total}" placeholder="0" ${item.total === 0 ? 'disabled' : ''}>
                    </td>
                    <td id="remainingStock_${item.item_id}">${item.total}</td>
                </tr>
            `;
            tableBody.append(newRow);
        });

        // Attach event listeners to qty inputs for dynamic calculation and validation
        $('.qty-input').on('input', function() {
            const itemId = $(this).data('item-id');
            const availableStock = parseInt($(`#availableStock_${itemId}`).text());
            let qtyUsed = parseInt($(this).val()) || 0;

            // Ensure qtyUsed doesn't exceed availableStock
            if (qtyUsed > availableStock) {
                qtyUsed = availableStock;
                $(this).val(qtyUsed); // Update the input field to reflect the corrected value
            }

            const remainingStock = availableStock - qtyUsed;
            $(`#remainingStock_${itemId}`).text(remainingStock);
        });
    }

    // Submit function to collect data and potentially send it via AJAX
    function submitStockData() {
        const updatedStockData = [];
        // Get the select element
        const kandangSelect = document.getElementById('kandangs');
        const tanggal = document.getElementById('tanggal');

        // Iterate through each row in the table body
        $('#stockTableBody tr').each(function() {
            const itemId = $(this).find('.qty-input').data('item-id');
            const qtyUsed = parseInt($(this).find('.qty-input').val()) || 0;
            const isDisabled = $(this).find('.qty-input').is(':disabled');

            // Skip if qtyUsed is 0 or input is disabled
            if (qtyUsed === 0 || isDisabled) {
                return; // Continue to the next iteration (skip this item)
            }

            updatedStockData.push({
                item_id: itemId,
                qty_used: qtyUsed 
            });
        });
        // $('#stockTableBody tr').each(function() {
        //     const itemId = $(this).find('.qty-input').data('item-id');
        //     const qtyUsed = parseInt($(this).find('.qty-input').val()) || 0;

        //     // Create an object for each item with updated qtyUsed
        //     updatedStockData.push({
        //         item_id: itemId,
        //         qty_used: qtyUsed 
        //     });
        // });

        // Prepare the final JSON data to be sent
        const finalData = {
            farm_id: farmId,
            kandang_id: kandangSelect.value,
            tanggal: tanggal.value,
            stock: updatedStockData,
            // ... other data you might need to include (e.g., 'parameter', 'tanggal')
        };

        // You can now send this 'finalData' to your server using AJAX or other methods
        console.log(JSON.stringify(finalData)); // Example: Log the JSON data to the console

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Example AJAX call (you'll need to adjust the URL and other settings)
        $.ajax({
            url: '/reduce-stock', // Replace with your actual endpoint
            type: 'POST',
            data: JSON.stringify(finalData),
            contentType: 'application/json', 
            success: function(response) {
                // Handle the successful response from the server
                console.log('Stock data updated successfully!', response);
                toastr.success(response.message); 
                // table.ajax.reload();
                Livewire.dispatch('closeFormPemakaian');
            },
            error: function(xhr) {
                // Handle errors during the AJAX call
                console.error('Error updating stock data:', xhr.responseText);
                // console.log(xhr.responseJSON.message);
                // console.log(xhr.errors);
                // console.log(xhr.message);
                toastr.error(xhr.responseJSON.message); 
            }
        });
    }

    // Attach the submit function to your button click
    $('#saveChangesButton').on('click', submitStockData);

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
    <script>
        const qtyInputs = document.querySelectorAll('.qty-input');
        qtyInputs.forEach(input => {
            input.addEventListener('input', function() {
                const itemId = this.dataset.itemId;
                const initialStock = parseInt(document.getElementById(`initial_${itemId}`).textContent);
                const usedQty = parseInt(this.value) || 0; 
                const totalUsed = document.getElementById(`used_${itemId}`);
                const remainingStock = document.getElementById(`remaining_${itemId}`);

                totalUsed.textContent = usedQty;
                remainingStock.textContent = initialStock - usedQty; 
            });
        });
    </script>
    @endpush