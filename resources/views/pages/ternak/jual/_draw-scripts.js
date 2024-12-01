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

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="view_detail_pembelian_doc"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        var modal = document.getElementById('kt_modal_pembelian_doc_details');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const transaksiId = event.currentTarget.getAttribute('data-kt-transaksi-id');

        // Get suppliers name
        const transaksiSupplier = parent.querySelectorAll('td')[2].innerText;
        const transaksiFaktur = parent.querySelectorAll('td')[0].innerText;

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
                var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                // Update the modal's title
                var modalTitle = modal.querySelector('.modal-title');
                modalTitle.textContent = title;
            });
            getDetailPembelianDoc(transaksiId);

            $('#kt_modal_pembelian_doc_details').modal('show');
            // Livewire.dispatch('editKandang', [transaksiId]);
        });
        
    });
});

// Add click event listener to delete buttons
document.querySelectorAll('[data-kt-action="delete_data"]').forEach(function (element) {
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
                Livewire.dispatch('delete', [this.getAttribute('data-kt-transaksi-id')]);
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

        // Get transaksi ID
        const transaksiId = event.currentTarget.getAttribute('data-kt-transaksi-id');

        // Get subject name
        const transaksiName = parent.querySelectorAll('td')[1].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data DOC <b>`+ transaksiName +`</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            Livewire.dispatch('editDoc', [transaksiId]);
            var modal = document.getElementById('kt_modal_new_doc');

            var myModal = new bootstrap.Modal(document.getElementById('kt_modal_new_doc'));
            myModal.show();

            modal.addEventListener('show.bs.modal', function (event) {
                // // Button that triggered the modal
                // var button = event.relatedTarget;
                // // Extract info from data-* attributes
                // var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                // // Update the modal's title
                // var modalTitle = modal.querySelector('.modal-title');
                // modalTitle.textContent = title;

                console.log('test editDoc');
            });

            console.log('test editDoc1');


        });
        
    });
});

document.querySelectorAll('[data-kt-action="new_kternak"]').forEach(function (element) {
    element.addEventListener('click', function () {
        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Load Data Form`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            fetchFarmData();
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

function getDetailPembelianDoc(param) {
    console.log(param);
    const table = new DataTable('#detailsTableDoc', {
        info: false,
        ordering: false,
        paging: false,
        // ajax: `/api/v1/transaksi/details/${param}`,
        ajax: {
            url: "/api/v1/transaksi", // Replace with your actual route
            type: 'POST', // Use POST method
            data: function (d) {
                // Add your additional data here
                d.bentuk = 'detail';
                d.jenis = 'transaksi';
                d.task = 'LIST';
                d.id = param;
            }
        },
        columns: [
            { data: '#',
                render: function (data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
                } 
            },
            { data: 'nama' },
            { 
                data: 'qty',
                // className: 'editable', // Tambahkan className di sini 
                render: $.fn.dataTable.render.number( '.', ',', 0, '' ) 
            },
            { data: 'terpakai', render: $.fn.dataTable.render.number( '.', ',', 0, '' ) },
            { data: 'sisa', render: $.fn.dataTable.render.number( '.', ',', 0, '' ) },
            { 
                data: 'harga_beli',
                // className: 'editable', // Tambahkan className di sini 
                render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) 
            },
            { 
                data: 'berat_beli', 
                render: function(data, type, row) {
                    let weight = parseFloat(data);
                    if (weight >= 1000000) {
                        return (weight / 1000000).toFixed(2) + ' Ton';
                    } else if (weight >= 1000) {
                        return (weight / 1000).toFixed(2) + ' Kg';
                    } else {
                        return weight.toFixed(2) + ' gram';
                    }
                }
            },
            { data: 'harga_jual', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) },
            { data: 'berat_jual', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
            // { data: 'sub_total', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) }
        ]
    });

}

$('#farmSelect').change(function() {
    farmId = $(this).val();
    if(farmId == ''){
        // break;
        return;
    }
    fetchKandangData(farmId);
    // console.log(farmId);
});

