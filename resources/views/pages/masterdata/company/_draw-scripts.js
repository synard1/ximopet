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
document
    .querySelectorAll('[data-kt-action="delete_row"]')
    .forEach(function (element) {
        element.addEventListener("click", function () {
            Swal.fire({
                text: "Are you sure you want to remove this company?",
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
                    Livewire.dispatch("deleteCompany", [
                        this.getAttribute("data-kt-company-id"),
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

            // Get company ID
            const companyId =
                event.currentTarget.getAttribute("data-kt-company-id");

            // Get subject name
            const companyName = parent.querySelectorAll("td")[1].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>` + companyName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                console.log("companyId", companyId);
                Livewire.dispatch("editCompany", [companyId]);
                const cardList = document.getElementById(`companyTableCard`);
                cardList.style.display = "none";
                const cardForm = document.getElementById(`companyFormCard`);
                cardForm.style.display = "block";
            });
        });
    });

// Listen for 'success' event emitted by Livewire
Livewire.on("success", (message) => {
    // Reload the customers-table datatable
    LaravelDataTables["companies-table"].ajax.reload();
});

// user mapping
document
    .querySelectorAll('[data-kt-action="user_mapping"]')
    .forEach(function (element) {
        element.addEventListener("click", function () {
            console.log(
                "user mapping",
                this.getAttribute("data-kt-company-id")
            );
            Livewire.dispatch("createMappingWithId", [
                this.getAttribute("data-kt-company-id"),
            ]);
        });
    });
