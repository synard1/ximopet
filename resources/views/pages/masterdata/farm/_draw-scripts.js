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
                // Livewire.dispatch('delete_farm', [this.getAttribute('data-kt-farm-id')]);
                deleteFarm(this.getAttribute('data-kt-farm-id'));
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

$(document).on('click', '.farm-detail', function(e) {
    e.preventDefault();
    var farmId = $(this).data('farm-id');
    var modal = $('#farmDetailsModal');
    
    $.ajax({
        url: '/farm/' + farmId + '/kandangs',
        type: 'GET',
        success: function(response) {
            var tableBody = modal.find('#kandangsTable tbody');
            tableBody.empty();
            
            $.each(response, function(index, kandang) {
                var formattedDate = kandang.kelompok_ternak && kandang.kelompok_ternak.start_date
                    ? moment(kandang.kelompok_ternak.start_date).format('DD-MM-YYYY')
                    : '-';
                
                tableBody.append(
                    '<tr>' +
                    '<td>' + kandang.kode + '</td>' +
                    '<td>' + kandang.nama + '</td>' +
                    '<td>' + kandang.kapasitas + '</td>' +
                    '<td>' + kandang.status + '</td>' +
                    '<td>' + formattedDate + '</td>' +
                    '<td>' + (kandang.kelompok_ternak ? kandang.kelompok_ternak.populasi_awal : '-') + '</td>' +
                    '</tr>'
                );
            });
            
            modal.modal('show');
        },
        error: function(xhr) {
            console.log('Error:', xhr);
        }
    });
});

// Listen for 'success' event emitted by Livewire
Livewire.on('success', (message) => {
    // Reload the farms-table datatable
    LaravelDataTables['farms-table'].ajax.reload();
});
