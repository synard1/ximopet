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
                Livewire.dispatch('delete_farm', [this.getAttribute('data-kt-farm-id')]);
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

        // Get farm ID
        const farmId = event.currentTarget.getAttribute('data-kt-farm-id');

        // Get subject name
        const farmName = parent.querySelectorAll('td')[1].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Membuka Data <b>`+ farmName +`</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            Livewire.dispatch('editFarm', [farmId]);
        });
        
    });
});

// Add click event listener to update buttons
document.querySelectorAll('[data-kt-action="view_detail_ternak"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        var modal = document.getElementById('kt_modal_ternak_details');

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
            getDetailsTernak(transaksiId);

            $('#kt_modal_ternak_details').modal('show');
            // Livewire.dispatch('editKandang', [transaksiId]);
        });
        
    });
});

document.querySelectorAll('[data-kt-action="view_detail_ternak"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        var ternakId = e.target.getAttribute('data-kt-ternak-id');
        
        // Show loading indication
        Swal.fire({
            text: "Loading ternak details...",
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        });

        fetch(`/ternak/${ternakId}/detail`, {
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
        .then(response => response.json())
        .then(data => {
            if (data.result && data.result.length > 0) {
                // Populate modal with data
                const modal = document.getElementById('kt_modal_ternak_details');
                const modalTitle = modal.querySelector('.modal-title');
                const tableBody = modal.querySelector('#detailTable tbody');

                // Clear existing table rows
                tableBody.innerHTML = '';

                // Set modal title
                // modalTitle.textContent = `Detail Ternak ID: ${ternakId}`;
                modalTitle.textContent = `Detail Ternak ID: ` + data.nama;

                // Populate table with data
                data.result.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.tanggal}</td>
                            <td>${item.ternak_mati || 0}</td>
                            <td>${item.ternak_afkir || 0}</td>
                            <td>${item.ternak_terjual || 0}</td>
                            <td>${item.pakan_nama}</td>
                            <td>${item.pakan_quantity}</td>
                            <td>${item.ovk_harian || 0}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });

                // Show the modal
                $('#kt_modal_ternak_details').modal('show');

                // Initialize or refresh DataTable
                if ($.fn.DataTable.isDataTable('#detailTable')) {
                    $('#detailTable').DataTable().destroy();
                }
                $('#detailTable').DataTable({
                    // pageLength: 10,
                    // lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    // order: [[0, 'desc']],
                    // columnDefs: [
                    //     { targets: 0, type: 'date' },
                    //     { targets: '_all', type: 'num' }
                    // ],
                    autoWidth: true,
                    responsive: false,
                    // scrollX: true,
                    dom: 'Bfrtip',
                    buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                });
            } else {
                Swal.fire({
                    text: "No data available for this ternak.",
                    icon: "info",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, got it!",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error fetching ternak details:', error);
            Swal.fire({
                text: "An error occurred while loading ternak details.",
                icon: "error",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
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

document.querySelectorAll('[data-kt-action="update_detail"]').forEach(function (element) {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        var modal = document.getElementById('kt_modal_ternak_detail_report');

        // Select parent row
        const parent = e.target.closest('tr');

        // Get ternak ID
        const ternakId = event.currentTarget.getAttribute('data-ternak-id');

        // Get ternak name
        const ternakName = parent.querySelectorAll('td')[0].innerText;

        // Show loading indication
        Swal.fire({
            html: `Loading data for <b>${ternakName}</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            // Fetch detail report data
            fetch(`/api/v1/ternak/${ternakId}/detail-report`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + window.AuthToken,
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
			})
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modal.addEventListener('show.bs.modal', function (event) {
                            // document.getElementById('ternak_id').value = ternakId;
                            // Update all inputs with id 'ternak_id'
                            document.querySelectorAll('#ternak_id').forEach(input => {
                                input.value = ternakId;
                            });
                            
                            // Populate form fields if bonus data exists
                            if (data.bonus) {
                                document.getElementById('jumlah').value = data.bonus.jumlah || '';
                                document.getElementById('tanggal').value = data.bonus.tanggal || '';
                                document.getElementById('keterangan').value = data.bonus.keterangan || '';
                            } else {
                                // Clear form fields if no bonus data
                                document.getElementById('jumlah').value = '';
                                document.getElementById('tanggal').value = '';
                                document.getElementById('keterangan').value = '';
                            }

                            // Populate form field if administrasi data exists
                            if (data.administrasi) {
                                document.getElementById('persetujuan_nama').value = data.administrasi.persetujuan_nama || '';
                                document.getElementById('persetujuan_jabatan').value = data.administrasi.persetujuan_jabatan || '';
                                document.getElementById('verifikator_nama').value = data.administrasi.verifikator_nama || '';
                                document.getElementById('verifikator_jabatan').value = data.administrasi.verifikator_jabatan || '';
                                document.getElementById('tanggal_laporan').value = data.administrasi.tanggal_laporan || '';
                            } else {
                                // Clear administrasi fields if no administrasi data
                                document.getElementById('persetujuan_nama').value = '';
                                document.getElementById('persetujuan_jabatan').value = '';
                                document.getElementById('verifikator_nama').value = '';
                                document.getElementById('verifikator_jabatan').value = '';
                                document.getElementById('tanggal_laporan').value = '';
                            }
                        });

                        $('#kt_modal_ternak_detail_report').modal('show');
                    } else {
                        throw new Error(data.message || 'Failed to retrieve bonus data');
                    }
                })
                .catch(error => {
                    console.error('Error fetching bonus data:', error);
                    Swal.fire({
                        text: "An error occurred while loading bonus data.",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                });
        });
    });
});

// Listen for 'success' event emitted by Livewire
Livewire.on('success', (message) => {
    // Reload the farms-table datatable
    LaravelDataTables['farms-table'].ajax.reload();
});
