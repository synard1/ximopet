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
document
    .querySelectorAll('[data-kt-action="view_details"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();
            var modal = document.getElementById("kt_modal_mutation_details");

            // Select parent row
            const parent = e.target.closest("tr");

            // Get transaksi ID
            const transaksiId = event.currentTarget.getAttribute(
                "data-kt-transaksi-id"
            );

            // Get suppliers name
            const transaksiSupplier =
                parent.querySelectorAll("td")[2].innerText;
            const transaksiFaktur = parent.querySelectorAll("td")[0].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>${transaksiFaktur} - ${transaksiSupplier}</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                modal.addEventListener("show.bs.modal", function (event) {
                    // Button that triggered the modal
                    var button = event.relatedTarget;
                    // Extract info from data-* attributes
                    var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
                    // Update the modal's title
                    var modalTitle = modal.querySelector(".modal-title");
                    modalTitle.textContent = title;
                });
                getDetails(transaksiId);

                $("#kt_modal_mutation_details").modal("show");
                // Livewire.dispatch('editKandang', [transaksiId]);
            });
        });
    });

// Add click event listener to delete buttons
document
    .querySelectorAll('[data-kt-action="delete_row"]')
    .forEach(function (element) {
        element.addEventListener("click", function () {
            Swal.fire({
                text: "Are you sure you want to remove?",
                icon: "warning",
                buttonsStyling: false,
                showCancelButton: true,
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                customClass: {
                    confirmButton: "btn btn-danger",
                    cancelButton: "btn btn-secondary",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch("delete_feed", [
                        this.getAttribute("data-kt-feed-id"),
                    ]);
                }
            });
        });
    });

// Add click event listener to update buttons
document
    .querySelectorAll('[data-kt-action="update_row"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();
            // Select parent row
            const parent = e.target.closest("tr");

            // Get supplier ID
            const supplierId = event.currentTarget.getAttribute(
                "data-kt-supplier-id"
            );

            // Get subject name
            const supplierName = parent.querySelectorAll("td")[1].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>` + supplierName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                // showLoadingSpinner();
                // Livewire.on('update_supplier', function (event) {
                //     // $('#kt_modal_master_supplier').modal('show');
                //     // Assuming event.detail.supplierId contains the actual ID:
                //     this.set('supplier_id', event.detail.supplierId);
                // });

                console.log(supplierId);

                Livewire.dispatch("editSupplier", [supplierId]);
            });
        });
    });

// Listen for 'success' event emitted by Livewire
Livewire.on("success", (message) => {
    // Reload the suppliers-table datatable
    LaravelDataTables["suppliers-table"].ajax.reload();
});

// Listen for delete warning event
Livewire.on("show-delete-warning", (data) => {
    // console.log(data[0]);
    // console.log(data[0].message);

    Swal.fire({
        title: "Peringatan!",
        text: data[0].message,
        icon: "warning",
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus",
        cancelButtonText: "Batal",
        customClass: {
            confirmButton: "btn btn-danger",
            cancelButton: "btn btn-secondary",
        },
    }).then((result) => {
        if (result.isConfirmed) {
            Livewire.dispatch("confirmDeleteFeed", [data[0].feedId]);
        }
    });
});
