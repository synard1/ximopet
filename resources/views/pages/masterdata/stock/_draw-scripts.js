// Initialize KTMenu
KTMenu.init();

const showLoadingSpinner = () => {
    const loadingEl = document.createElement("div");
    document.body.append(loadingEl);
    loadingEl.classList.add("page-loader");
    loadingEl.innerHTML = `
        <span class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </span>
    `;
    KTApp.showPageLoading();
    setTimeout(() => {
        KTApp.hidePageLoading();
        loadingEl.remove();
    }, 3000);
};



// Add click event listener to delete buttons
document.querySelectorAll('[data-kt-action="delete_row"]').forEach(function (element) {
    element.addEventListener('click', function () {
        Swal.fire({
            text: 'Are you sure you want to remove?',
            icon: 'warning',
            buttonsStyling: false,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary',
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('delete_stok', [this.getAttribute('data-kt-stok-id')]);
            }
        });
    });
});

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="update_row"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        // Select parent row
        const parent = e.target.closest('tr');

        // Get stok ID
        const stokId = event.currentTarget.getAttribute('data-kt-stok-id');

        // Get subject name
        const stokName = parent.querySelectorAll('td')[1].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data <b>`+ stokName +`</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            Livewire.dispatch('editStok', [stokId]);
        });
        
    });
});

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="view_detail_supplystocks"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        // destroyDetailsTable();

        var modal = document.getElementById('kt_modal_supplystock_details');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const farmId = event.currentTarget.getAttribute('data-farm-id');
        const supplyId = event.currentTarget.getAttribute('data-supply-id');

        console.log('farmId '+ farmId);
        

        // Get suppliers name
        const transaksiSupplier = parent.querySelectorAll('td')[1].innerText;
        const transaksiFaktur = parent.querySelectorAll('td')[0].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data <b>${transaksiFaktur} - ${transaksiSupplier}</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {

            // destroyDetailsTable();
            resetDateRange();

            modal.addEventListener('show.bs.modal', function (event) {
                
                // Button that triggered the modal
                var button = event.relatedTarget;
                // Extract info from data-* attributes
                var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                // Update the modal's title
                var modalTitle = modal.querySelector('.modal-title');
                modalTitle.textContent = title;
            });

            $('#dateRange').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
    
            $('#applyDateFilter').click(function() {
                var dateRange = $('#dateRange').val();
                var dates = dateRange.split(' - ');
                var startDate = dates[0];
                var endDate = dates[1];
    
                // Destroy the existing DataTable
                if ($.fn.DataTable.isDataTable('#detailsStokTable')) {
                    // destroyDetailsTable();
                }
    
                // Reinitialize the DataTable with new parameters
                getDetailStoksGrouped(farmId, supplyId, startDate, endDate);
            });

            $('#kt_modal_supplystock_details').modal('show');
        });
        
    });
});

function getDetailFeedGrouped(feedId, livestockId, startDate, endDate) {
    // destroyDetailsTable();

    $.ajax({
        url: "/api/v2/feed/usages/details",
        type: 'POST',
        data: {
            livestock_id: livestockId,
            feed_id: feedId,
            start_date: startDate,
            end_date: endDate
        },
        success: function(response) {
            if (response.status === 'success') {
                renderFeedstockDetails(response.data);
            } else {
                $('#feedstockDetailsContainer').html('<p class="text-danger">Gagal memuat data.</p>');
            }
        },
        error: function(xhr) {
            console.error(xhr);
            $('#feedstockDetailsContainer').html('<p class="text-danger">Terjadi kesalahan server.</p>');
        }
    });
}

function getDetailStoksGrouped(farmId, supplyId, startDate, endDate) {
    // destroyDetailsTable();

    $.ajax({
        url: "/api/v2/supply/usages/details",
        type: 'POST',
        data: {
            farm_id: farmId,
            supply_id: supplyId,
            start_date: startDate,
            end_date: endDate
        },
        success: function(response) {
            if (response.status === 'success') {
                rendersupplystockDetails(response.data);
            } else {
                $('#supplystockDetailsContainer').html('<p class="text-danger">Gagal memuat data.</p>');
            }
        },
        error: function(xhr) {
            console.error(xhr);
            $('#supplystockDetailsContainer').html('<p class="text-danger">Terjadi kesalahan server.</p>');
        }
    });
}

