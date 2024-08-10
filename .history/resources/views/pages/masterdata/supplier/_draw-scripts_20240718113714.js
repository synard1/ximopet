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
        const supplierName = parent.querySelectorAll('td')[2].innerText;

        // Simulate delete request -- for demo purpose only
        Swal.fire({
            html: `Load Data <b>`+ incidentTitle +`</b>`,
            icon: "info",
            buttonsStyling: false,
            showConfirmButton: false,
            timer: 2000
        }).then(function () {
            // Close kt_docs_card_incident_new
            $('#kt_docs_card_incident_new').collapse('show');
            // Show kt_docs_card_incident_list
            $('#kt_docs_card_incident_list').collapse('hide');

            $.ajax({
                url: '/apps/itsm/api/incidents',
                type: 'GET',
                data: {
                    id: incidentId,
                },
                success: function(response) {
                    $('#incident').val(response.data.title);
                    $('#description').val(response.data.description);

                    // Format date and set values for report_time and respond_date
                    $('#report_time').val(formatDateTime(response.data.report_time));
                    $('#response_time').val(formatDateTime(response.data.response_time));

                    // Auto-select the unit-dropdown based on response.data.origin_unit
                    selectClassification(response.data.category_id);
                    selectLocation(response.data.location);
                    selectSource(response.data.source);
                    selectReporter(response.data.reportedBy);
                    selectSeverity(response.data.severity);

                    response.data.category.forEach(function(category) {
                        $('input[name="category[]"][value="' + category + '"]').prop('checked', true);
                    });

                    // Find the input element by its id
                    var incidentInput = document.getElementById('incident');
                    incidentInput.setAttribute('readonly', true);

                    // Create a new hidden input element
                    var hiddenInput = document.createElement("input");
                    hiddenInput.type = "hidden";
                    hiddenInput.id = "incident_id";
                    hiddenInput.name = "incident_id";
                    hiddenInput.className = "form-control form-control-solid";
                    hiddenInput.value = response.data.id;
                    hiddenInput.readOnly = true;

                    // Find the form by its id and append the hidden input to it
                    document.getElementById("kt_new_incident_form").appendChild(hiddenInput);
                },
                error: function (error) {
                    let errorMessage = "Sorry, looks like there are some errors detected, please try again.";

                    if (error.responseJSON && error.responseJSON.message) {
                        errorMessage = error.responseJSON.message;
                    }

                    Swal.fire({
                        text: errorMessage,
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok, got it!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });

                    console.error('Error deleting incident:', error);
                }
            });
        });

        console.log(supplierId);

        

        
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
