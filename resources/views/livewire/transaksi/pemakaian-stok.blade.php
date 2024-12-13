    <div id="pemakaianStokFormCard" class="app-content  flex-column-fluid " tabindex="-1" style="display: none;" wire:ignore.self>
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container  container-fluid ">
            <!--begin::Layout-->
            <div class="d-flex flex-column flex-lg-row">
                <!--begin::Content-->
                {{-- <div class="d-flex flex-column gap-7 gap-lg-10" style="display: none;" id="formDiv"> --}}
                <div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10 gap-7 gap-lg-10" id="formDiv">
                    <!--begin::Card-->
                    <div class="card card-flush py-4 mb-7" id="formDiva">
                        <!--begin::Card header-->
                        <div class="card-header">
                            <h2 class="card-title">Transaksi Stok</h2>
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-0">
                            <!--begin::Form-->
                            <form action="" id="kt_pemakaian_stok_form">


                                <!--begin::Table wrapper-->
                                <div class="table-responsive mb-10">
                                    <!--begin::Table-->
                                    <table class="table table-rounded table-striped border gy-7 gs-7">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200">
                                                <th>Nama Barang</th>
                                                <th>Stok Tersedia</th>
                                                <th>Digunakan</th>
                                                <th>Sisa Stok</th>
                                            </tr>
                                        </thead>
                                        <tbody id="stockTableBody">
                                            <!-- Stock data will be populated here dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                                <!--end::Table-->

                                <!--begin::Wrapper-->
                                <div class="d-flex flex-column align-items-start flex-xxl-row mb-5">
                                    <div class="form-group w-100 mb-5">
                                        <label for="keterangan" class="form-label fs-6 fw-bold text-gray-700">Keterangan</label>
                                        <textarea id="keterangan" class="form-control form-control-solid" name="keterangan" rows="3" placeholder="Masukkan keterangan transaksi"></textarea>
                                    </div>
                                </div>
                                <!--end::Wrapper-->

                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                    
                    <!--begin::Card-->
                    <div class="card card-flush py-4" id="cardTransaksi">
                        <!--begin::Card header-->
                        <div class="card-header">
                            <h2 class="card-title">Transaksi Ternak</h2>
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-0">
                            <!--begin::Form-->
                            <form id="ternakForm">
                                <div class="mb-10">
                                    <label class="form-label fw-bold fs-6 text-gray-700">Ternak Mati</label>
                                    <input type="number" wire:model="ternak_mati"  class="form-control form-control-solid" name="ternak_mati" id="ternak_mati" placeholder="Jumlah Ekor" value="0">
                                </div>
                                
                                <div class="mb-10">
                                    <label class="form-label fw-bold fs-6 text-gray-700">Ternak Afkir</label>
                                    <input type="number" class="form-control form-control-solid" name="ternak_afkir" id="ternak_afkir" placeholder="Jumlah Ekor" value="0">
                                </div>
                                
                                <div class="mb-10">
                                    <label class="form-label fw-bold fs-6 text-gray-700">Ternak Terjual</label>
                                    <input type="number" class="form-control form-control-solid" name="ternak_jual" id="ternak_jual" placeholder="Jumlah Ekor" value="0">
                                    {{-- <div class="row">
                                        <div class="col-md-6 mb-5">
                                            <input type="number" class="form-control form-control-solid" name="ternak_jual" id="ternak_jual" placeholder="Jumlah Ekor">
                                        </div>
                                        <div class="col-md-6 mb-5">
                                            <input type="number" class="form-control form-control-solid" name="ternak_terjual_kg" placeholder="Jumlah Kg">
                                        </div>
                                    </div> --}}
                                </div>
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
                        data-kt-sticky-top="150px" data-kt-sticky-animation="false" data-kt-sticky-zindex="95" data-kt-sticky-resize="false" style="">
    
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
                                        <option value="{{ $farm->id }}">{{ $farm->nama }}</option>
    
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
    
                                <select wire:model="selectedKandang" class="js-select2 form-control" id="kandangs" disabled>
                                    <option value="">=== Pilih Kandang ===</option>
                                    @foreach ($kandangs as $kandang)
                                    <option value="{{ $kandang->id }}">{{ $kandang->nama }}</option>
                                    @endforeach
                                </select>
                                @error('selectedKandang') <span class="text-danger error">{{ $message }}</span>@enderror
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-bold fs-6 text-gray-700">Tanggal</label>
                                <!--end::Label-->

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-calendar fs-2"></i>
                                    </span>
                                    <input type="date" wire:model="tanggal" class="form-control" id="tanggal" name="tanggal" x-data 
                                    x-init="flatpickr($el, {
                                        enableTime: false,
                                    })" disabled>
                                </div>
                                @error('tanggal') <span class="text-danger error">{{ $message }}</span>@enderror
                            </div>
                            <!--end::Input group-->
    
                            <!--begin::Separator-->
                            <div class="separator separator-dashed mb-8"></div>
                            <!--end::Separator-->
    
                            <!--begin::Actions-->
                            <div class="mb-0">
                                <button type="button" class="btn btn-secondary" wire:click="close()">Close</button>
                                <button type="submit" href="#" class="btn btn-primary" id="saveChangesButton">Save
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
            // console.log(param);
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
        let kandangsData=[];
        let stocksData=[];
        $(document).ready(function() {
            var updateArea = $('#formDiva'); 
            var cardTransaksi = $('#cardTransaksi'); 
            updateArea.addClass('grey-block'); 
            cardTransaksi.addClass('grey-block'); 

            // Get the button element
            const saveChangesButton = document.getElementById('saveChangesButton');
            const kandangInput = document.getElementById('kandangs');
            const tanggalInput = document.getElementById('tanggal');
            let jsonData = '';
            const farmSelect = document.getElementById('farms');


            // let farmId = '';

            // Disable the button
            saveChangesButton.disabled = true;

            $('#selectedFarm').change(function() {

                farmId = $(this).val();
                if(farmId == ''){
                    return;
                }
                const updateArea = $('#formDiva'); 
                var cardTransaksi = $('#cardTransaksi'); 
                kandangsData.length = 0; // Clear the array before assigning new data
                stocksData.length = 0; // Clear the array before assigning new data

                // const div = document.getElementById('formDiv');

                Swal.fire({
                html: `Memuat Data`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000
                }).then(function () {

                    $.ajaxSetup({
                        headers: {
                            'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                    });

                    const finalData = {
                        farm_id: farmId,
                        task: 'GET',
                        mode: 'LIST',
                    };

                    $.ajax({
                        // url: `/api/v2/data/farms/stocks`,
                        url: `/api/v2/data/farms/details`,
                        type: 'POST',
                        data: JSON.stringify(finalData),
                        contentType: 'application/json', 
                        success: function(data) {
                            // console.log(result.data);
                            kandangsData.push(...data.kandangs);
                            stocksData.push(...data.stock);
                            // console.table(stocksData);
                            

                                const kandangSelect = document.getElementById('kandangs');

                                // Clear existing options
                                kandangSelect.innerHTML = '<option value="">=== Pilih Kandang ===</option>';

                                // Only populate options if kandangs data exists and has items
                                if (data.kandangs?.length > 0) {
                                    data.kandangs.forEach(kandang => {
                                        const option = document.createElement('option');
                                        option.value = kandang.id;
                                        option.text = kandang.nama;
                                        kandangSelect.appendChild(option);
                                    });
                                }

                                kandangInput.disabled = false;

                                // jsonData = data;



                                // // div.style.display = 'block'
                                // updateArea.removeClass('grey-block'); 
                                // cardTransaksi.removeClass('grey-block'); 
                                // kandangInput.disabled = false;
                                // // tanggalInput.disabled = false;

                                // // let minDat = data.parameter.oldestDate;

                                // // flatpickr("#tanggal", {
                                // //     minDate: minDat, // Assuming $minDate is available in your Blade view
                                // //     // Other Flatpickr options...
                                // // });

                                // // Get the Select2 instance
                                // const itemSelect = $('#itemsSelect');
    
                                // // Clear existing options
                                // itemSelect.empty();

                                // // Add new options based on the fetched data
                                // data.stock.forEach(item => {
                                //     const option = new Option(item.item_name, item.item_id);
                                //     itemSelect.append(option);
                                // });

                                // // Disable the button
                                // saveChangesButton.disabled = false;

                                // // Parse the JSON string into a JavaScript object
                                // // const tempData = JSON.parse(jsonData);

                                // // Now you can access and work with the data
                                // // console.log(jsonData.stock[0].nama); // Output: Nama StokObat

                                // updateTableBody(data); 
                                // // updateKandang(farmId);

                            },
                            error: function(xhr) {
                                let errorMessage = 'An error occurred';
                                let errorData = {
                                    stock: [],
                                    parameter: { oldestDate: null },
                                    kandangs: [],
                                    error: errorMessage
                                };

                                if (xhr.responseJSON && xhr.responseJSON.error) {
                                    errorData.error = xhr.responseJSON.error;
                                }

                                // If kandangs data is available in the error response, use it
                                if (xhr.responseJSON && xhr.responseJSON.kandangs) {
                                    errorData.kandangs = xhr.responseJSON.kandangs;
                                }

                                Swal.fire({
                                    html: `Error: <b>${errorData.error}</b>`,
                                    icon: "error",
                                    buttonsStyling: true,
                                    showConfirmButton: true,
                                });

                                // Update UI elements
                                updateArea.addClass('grey-block');
                                saveChangesButton.disabled = true;

                                // Update kandang select if kandangs data is available
                                if (errorData.kandangs.length > 0) {
                                    const kandangSelect = document.getElementById('kandangs');
                                    kandangSelect.innerHTML = '<option value="">=== Pilih Kandang ===</option>';
                                    errorData.kandangs.forEach(kandang => {
                                        const option = document.createElement('option');
                                        option.value = kandang.id;
                                        option.text = kandang.nama;
                                        kandangSelect.appendChild(option);
                                    });
                                    kandangInput.disabled = false;
                                } else {
                                    kandangInput.disabled = true;
                                }

                                // Clear other data
                                stocksData.length = 0;
                                updateTableBody(stocksData);
                            }
                        });

                });
            });

            $('#kandangs').change(function() {

                kandangId = $(this).val();
                if(kandangId == ''){
                    // break;
                }
                // console.log(kandangId);
                // console.log(kandangsData);

                // Find the kandang object with matching id
                let selectedKandang = kandangsData.find(kandang => kandang.id == kandangId);

                if (selectedKandang) {
                    let startDate = selectedKandang.start_date;
                    // console.log(startDate);

                    tanggalInput.disabled = false;

                    let minDat = startDate;

                    flatpickr("#tanggal", {
                        minDate: minDat,
                        dateFormat: "Y-m-d", // Adjust this format if needed
                    });

                    updateArea.removeClass('grey-block'); 
                    cardTransaksi.removeClass('grey-block');
                    kandangInput.disabled = false;

                    


                    // Get the Select2 instance
                    const itemSelect = $('#itemsSelect');

                    // Clear existing options
                    itemSelect.empty();

                    // Add new options based on the fetched data
                    stocksData.forEach(item => {
                        const option = new Option(item.item_name, item.item_id);
                        itemSelect.append(option);
                    });

                    // Disable the button
                    saveChangesButton.disabled = false;

                    updateTableBody(stocksData); 
                                // updateKandang(farmId);
                } else {
                    // console.log('Kandang not found');
                    // Handle the case where no matching kandang is found
                }


            });
        });

    // Function to update the table body with stock data
    // function updateKandang(farmId) {
    //     console.log('cek data kandang');
    //     const kandangSelect = document.getElementById('kandangs');

    //     // Clear existing options in the farmSelect dropdown
    //     while (kandangSelect.options.length > 0) {
    //             kandangSelect.remove(0);
    //     }

    //     kandangSelect.innerHTML = '<option value="">=== Pilih Kandang ===</option>';


    //     // Fetch operators for the selected farm via AJAX
    //     fetch(`/api/v1/get-kandangs/${farmId}/used`, {
    //         headers: {
    //             'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
    //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //         }
    //     })
    //     .then(response => response.json())
    //     .then(data => {
    //         if (data.kandangs && data.kandangs.length > 0) {
    //             // console.log(data.kandangs);

    //             data.kandangs.forEach(kandang => {
    //                 const option = document.createElement('option');
    //                 option.value = kandang.id;
    //                 option.text = kandang.nama;
    //                 kandangSelect.appendChild(option);
    //             });
    //         }
    //     })
    //     .catch(error => console.error('Error fetching kandangs:', error));
    // }

    // Function to update the table body with stock data
    function updateTableBody(jsonData) {
        const tableBody = $('#stockTableBody');
        tableBody.empty(); // Clear existing rows

        jsonData.forEach(item => {
            const newRow = `
                <tr>
                    <td>${item.item_name}</td>
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
            ternak_mati: ternak_mati.value,
            ternak_afkir: ternak_afkir.value,
            ternak_jual: ternak_jual.value,
            // ... other data you might need to include (e.g., 'parameter', 'tanggal')
        };

        // You can now send this 'finalData' to your server using AJAX or other methods
        // console.log(JSON.stringify(finalData)); // Example: Log the JSON data to the console

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
                // console.log('Stock data updated successfully!', response);
                toastr.success(response.message); 
                // table.ajax.reload();
                Livewire.dispatch('closeFormPemakaian');
                // Reset Select2 fields
                $('#selectedFarm').val('').trigger('change');
                $('#kandangs').val('').trigger('change');
                
                // Reset Flatpickr
                if (document.getElementById('tanggal')._flatpickr) {
                    document.getElementById('tanggal')._flatpickr.clear();
                }
                
                // Disable fields
                $('#kandangs').prop('disabled', true);
                $('#tanggal').prop('disabled', true);
                
                // Reset other form elements if needed
                
                // Re-initialize grey blocks
                $('#formDiva').addClass('grey-block');
                $('#cardTransaksi').addClass('grey-block');
                const tableBody = $('#stockTableBody');
                tableBody.empty(); // Clear existing rows
                
                // After successful form submission or when closing the form
                resetTernakForm();
                
                // Disable save button
                $('#saveChangesButton').prop('disabled', true);

                // Reload DataTables
                $('.table').each(function() {
                    if ($.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable().ajax.reload();
                    }
                });
            },
            error: function(xhr) {
                // console.error('Error updating stock data:', xhr.responseText);
                
                let errorMessage = 'An error occurred while updating stock data.';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    if (xhr.responseJSON.error) {
                        errorMessage += '<br>' + xhr.responseJSON.error;
                    }
                }
                
                toastr.error(errorMessage, 'Error', {
                    closeButton: true,
                    timeOut: 0,
                    extendedTimeOut: 0,
                    progressBar: true,
                    enableHtml: true
                });
            }
        });
    }

    function resetTernakForm() {
        document.getElementById('ternakForm').reset();
        document.getElementById('ternak_mati').value = '0';
        document.getElementById('ternak_afkir').value = '0';
        document.getElementById('ternak_jual').value = '0';
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
                        // console.log('test update dropdown');
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