function renderFeedstockDetails(data) {
    const container = $('#feedstockDetailsContainer');
    container.empty();    

    data.forEach((group, index) => {
        const feed = group.feed_purchase_info;
        const histories = group.histories;

        const batchId = `batch_${index}`;

        const card = `
            <div class="accordion-item mb-4 border border-primary rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#${batchId}" aria-expanded="false" aria-controls="${batchId}">
                        ${feed.feed_name} - Batch ${feed.no_batch} | Tanggal: ${feed.tanggal} | Harga: Rp ${parseFloat(feed.harga).toLocaleString('id-ID')}
                    </button>
                </h2>
                <div id="${batchId}" class="accordion-collapse collapse" data-bs-parent="#feedstockDetailsContainer">
                    <div class="accordion-body p-0">
                        <div class="d-flex justify-content-end gap-2 px-3 py-2">
                            <button class="btn btn-sm btn-outline-success" onclick="exportTableToExcel('${batchId}')">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </button>
                            <button class="btn btn-sm btn-outline-dark" onclick="printTable('${batchId}')">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>

                        <div class="table-responsive p-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-start">Keterangan</th>
                                        <th class="text-end">Stok Awal</th>
                                        <th class="text-end">Masuk</th>
                                        <th class="text-end">Keluar</th>
                                        <th class="text-end">Stok Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${histories.map(item => {
                                        let rowClass = '';
                                        if (item.keterangan.toLowerCase().includes('pembelian')) {
                                            rowClass = 'table-success';
                                        } else if (item.keterangan.toLowerCase().includes('mutasi')) {
                                            rowClass = 'table-warning';
                                        } else if (item.keterangan.toLowerCase().includes('pakai') || item.keterangan.toLowerCase().includes('pemakaian')) {
                                            rowClass = 'table-danger';
                                        }

                                        return `
                                            <tr class="${rowClass}">
                                                <td class="text-center">${item.tanggal}</td>
                                                <td>${item.keterangan}</td>
                                                <td class="text-end">${parseFloat(item.stok_awal).toLocaleString('id-ID')}</td>
                                                <td class="text-end">${parseFloat(item.masuk).toLocaleString('id-ID')}</td>
                                                <td class="text-end">${parseFloat(item.keluar).toLocaleString('id-ID')}</td>
                                                <td class="text-end">${parseFloat(item.stok_akhir).toLocaleString('id-ID')}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.append(card);
    });
}

function rendersupplystockDetails(data) {
    const container = $('#supplystockDetailsContainer');
    container.empty();

    data.forEach((group, index) => {
        const supply = group.supply_purchase_info;
        const histories = group.histories;

        const batchId = `batch_${index}`;

        const card = `
            <div class="accordion-item mb-4 border border-primary rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#${batchId}" aria-expanded="false" aria-controls="${batchId}">
                        ${supply.supply_name} - Batch ${supply.no_batch} | Tanggal: ${supply.tanggal} | Harga: Rp ${parseFloat(supply.harga).toLocaleString('id-ID')}
                    </button>
                </h2>
                <div id="${batchId}" class="accordion-collapse collapse" data-bs-parent="#supplystockDetailsContainer">
                    <div class="accordion-body p-0">
                        <div class="d-flex justify-content-end gap-2 px-3 py-2">
                            <button class="btn btn-sm btn-outline-success" onclick="exportTableToExcel('${batchId}')">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </button>
                            <button class="btn btn-sm btn-outline-dark" onclick="printTable('${batchId}')">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>

                        <div class="table-responsive p-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-start">Keterangan</th>
                                        <th class="text-end">Stok Awal</th>
                                        <th class="text-end">Masuk</th>
                                        <th class="text-end">Keluar</th>
                                        <th class="text-end">Stok Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${histories.map(item => {
                                        let rowClass = '';
                                        if (item.keterangan.toLowerCase().includes('pembelian')) {
                                            rowClass = 'table-success';
                                        } else if (item.keterangan.toLowerCase().includes('mutasi')) {
                                            rowClass = 'table-warning';
                                        } else if (item.keterangan.toLowerCase().includes('pakai') || item.keterangan.toLowerCase().includes('pemakaian')) {
                                            rowClass = 'table-danger';
                                        }

                                        return `
                                            <tr class="${rowClass}">
                                                <td class="text-center">${item.tanggal}</td>
                                                <td>${item.keterangan}</td>
                                                <td class="text-end">${parseFloat(item.stok_awal).toLocaleString('id-ID')}</td>
                                                <td class="text-end">${parseFloat(item.masuk).toLocaleString('id-ID')}</td>
                                                <td class="text-end">${parseFloat(item.keluar).toLocaleString('id-ID')}</td>
                                                <td class="text-end">${parseFloat(item.stok_akhir).toLocaleString('id-ID')}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.append(card);
    });
}


