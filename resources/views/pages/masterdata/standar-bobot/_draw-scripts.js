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
                    Livewire.dispatch("delete_standar_bobot", [
                        this.getAttribute("data-standarBobot-id"),
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

            // Get standar bobot ID
            const standarBobotId = this.getAttribute("data-standarBobot-id");

            // Get subject name
            const standarName = parent.querySelectorAll("td")[1].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>` + standarName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                console.log(standarBobotId);
                Livewire.dispatch("editStrainStandard", [standarBobotId]);
            });
        });
    });

// Add click event listener to view
document
    .querySelectorAll('[data-kt-action="view_row"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();
            // Select parent row
            const parent = e.target.closest("tr");

            // Get standar bobot ID
            const standarBobotId = this.getAttribute("data-standarBobot-id");

            // Get subject name
            const standarName = parent.querySelectorAll("td")[1].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>` + standarName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                // console.log('open modal');

                Livewire.dispatch("viewStandarBobot", [standarBobotId]);
                // $('#standarBobotDetailModal').modal('show'); // Show the modal
            });
        });
    });
