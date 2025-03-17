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
                Livewire.dispatch('delete_transaksi_pembelian', [this.getAttribute('data-kt-transaksi-id')]);
            }
        });
    });
});

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="update_row_stok"]').forEach(function (element) {
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
            html: `Membuka Data <b>`+ transaksiName +`</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            Livewire.dispatch('editPembelian', [transaksiId]);

            // flatpickr("#tanggalPembelian", {
            //     enableTime: true,
            //     dateFormat: "Y-m-d H:i",
            // });

            // flatpickr("#tanggal", {
            //     enableTime: true,
            //     dateFormat: "Y-m-d H:i", 
            //     defaultDate: '{{ $tanggal }}',
            // });

            getDetailsPurchasing(transaksiId);

            const cardList = document.getElementById(`stokTableCard`);
            cardList.style.display = 'none';
            // cardList.classList.toggle('d-none');

            const cardForm = document.getElementById(`stokFormCard`);
            cardForm.style.display = 'block';

            
        });
        
    });
});

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="view_details"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        var modal = document.getElementById('kt_modal_pembelian_details');

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
            getDetails(transaksiId);

            $('#kt_modal_pembelian_details').modal('show');
            // Livewire.dispatch('editKandang', [transaksiId]);
        });
        
    });
});

// Add click event listener to edit No. SJ buttons
document.querySelectorAll('[data-kt-action="edit_sj"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        
        // Select parent row
        const parent = e.target.closest('tr');

        // Get transaksi ID
        const transaksiId = this.getAttribute('data-kt-transaksi-id');

        // Get current No. SJ
        const currentNoSj = parent.querySelectorAll('td')[1].innerText;

        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="editNoSjModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit No. SJ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="text" class="form-control" id="noSjInputModal" value="${currentNoSj}">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="saveNoSj">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Append modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Initialize modal
        const modal = new bootstrap.Modal(document.getElementById('editNoSjModal'));
        modal.show();

        // Add event listener to save button
        document.getElementById('saveNoSj').addEventListener('click', function() {
            const newNoSj = document.getElementById('noSjInputModal').value;

            console.log(newNoSj);
            
            
            // Here you would typically send an AJAX request or use Livewire to update the No. SJ
            // For this example, we'll just use a Livewire dispatch
            Livewire.dispatch('updateNoSj', { transaksiId: transaksiId, newNoSj: newNoSj });

            // // Close the modal
            // modal.hide();

            // // Show success message
            // Swal.fire({
            //     text: 'No. SJ has been updated successfully.',
            //     icon: 'success',
            //     buttonsStyling: false,
            //     confirmButtonText: 'Ok, got it!',
            //     customClass: {
            //         confirmButton: 'btn btn-primary'
            //     }
            // }).then(function() {
            //     // Optionally, you can refresh the table or update the specific row here
            // });
        });

        // Remove modal from DOM when it's hidden
        document.getElementById('editNoSjModal').addEventListener('hidden.bs.modal', function () {
            this.remove();
        });
    });
});



// // Listen for 'success' event emitted by Livewire
// Livewire.on('success', (message) => {
//     // Reload the transaksis-table datatable
//     LaravelDataTables['transaksis-table'].ajax.reload();
// });
