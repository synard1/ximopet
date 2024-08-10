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
                Livewire.dispatch('delete_supplier', [this.getAttribute('data-kt-supplier-id')]);
            }
        });
    });
});

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="update_row"]').forEach(function (element) {
    element.addEventListener('click', function () {
        // Select parent row
        const parent = e.target.closest('tr');
        // Get supplier ID
        const supplierId = event.currentTarget.getAttribute('data-kt-supplier-id');

        // Get subject name
        const incidentTitle = parent.querySelectorAll('td')[2].innerText;
        
        console.log(supplierId);

        

        // showLoadingSpinner();
        Livewire.on('update_supplier', function (event) {
            // $('#kt_modal_master_supplier').modal('show');
            // Assuming event.detail.supplierId contains the actual ID:
            this.set('supplier_id', event.detail.supplierId); 
        });
        
        Livewire.dispatch('edit', [supplierId]);
        // this.set('supplier_id', event.detail.supplierId); 


        // const modal = new bootstrap.Modal(document.getElementById('kt_modal_1'));
        // modal.show();

        // toggleDiv('kt_modal_supplier_edit');
        
    });
});

// Listen for 'success' event emitted by Livewire
Livewire.on('success', (message) => {
    // Reload the suppliers-table datatable
    LaravelDataTables['suppliers-table'].ajax.reload();
});
