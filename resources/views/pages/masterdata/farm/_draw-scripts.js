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
            const farmId = this.getAttribute("data-kt-farm-id");
            const farmName =
                this.closest("tr").querySelectorAll("td")[1].innerText;

            Swal.fire({
                title: "Konfirmasi Hapus",
                text: `Apakah Anda yakin ingin menghapus farm "${farmName}"?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Hapus",
                cancelButtonText: "Batal",
                buttonsStyling: false,
                customClass: {
                    confirmButton: "btn btn-danger",
                    cancelButton: "btn btn-secondary",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch("delete_farm", { id: farmId });
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

            // Get farm ID
            const farmId = event.currentTarget.getAttribute("data-kt-farm-id");

            // Get subject name
            const farmName = parent.querySelectorAll("td")[1].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>` + farmName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                Livewire.dispatch("editFarm", [farmId]);
            });
        });
    });

$(document).on("click", ".farm-detail", function (e) {
    e.preventDefault();
    var farmId = $(this).data("farm-id");
    var modal = $("#farmDetailsModal");

    $.ajax({
        url: `/api/v1/farms/${farmId}/kandangs`,
        type: "POST",
        headers: {
            "Content-Type": "application/json",
            Authorization: "Bearer " + window.AuthToken,
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: JSON.stringify({
            farm_id: farmId,
        }),
        contentType: "application/json",
        success: function (response) {
            var tableBody = modal.find("#kandangsTable tbody");
            tableBody.empty();

            // console.log(response);

            if (response && response.length > 0) {
                $.each(response, function (index, kandang) {
                    var formattedDate =
                        kandang.livestock && kandang.livestock.start_date
                            ? moment(kandang.livestock.start_date).format(
                                  "DD-MM-YYYY"
                              )
                            : "-";

                    var row = `
                        <tr>
                            <td>${kandang.kode}</td>
                            <td>${kandang.nama}</td>
                            <td>${parseFloat(kandang.kapasitas).toLocaleString(
                                "id-ID"
                            )}</td>
                            <td>
                                <span class="badge badge-light-${
                                    kandang.status === "Digunakan"
                                        ? "success"
                                        : "warning"
                                }">
                                    ${kandang.status}
                                </span>
                            </td>
                            <td>${formattedDate}</td>
                            <td>${
                                kandang.livestock
                                    ? kandang.livestock.populasi_awal.toLocaleString(
                                          "id-ID"
                                      )
                                    : "-"
                            }</td>
                            <td>${
                                kandang.livestock
                                    ? parseFloat(
                                          kandang.livestock.berat_awal
                                      ).toLocaleString("id-ID") + " gr"
                                    : "-"
                            }</td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
            } else {
                tableBody.append(
                    '<tr><td colspan="8" class="text-center">Tidak ada data kandang</td></tr>'
                );
            }

            modal.modal("show");
        },
        error: function (xhr) {
            if (xhr.status === 401) {
                toastr.warning(
                    "Sesi Anda telah berakhir. Silakan login ulang."
                );
                // window.location.href = "/login";
            } else {
                toastr.error("Terjadi kesalahan saat mengambil data kandang");
            }
        },
    });
});