// function getDetailStoks(farmId, supplyId, startDate, endDate) {

//     //Destroy old data
//     destroyDetailsTable();
//     // resetDateRange();
//     resetTableHeader();

//     const table = new DataTable('#detailsStokTable', {
//         info: true,
//         ordering: true,
//         paging: true,
//         pageLength: 10, // Set the initial number of rows per page
//         lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]], // Add options for rows per page, including "All"
//         dom: 'Blfrtip',
//         buttons: [
//             'excel', 'pdf', 'print'
//         ],
//         ajax: {
//             url: "/api/v2/supply/usages/details",
//             type: 'POST',
//             data: function (d) {
//                 d.livestock_id = farmId;
//                 d.supply_id = supplyId;
//                 d.start_date = startDate;
//                 d.end_date = endDate;
//             }
//         },
//         columns: [
//             { 
//                 data: '#',
//                 autoWidth: true,
//                 render: function (data, type, row, meta) {
//                     return meta.row + meta.settings._iDisplayStart + 1;
//                 } 
//             },
//             { 
//                 data: 'tanggal',
//                 autoWidth: true,
//                 render: function(data, type, row) {
//                     if (type === 'display' || type === 'filter') {
//                         var date = new Date(data);
//                         var day = date.getDate().toString().padStart(2, '0');
//                         var month = (date.getMonth() + 1).toString().padStart(2, '0');
//                         var year = date.getFullYear();
//                         return day + '-' + month + '-' + year;
//                     }
//                     return data;
//                 }
//             },
//             { data: 'farm_name', autoWidth: true },
//             { data: 'kandang_name', autoWidth: true },
//             { data: 'supply_name', autoWidth: true },
//             { data: 'jumlah', autoWidth: true },
//         ]
//     });

// }


document.querySelectorAll('[data-kt-action="view_detail_feedstoks"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        // destroyDetailsTable();

        var modal = document.getElementById('kt_modal_feedstock_details');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const feedId = event.currentTarget.getAttribute('data-feed-id');
        const livestockId = event.currentTarget.getAttribute('data-livestock-id');        

        // Get suppliers name
        const transaksiSupplier = parent.querySelectorAll('td')[1].innerText;
        const transaksiFaktur = parent.querySelectorAll('td')[0].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data <b>${transaksiFaktur} - ${transaksiSupplier}</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {

            // destroyDetailsTable();
            resetDateRange();
            resetFarmSelect();


            modal.addEventListener('show.bs.modal', function (event) {
                
                // Button that triggered the modal
                var button = event.relatedTarget;
                // Extract info from data-* attributes
                var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                // Update the modal's title
                var modalTitle = modal.querySelector('.modal-title');
                modalTitle.textContent = title;
            });

            $('#dateRange').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
    
            $('#applyDateFilter').click(function() {
                var dateRange = $('#dateRange').val();
                var farmId = $('#farmSelect').val();
                var dates = dateRange.split(' - ');
                var startDate = dates[0];
                var endDate = dates[1];
    
                // Destroy the existing DataTable
                if ($.fn.DataTable.isDataTable('#detailsStokTable')) {
                    destroyDetailsTable();
                }
    
                // Reinitialize the DataTable with new parameters
                getDetailFeedGrouped(feedId, livestockId, startDate, endDate);
            });

            // Get the farm select element
            const farmSelect = document.getElementById('farmSelect');
        
            // Add event listener for change event
            farmSelect.addEventListener('change', function() {
                // Get the selected farm ID
                const selectedFarmId = this.value;
                var dateRange = $('#dateRange').val();
                var dates = dateRange.split(' - ');
                var startDate = dates[0];
                var endDate = dates[1];
                getDetailFeedGrouped(feedId, livestockId, startDate, endDate);
                
            });

            $('#kt_modal_feedstock_details').modal('show');
        });
        
    });
});

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="view_detail_stok"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        // destroyDetailsTable();

        var modal = document.getElementById('kt_modal_stok_details');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const transaksiId = event.currentTarget.getAttribute('data-item-id');

        // Get suppliers name
        const transaksiSupplier = parent.querySelectorAll('td')[1].innerText;
        const transaksiFaktur = parent.querySelectorAll('td')[0].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data <b>${transaksiFaktur} - ${transaksiSupplier}</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {

            // destroyDetailsTable();
            resetDateRange();
            resetFarmSelect();


            modal.addEventListener('show.bs.modal', function (event) {
                
                // Button that triggered the modal
                var button = event.relatedTarget;
                // Extract info from data-* attributes
                var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                // Update the modal's title
                var modalTitle = modal.querySelector('.modal-title');
                modalTitle.textContent = title;
            });

            $('#dateRange').daterangepicker({
                opens: 'left',
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
    
            $('#applyDateFilter').click(function() {
                var dateRange = $('#dateRange').val();
                var farmId = $('#farmSelect').val();
                var dates = dateRange.split(' - ');
                var startDate = dates[0];
                var endDate = dates[1];
    
                // Destroy the existing DataTable
                if ($.fn.DataTable.isDataTable('#detailsStokTable')) {
                    destroyDetailsTable();
                }
    
                // Reinitialize the DataTable with new parameters
                getDetailStoks(transaksiId, farmId, startDate, endDate);
            });

            // $('#farmSelect').change(function() {

            //     farmId = $(this).val();
            //     if(farmId == ''){
            //         // break;
            //     }

            //     getDetailStoks(transaksiId, selectedFarmId);

            // });

            
            // Get the farm select element
            const farmSelect = document.getElementById('farmSelect');
            
            // Add event listener for change event
            farmSelect.addEventListener('change', function() {
                //Destroy previous table
                // $('#detailsStokTable').DataTable().destroy();


                // Get the selected farm ID
                const selectedFarmId = this.value;
                var dateRange = $('#dateRange').val();
                var dates = dateRange.split(' - ');
                var startDate = dates[0];
                var endDate = dates[1];
                getDetailStoks(transaksiId, selectedFarmId, startDate, endDate);

                
                // Call getDetailStoks with both transaksiId and selectedFarmId
            });

            // Initial call to getDetailStoks with the default selected farm
            // const initialSelectedFarmId = farmSelect.value;
            // getDetailStoks(transaksiId, initialSelectedFarmId);

            $('#kt_modal_stok_details').modal('show');
            // Livewire.dispatch('editKandang', [transaksiId]);
        });
        
    });
});

