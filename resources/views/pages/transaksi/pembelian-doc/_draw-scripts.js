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

// Listen for 'success' event emitted by Livewire
// Livewire.on('success', (message) => {
//     // Reload the transaksis-table datatable
//     LaravelDataTables['transaksis-table'].ajax.reload();
// });
