// Initialize KTMenu
KTMenu.init();

// const showLoadingSpinner = () => {
//     const loadingEl = document.createElement("div");
//     document.body.append(loadingEl);
//     loadingEl.classList.add("page-loader");
//     loadingEl.innerHTML = `
//         <span class="spinner-border text-primary" role="status">
//             <span class="visually-hidden">Loading...</span>
//         </span>
//     `;
//     KTApp.showPageLoading();
//     setTimeout(() => {
//         KTApp.hidePageLoading();
//         loadingEl.remove();
//     }, 3000);
// };

document.querySelectorAll('[data-kt-button="new_penjualan"]').forEach(function (element) {
    element.addEventListener('click', function () {
        Swal.fire({
            html: `Load Data Form`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            $('#penjualanTernak').modal('show');
        });
    });

    // Optionally, listen for changes in the Select2 dropdown
    $('#farmSelect').on('select2:select', function (e) {
        const selectedData = e.params.data;
        const farmId = selectedData.element.getAttribute('data-farm-id');
        console.log('Selected Farm ID:', farmId);
        // Now you have the farmId, which you can use in your application
    });
});

document.querySelectorAll('[data-kt-action="edit_penjualan"]').forEach(function (element) {
    element.addEventListener('click', function (event) {
        Swal.fire({
            html: `Load Data Form`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {

            event.preventDefault();
            const transaksiId = event.target.getAttribute('data-kt-transaksi-id');

            // console.log('edit');
            
            Livewire.dispatch('editPenjualan', [transaksiId]);
        });
    });

    // Optionally, listen for changes in the Select2 dropdown
    $('#farmSelect').on('select2:select', function (e) {
        const selectedData = e.params.data;
        const farmId = selectedData.element.getAttribute('data-farm-id');
        console.log('Selected Farm ID:', farmId);
        // Now you have the farmId, which you can use in your application
    });
});


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
                const id = element.getAttribute('data-kt-transaksi-id');
                // Post reverse stock reduction using AJAX with headers
                $.ajax({
                    url: '/api/v1/stocks',
                    method: 'POST',
                    data: { type: 'reverse', id: id, jenis: 'Pemakaian' },
                }).then(function (response) {
                    if (response.status === 'success') {
                        var table = new DataTable('#pemakaianStoks-table');
                        // table.destroy();
                        // table.ajax.reload();
                        toastr.success(response.message);
                        // if (LaravelDataTables && LaravelDataTables['transaksis-table'] && typeof LaravelDataTables['transaksis-table'].ajax === 'function') {
                        //     LaravelDataTables['transaksis-table'].ajax.reload();
                        // } else {
                        //     console.error('LaravelDataTables or transaksis-table is not properly initialized');
                        // }

                        // Reload DataTables
                        $('.table').each(function() {
                            if ($.fn.DataTable.isDataTable(this)) {
                                $(this).DataTable().ajax.reload();
                            }
                        });
                    }
                });

                // Livewire.dispatch('deletePemakaianStok', [this.getAttribute('data-kt-transaksi-id')]);
            }
        });
    });
});

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="view_detail_pemakaian"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        var modal = document.getElementById('kt_modal_pemakaian_details');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const transaksiId = event.currentTarget.getAttribute('data-kt-transaksi-id');

        // Get suppliers name
        const transaksiSupplier = parent.querySelectorAll('td')[2].innerText;
        const transaksiFaktur = parent.querySelectorAll('td')[0].innerText;
        const transaksiTanggalRaw = parent.querySelectorAll('td')[3].innerText;
        const transaksiTanggal = formatDate(transaksiTanggalRaw);


        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data <b>${transaksiFaktur} - ${transaksiSupplier}</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            modal.addEventListener('show.bs.modal', function (event) {
                // Button that triggered the modal
                var button = event.relatedTarget;
                // Extract info from data-* attributes
                var title = `${transaksiTanggal} - ${transaksiSupplier} Detail Data`;
                // Update the modal's title
                var modalTitle = modal.querySelector('.modal-title');
                modalTitle.textContent = title;
            });
            getDetailPemakaian(transaksiId);

            $('#kt_modal_pemakaian_details').modal('show');
            // Livewire.dispatch('editKandang', [transaksiId]);
        });
        
    });
});





// Add this function at the end of your file or in a separate utilities file
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

