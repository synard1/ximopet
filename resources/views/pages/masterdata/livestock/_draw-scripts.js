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
                    Livewire.dispatch("delete_transaksi_doc", [
                        this.getAttribute("data-ternak-id"),
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

            // Get farm ID
            const ternakId = event.currentTarget.getAttribute("data-ternak-id");

            // Get subject name
            const ternakName = parent.querySelectorAll("td")[0].innerText;

            // Simulate delete request -- for demo purpose only
            Swal.fire({
                html: `Membuka Data <b>` + ternakName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                console.log(ternakId);
                Livewire.dispatch("editDoc", [ternakId]);
                // Show modal
                $("#kt_modal_new_doc").modal("show");
            });
        });
    });

// Event handler for livestock settings button
document
    .querySelectorAll('[data-kt-action="update_setting"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();

            // Select parent row
            const parent = e.target.closest("tr");

            // Get livestock ID
            const livestockId =
                e.currentTarget.getAttribute("data-livestock-id");

            if (!livestockId) {
                console.error("Livestock ID not found");
                return;
            }

            // Get subject name
            const livestockName = parent.querySelectorAll("td")[0].innerText;

            // Show loading message
            Swal.fire({
                html: `Membuka Data <b>` + livestockName + `</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                // Dispatch Livewire event with parameters
                Livewire.dispatch("setLivestockIdSetting", [
                    livestockId,
                    livestockName,
                ]);
            });
        });
    });

const recordsContainer = document.getElementById("livewireRecordsContainer");
const workerContainer = document.getElementById("assignWorkerContainer");
const tableContainer = document.getElementById("ternaksTables");

document
    .querySelectorAll("[data-kt-action='update_records']")
    .forEach((item) => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            const ternakId = this.getAttribute("data-ternak-id");

            // Use Livewire dispatch with proper payload format
            Livewire.dispatch("setRecords", [ternakId]);
            // console.log("records click");

            // Toggle visibility
            // tableContainer.style.display = "none";
            // recordsContainer.style.display = "block";

            // Smooth scroll
            // recordsContainer.scrollIntoView({ behavior: "smooth" });
        });
    });

document
    .querySelectorAll("[data-kt-action='assign_worker']")
    .forEach((item) => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            const livestockId = this.getAttribute("data-livestock-id");

            // Dispatch Livewire event with proper payload format
            Livewire.dispatch("setLivestockId", [livestockId]);

            // Toggle visibility
            // const tableContainer = document.querySelector("#ternaksTables");
            // const workerContainer = document.querySelector(
            //     "#assignWorkerContainer"
            // );

            // if (tableContainer && workerContainer) {
            //     tableContainer.style.display = "none";
            //     workerContainer.style.display = "block";
            // }
        });
    });

document
    .querySelectorAll("[data-kt-action='fifo_depletion']")
    .forEach((item) => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            const livestockId = this.getAttribute("data-livestock-id");

            // Dispatch Livewire event with proper payload format
            Livewire.dispatch("show-fifo-depletion", [livestockId]); // TODO: change to manual depletion
            $("#kt_modal_fifo_depletion").modal("show");
        });
    });

document
    .querySelectorAll("[data-kt-action='manual_depletion']")
    .forEach((item) => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            const livestockId = this.getAttribute("data-livestock-id");

            // Dispatch Livewire event with proper payload format
            Livewire.dispatch("show-manual-mutation", [livestockId]); // TODO: change to manual depletion
            $("#kt_modal_manual_batch_depletion").modal("show");
        });
    });

document
    .querySelectorAll("[data-kt-action='manual_mutation']")
    .forEach((item) => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            const livestockId = this.getAttribute("data-livestock-id");

            // Dispatch Livewire event with proper payload format
            Livewire.dispatch("show-manual-mutation", [livestockId]); // TODO: change to manual mutation
            $("#kt_modal_manual_livestock_mutation").modal("show");
        });
    });

document
    .querySelectorAll("[data-kt-action='fifo_mutation']")
    .forEach((item) => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            const livestockId = this.getAttribute("data-livestock-id");

            // Dispatch Livewire event with proper payload format
            Livewire.dispatch("show-fifo-simple-modal", [livestockId]);
            // Remove Bootstrap modal call since we're using Livewire component
            // $("#fifoSimpleModal").modal("show");
        });
    });

document.querySelectorAll("[data-kt-action='manual_usage']").forEach((item) => {
    item.addEventListener("click", function (e) {
        e.preventDefault();
        const livestockId = this.getAttribute("data-livestock-id");

        console.log("🔥 Manual usage button clicked", { livestockId });

        // Use the most reliable dispatch method
        try {
            // Dispatch with array parameters like in manual depletion
            Livewire.dispatch("show-manual-feed-usage", [livestockId]);
            console.log("🔥 Dispatched show-manual-feed-usage event");
        } catch (error) {
            console.error("🔥 Error dispatching event:", error);
        }

        // Show modal via Bootstrap
        $("#manual-feed-usage-modal").modal("show");
    });
});

// Tangkap semua elemen dengan class .closeRecordsBtn
document.querySelectorAll(".closeRecordsBtn").forEach(function (btn) {
    btn.addEventListener("click", function () {
        recordsContainer.style.display = "none";
        workerContainer.style.display = "none";
        tableContainer.style.display = "block";

        if (LaravelDataTables && LaravelDataTables["ternaks-table"]) {
            LaravelDataTables["ternaks-table"].ajax.reload();
        }

        console.log("kembali");
    });
});

// Add click event listener to update buttons
// document
//     .querySelectorAll('[data-kt-action="view_detail_livestock"]')
//     .forEach(function (element) {
//         element.addEventListener("click", function (e) {
//             e.preventDefault();
//             var modal = document.getElementById("kt_modal_ternak_details");
//             // Select parent row
//             const parent = e.target.closest("tr");

//             // Get transaksi ID
//             const transaksiId = event.currentTarget.getAttribute(
//                 "data-kt-transaksi-id"
//             );

//             // Get suppliers name
//             const transaksiSupplier =
//                 parent.querySelectorAll("td")[2].innerText;
//             const transaksiFaktur = parent.querySelectorAll("td")[0].innerText;

//             // Simulate delete request -- for demo purpose only
//             Swal.fire({
//                 html: `Membuka Data <b>${transaksiFaktur} - ${transaksiSupplier}</b>`,
//                 icon: "info",
//                 buttonsStyling: false,
//                 showConfirmButton: false,
//                 timer: 2000,
//             }).then(function () {
//                 modal.addEventListener("show.bs.modal", function (event) {
//                     // Button that triggered the modal
//                     var button = event.relatedTarget;
//                     // Extract info from data-* attributes
//                     var title = `${transaksiFaktur} - ${transaksiSupplier} Detail Data`;
//                     // Update the modal's title
//                     var modalTitle = modal.querySelector(".modal-title");
//                     modalTitle.textContent = title;
//                 });
//                 getDetailsTernak(transaksiId);

//                 $("#kt_modal_ternak_details").modal("show");
//                 // Livewire.dispatch('editKandang', [transaksiId]);
//             });
//         });
//     });

document
    .querySelectorAll('[data-kt-action="view_detail_livestock"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();
            var ternakId = e.target.getAttribute("data-kt-livestock-id");

            // Show loading indication
            Swal.fire({
                text: "Loading livestock details...",
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            });

            fetch(`/livestock/${ternakId}/detail`, {
                // method: 'POST',
                // headers: {
                //     'Content-Type': 'application/json',
                //     'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                //     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                // },
                // body: JSON.stringify({
                //     type: 'LIST',
                //     status: 'Aktif',
                //     roles: 'Supervisor'
                // })
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.result && data.result.length > 0) {
                        // Populate modal with data
                        const modal = document.getElementById(
                            "kt_modal_ternak_details"
                        );
                        const modalTitle = modal.querySelector(".modal-title");
                        const tableBody =
                            modal.querySelector("#detailTable tbody");

                        // Clear existing table rows
                        tableBody.innerHTML = "";

                        // Set modal title
                        // modalTitle.textContent = `Detail Ternak ID: ${ternakId}`;
                        modalTitle.textContent =
                            `Detail Ternak ID: ` + data.nama;

                        // Populate table with data
                        data.result.forEach((item) => {
                            const row = `
                        <tr>
                            <td>${item.tanggal}</td>
                            <td>${item.mati || 0}</td>
                            <td>${item.afkir || 0}</td>
                            <td>${item.ternak_terjual || 0}</td>
                            <td>${item.pakan_jenis}</td>
                            <td>${item.pakan_harian}</td>
                            <td>${item.ovk_harian || 0}</td>
                        </tr>
                    `;
                            tableBody.insertAdjacentHTML("beforeend", row);
                        });

                        // Initialize or refresh DataTable
                        if ($.fn.DataTable.isDataTable("#detailTable")) {
                            $("#detailTable").DataTable().destroy();
                        }

                        // Show the modal
                        $("#kt_modal_ternak_details").modal("show");

                        $("#detailTable").DataTable({
                            autoWidth: true,
                            responsive: false,
                            // scrollX: true,
                            dom: "Bfrtip",
                            buttons: ["copy", "csv", "excel", "pdf", "print"],
                        });
                    } else {
                        Swal.fire({
                            text: "No data available for this ternak.",
                            icon: "info",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn btn-primary",
                            },
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error fetching ternak details:", error);
                    Swal.fire({
                        text: "An error occurred while loading ternak details.",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary",
                        },
                    });
                });

            // Fetch ternak details
            // fetch(`/ternak/${ternakId}/detail`)
            //     .then(response => response.text())
            //     .then(html => {
            //         let table;

            //         // Trigger DataTable initialization when modal is opened
            //         $('#kt_modal_ternak_details').on('shown.bs.modal', function () {
            //             if (!$.fn.DataTable.isDataTable('#detailTable')) {
            //                 table = $('#exampleTable').DataTable({
            //                     processing: true,
            //                     serverSide: true,
            //                     ajax: "{{ route('users.get') }}",
            //                     columns: [
            //                         { data: 'id', name: 'id' },
            //                         { data: 'name', name: 'name' },
            //                         { data: 'email', name: 'email' },
            //                         { data: 'created_at', name: 'created_at' }
            //                     ],
            //                 });
            //             } else {
            //                 table.ajax.reload(); // Reload data if already initialized
            //             }
            //         });

            //         // document.getElementById('ternak_details_content').innerHTML = html;
            //         $('#kt_modal_ternak_details').modal('show');
            //     })
            //     .catch(error => {
            //         console.error('Error:', error);
            //         Swal.fire({
            //             text: "An error occurred while loading ternak details.",
            //             icon: "error",
            //             buttonsStyling: false,
            //             confirmButtonText: "Ok, got it!",
            //             customClass: {
            //                 confirmButton: "btn btn-primary"
            //             }
            //         });
            //     });
        });
    });

$(document).on("click", ".delete-btn", function () {
    const id = $(this).data("id");
    const tanggal = $(this).data("tanggal");

    // Validasi ID dan Tanggal
    if (!id || !tanggal) {
        alert("Data tidak valid. ID atau tanggal tidak ditemukan.");
        return;
    }

    if (confirm(`Yakin ingin menghapus data tanggal ${tanggal}?`)) {
        $.ajax({
            url: `/recording/delete/${id}`,
            type: "DELETE",
            data: {
                _token: "{{ csrf_token() }}",
                tanggal: tanggal, // kirim juga tanggal jika perlu di-backend
            },
            success: function (response) {
                alert(response.message);
                location.reload(); // atau panggil data ulang tanpa reload
            },
            error: function (xhr) {
                console.error(xhr);
                alert("Terjadi kesalahan saat menghapus data.");
            },
        });
    }
});

document
    .querySelectorAll('[data-kt-action="update_detail"]')
    .forEach(function (element) {
        element.addEventListener("click", function (e) {
            e.preventDefault();
            var modal = document.getElementById(
                "kt_modal_ternak_detail_report"
            );

            // Select parent row
            const parent = e.target.closest("tr");

            // Get ternak ID
            const ternakId = event.currentTarget.getAttribute("data-ternak-id");

            // Get ternak name
            const ternakName = parent.querySelectorAll("td")[0].innerText;

            // Show loading indication
            Swal.fire({
                html: `Loading data for <b>${ternakName}</b>`,
                icon: "info",
                buttonsStyling: false,
                showConfirmButton: false,
                timer: 2000,
            }).then(function () {
                // Fetch detail report data
                fetch(`/api/v1/ternak/${ternakId}/detail-report`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: "Bearer " + window.AuthToken,
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                            "content"
                        ),
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            modal.addEventListener(
                                "show.bs.modal",
                                function (event) {
                                    // document.getElementById('ternak_id').value = ternakId;
                                    // Update all inputs with id 'ternak_id'
                                    document
                                        .querySelectorAll("#ternak_id")
                                        .forEach((input) => {
                                            input.value = ternakId;
                                        });

                                    // Populate form fields if bonus data exists
                                    if (data.bonus) {
                                        document.getElementById(
                                            "jumlah"
                                        ).value = data.bonus.jumlah || "";
                                        document.getElementById(
                                            "tanggal"
                                        ).value = data.bonus.tanggal || "";
                                        document.getElementById(
                                            "keterangan"
                                        ).value = data.bonus.keterangan || "";
                                    } else {
                                        // Clear form fields if no bonus data
                                        document.getElementById(
                                            "jumlah"
                                        ).value = "";
                                        document.getElementById(
                                            "tanggal"
                                        ).value = "";
                                        document.getElementById(
                                            "keterangan"
                                        ).value = "";
                                    }

                                    // Populate form field if administrasi data exists
                                    if (data.administrasi) {
                                        document.getElementById(
                                            "persetujuan_nama"
                                        ).value =
                                            data.administrasi
                                                .persetujuan_nama || "";
                                        document.getElementById(
                                            "persetujuan_jabatan"
                                        ).value =
                                            data.administrasi
                                                .persetujuan_jabatan || "";
                                        document.getElementById(
                                            "verifikator_nama"
                                        ).value =
                                            data.administrasi
                                                .verifikator_nama || "";
                                        document.getElementById(
                                            "verifikator_jabatan"
                                        ).value =
                                            data.administrasi
                                                .verifikator_jabatan || "";
                                        document.getElementById(
                                            "tanggal_laporan"
                                        ).value =
                                            data.administrasi.tanggal_laporan ||
                                            "";
                                    } else {
                                        // Clear administrasi fields if no administrasi data
                                        document.getElementById(
                                            "persetujuan_nama"
                                        ).value = "";
                                        document.getElementById(
                                            "persetujuan_jabatan"
                                        ).value = "";
                                        document.getElementById(
                                            "verifikator_nama"
                                        ).value = "";
                                        document.getElementById(
                                            "verifikator_jabatan"
                                        ).value = "";
                                        document.getElementById(
                                            "tanggal_laporan"
                                        ).value = "";
                                    }
                                }
                            );

                            $("#kt_modal_ternak_detail_report").modal("show");
                        } else {
                            throw new Error(
                                data.message || "Failed to retrieve bonus data"
                            );
                        }
                    })
                    .catch((error) => {
                        console.error("Error fetching bonus data:", error);
                        Swal.fire({
                            text: "An error occurred while loading bonus data.",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn btn-primary",
                            },
                        });
                    });
            });
        });
    });

// Listen for 'success' event emitted by Livewire
// Livewire.on('success', (message) => {
//     // Reload the farms-table datatable
//     LaravelDataTables['farms-table'].ajax.reload();
// });
