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
document.querySelectorAll('[data-kt-action="delete_row_doc"]').forEach(function (element) {
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
                Livewire.dispatch('delete_transaksi_doc', [this.getAttribute('data-kt-transaksi-id')]);
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
                render: $.fn.dataTable.render.number( '.', ',', 2, '' ) 
            },
            { data: 'terpakai', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
            { data: 'sisa', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
            { 
                data: 'harga',
                // className: 'editable', // Tambahkan className di sini 
                render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) 
            },
            { data: 'sub_total', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) }
        ]
    });

    // Make cells editable (using a simple approach for now)
    // table.on('click', 'tbody td.editable', function() {
    //     var cell = $(this);
    //     // var originalValue = cell.text();

    //     // Extract the numeric value without the prefix
    //     var originalValueText = cell.text();
    //     var originalValue = parseFloat(originalValueText.replace(/[^0-9.-]+/g, '')); // Remove non-numeric characters


    //     // Get the row data to check 'terpakai'
    //     var rowData = table.row(cell.closest('tr')).data();
    //     console.log(rowData.terpakai);

    //     // Disable editing if 'terpakai' is greater than 0 or if it's null/undefined
    //     if (rowData.terpakai > 0 || rowData.terpakai === null || rowData.terpakai === undefined) {
    //         return; // Exit the click handler, preventing editing
    //     }

    //     // Create an input field for editing
    //     var input = $('<input type="text" value="' + originalValue + '">');
    //     cell.html(input);
    //     input.focus();

    //     // Handle saving the edit
    //     input.blur(function() {
    //         // var newValue = input.val();
    //         var newValue = parseFloat(input.val()); // Parse the new value as a float

    //         // if (newValue !== originalValue) {
    //         if (!isNaN(newValue) && newValue !== originalValue) { 
    //             // Get the row and column data
    //             var rowData = table.row(cell.closest('tr')).data();
    //             var columnIndex = table.cell(cell).index().column;
    //             var columnData = table.settings().init().columns[columnIndex];

    //             console.log('edit via ajax');
                

    //             // Send AJAX request to update the data
    //             $.ajax({
    //                 url: '/api/v1/transaksi', // Replace with your actual Laravel route
    //                 method: 'POST',
    //                 data: {
    //                     // Include the row's ID or other identifiers
    //                     id: rowData.id,
    //                     column: columnData.data, // Get the column's data property
    //                     value: newValue,
    //                     task: 'UPDATE'
    //                 },
    //                 success: function(response) {
    //                     // Handle successful update
    //                     cell.text(newValue);
    //                     toastr.success(response.message);
    //                     table.ajax.reload();
    //                 },
    //                 error: function(error) {
    //                     // Handle errors
    //                     // cell.text(originalValue); 
    //                     // cell.data('originalValue', originalValue); // Store original value in data attribute
    //                     table.ajax.reload();
    //                     alert('Error updating value.');
    //                 }
    //             });
    //         } else if (isNaN(newValue) || newValue === '') {
    //             alert('Error value cannot blank');
    //             table.ajax.reload();

    //         } else {
    //             // No change, revert to original value
    //             table.ajax.reload();
    //         }
    //     });
    // });
}

// Listen for 'success' event emitted by Livewire
// Livewire.on('success', (message) => {
//     // Reload the transaksis-table datatable
//     LaravelDataTables['transaksis-table'].ajax.reload();
// });