// Add click event to transfer stock
document.querySelectorAll('[data-kt-action="supply_transfer_row"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        // destroyDetailsTable();

        console.log('klik');
        

        var modal = document.getElementById('modalsupplystockTransfer');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const transaksiId = event.currentTarget.getAttribute('data-kt-transaction-id');

        // Get suppliers name
        // const transaksiSupplier = parent.querySelectorAll('td')[1].innerText;
        // const transaksiFaktur = parent.querySelectorAll('td')[0].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data Feed <b>${transaksiId}</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {

            $('#modalsupplystockTransfer').modal('show');

            modal.addEventListener('show.bs.modal', function (event) {
                
                // Button that triggered the modal
                // var button = event.relatedTarget;
                // Extract info from data-* attributes
                // var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                // Update the modal's title
                // var modalTitle = modal.querySelector('.modal-title');
                // modalTitle.textContent = title;
                console.log('modal open');
                
            });
        });
        
    });
});

document.querySelectorAll('[data-kt-action="transfer_row"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        // destroyDetailsTable();

        var modal = document.getElementById('modalStokTransfer');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const transaksiId = event.currentTarget.getAttribute('data-kt-stok-id');

        // Get suppliers name
        // const transaksiSupplier = parent.querySelectorAll('td')[1].innerText;
        // const transaksiFaktur = parent.querySelectorAll('td')[0].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data <b>${transaksiId}</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {

            $('#modalStokTransfer').modal('show');

            modal.addEventListener('show.bs.modal', function (event) {
                
                // Button that triggered the modal
                // var button = event.relatedTarget;
                // Extract info from data-* attributes
                // var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                // Update the modal's title
                // var modalTitle = modal.querySelector('.modal-title');
                // modalTitle.textContent = title;
                console.log('modal open');
                
            });
        });
        
    });
});

document.querySelectorAll('[data-kt-action="feed_transfer_row"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        // destroyDetailsTable();

        var modal = document.getElementById('modalFeedstockTransfer');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const transaksiId = event.currentTarget.getAttribute('data-kt-transaction-id');

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data <b>${transaksiId}</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {

            $('#modalFeedstockTransfer').modal('show');

            // modal.addEventListener('show.bs.modal', function (event) {
            //     console.log('modal open');
                
            // });
        });
        
    });
});


// function getDetailStoks(transaksiId, farmId, startDate, endDate) {

//     //Destroy old data
//     destroyDetailsTable();
//     // resetDateRange();
//     resetTableHeader();

