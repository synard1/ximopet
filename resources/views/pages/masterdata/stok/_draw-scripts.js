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
document.querySelectorAll('[data-kt-action="view_detail_stok"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        destroyDetailsTable();

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

            destroyDetailsTable();
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

function getDetailStoks(transaksiId, farmId, startDate, endDate) {
    // console.log(transaksiId + " - " + farmId);
    //Destroy old data
    destroyDetailsTable();
    // resetDateRange();
    resetTableHeader();

    const table = new DataTable('#detailsStokTable', {
        info: false,
        ordering: false,
        paging: false,
        dom: 'Bfrtip',
        buttons: [
            'excel', 'pdf', 'print'
        ],
        ajax: {
            url: "/api/v1/stocks",
            type: 'POST',
            data: function (d) {
                d.type = 'details'
                d.farm_id = farmId;
                d.id = transaksiId;
                d.start_date = startDate;
                d.end_date = endDate;
            }
        },
        columns: [
            { 
                data: '#',
                autoWidth: true,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                } 
            },
            { 
                data: 'tanggal',
                autoWidth: true,
                render: function(data, type, row) {
                    if (type === 'display' || type === 'filter') {
                        var date = new Date(data);
                        var day = date.getDate().toString().padStart(2, '0');
                        var month = (date.getMonth() + 1).toString().padStart(2, '0');
                        var year = date.getFullYear();
                        return day + '-' + month + '-' + year;
                    }
                    return data;
                }
            },
            { data: 'jenis', autoWidth: true },
            { data: 'nama_farm', autoWidth: true },
            { data: 'item_name', autoWidth: true },
            { data: 'perusahaan_nama', autoWidth: true },
            { data: 'quantity', autoWidth: true },
            // { 
            //     data: 'hpp',
            //     autoWidth: true,
            //     render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) 
            // },
            // { 
            //     data: 'stok_awal',
            //     autoWidth: true,
            //     render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
            // },
            // { 
            //     data: 'stok_masuk',
            //     autoWidth: true,
            //     render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
            // },
            // { 
            //     data: 'stok_keluar',
            //     autoWidth: true,
            //     render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
            // },
            // { 
            //     data: 'stok_akhir',
            //     autoWidth: true,
            //     render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
            // },
            // { data: 'satuan', autoWidth: true },
        ]
    });

}

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