//     const table = new DataTable('#detailsStokTable', {
//         info: true,
//         ordering: true,
//         paging: true,
//         pageLength: 10, // Set the initial number of rows per page
//         lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]], // Add options for rows per page, including "All"
//         dom: 'Blfrtip',
//         buttons: [
//             'excel', 'pdf', 'print'
//         ],
//         ajax: {
//             url: "/api/v1/stocks",
//             type: 'POST',
//             data: function (d) {
//                 d.type = 'details'
//                 d.farm_id = farmId;
//                 d.id = transaksiId;
//                 d.start_date = startDate;
//                 d.end_date = endDate;
//             }
//         },
//         columns: [
//             { 
//                 data: '#',
//                 autoWidth: true,
//                 render: function (data, type, row, meta) {
//                     return meta.row + meta.settings._iDisplayStart + 1;
//                 } 
//             },
//             { 
//                 data: 'tanggal',
//                 autoWidth: true,
//                 render: function(data, type, row) {
//                     if (type === 'display' || type === 'filter') {
//                         var date = new Date(data);
//                         var day = date.getDate().toString().padStart(2, '0');
//                         var month = (date.getMonth() + 1).toString().padStart(2, '0');
//                         var year = date.getFullYear();
//                         return day + '-' + month + '-' + year;
//                     }
//                     return data;
//                 }
//             },
//             { data: 'jenis', autoWidth: true },
//             { data: 'nama_farm', autoWidth: true },
//             { data: 'nama_kandang', autoWidth: true },
//             { data: 'item_name', autoWidth: true },
//             { data: 'quantity', autoWidth: true },
//         ]
//     });

// }

// function getDetailStoks(transaksiId, farmId, startDate, endDate) {
//     // console.log(transaksiId + " - " + farmId);
//     //Destroy old data
//     destroyDetailsTable();
//     // resetDateRange();
//     resetTableHeader();

//     const table = new DataTable('#detailsStokTable', {
//         info: true,
//         ordering: true,
//         paging: true,
//         pageLength: 10, // Set the initial number of rows per page
//         lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]], // Add options for rows per page, including "All"
//         dom: 'Blfrtip',
//         buttons: [
//             'excel', 'pdf', 'print'
//         ],
//         ajax: {
//             url: "/api/v1/stocks",
//             type: 'POST',
//             data: function (d) {
//                 d.type = 'details'
//                 d.farm_id = farmId;
//                 d.id = transaksiId;
//                 d.start_date = startDate;
//                 d.end_date = endDate;
//             }
//         },
//         columns: [
//             { 
//                 data: '#',
//                 autoWidth: true,
//                 render: function (data, type, row, meta) {
//                     return meta.row + meta.settings._iDisplayStart + 1;
//                 } 
//             },
//             { 
//                 data: 'tanggal',
//                 autoWidth: true,
//                 render: function(data, type, row) {
//                     if (type === 'display' || type === 'filter') {
//                         var date = new Date(data);
//                         var day = date.getDate().toString().padStart(2, '0');
//                         var month = (date.getMonth() + 1).toString().padStart(2, '0');
//                         var year = date.getFullYear();
//                         return day + '-' + month + '-' + year;
//                     }
//                     return data;
//                 }
//             },
//             { data: 'jenis', autoWidth: true },
//             { data: 'nama_farm', autoWidth: true },
//             { data: 'nama_kandang', autoWidth: true },
//             { data: 'item_name', autoWidth: true },
//             // { data: 'perusahaan_nama', autoWidth: true },
//             { data: 'quantity', autoWidth: true },
//             // { 
//             //     data: 'hpp',
//             //     autoWidth: true,
//             //     render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) 
//             // },
//             // { 
//             //     data: 'stok_awal',
//             //     autoWidth: true,
//             //     render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
//             // },
//             // { 
//             //     data: 'stok_masuk',
//             //     autoWidth: true,
//             //     render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
//             // },
//             // { 
//             //     data: 'stok_keluar',
//             //     autoWidth: true,
//             //     render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
//             // },
//             // { 
//             //     data: 'stok_akhir',
//             //     autoWidth: true,
//             //     render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
//             // },
//             // { data: 'satuan', autoWidth: true },
//         ]
//     });

// }

// Listen for 'success' event emitted by Livewire
Livewire.on('success', (message) => {
    // Reload the stoks-table datatable
    LaravelDataTables['stoks-table'].ajax.reload();
});


// Function to destroy DataTable on modal close
// $('#kt_modal_stok_details').on('hidden.bs.modal', function () {
//     if ($.fn.DataTable.isDataTable('#detailsStokTable')) {
//         $('#detailsStokTable').DataTable().destroy();
//         console.log('Details table successfully destroyed');
//     } else {
//         console.log('Details table was not a DataTable instance');
//     }
//     // $('#detailsStokTable').empty(); // Clear the table contents
// });

