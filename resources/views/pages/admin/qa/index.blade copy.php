<x-default-layout>

    <div class="card" id="qaList">
        <div class="card-header">
            <h3 class="card-title">QA Management</h3>
            <div class="card-toolbar">
                {{-- <a href="{{ route('administrator.qa.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New QA
                </a> --}}
                <button type="button" class="btn btn-primary ms-2" id="show-qa-form"
                    onclick="Livewire.dispatch('showQaForm')">
                    <i class="fa fa-plus"></i> Add New QA
                </button>
                {{-- <a href="{{ route('administrator.qa.export') }}" class="btn btn-secondary ms-2">
                    <i class="fa fa-download"></i> Export Config
                </a>
                <button type="button" class="btn btn-secondary ms-2" id="import-qa-button">
                    <i class="fa fa-upload"></i> Import Config
                </button>
                <button type="button" class="btn btn-info ms-2" id="backup-qa-button">
                    <i class="fa fa-save"></i> Manual Backup
                </button>
                <button type="button" class="btn btn-warning ms-2" id="restore-qa-button">
                    <i class="fa fa-history"></i> Restore Backup
                </button> --}}

                <form action="{{ route('administrator.qa.import') }}" method="POST" enctype="multipart/form-data"
                    id="import-qa-form" style="display: none;">
                    @csrf
                    <input type="file" name="qa_file" id="qa-file-input" accept=".json">
                </form>
            </div>
        </div>
        <div class="card-body">
            {{-- <div class="alert alert-info d-flex align-items-center p-5 mb-10">
                <i class="ki-duotone ki-information-5 fs-2qx text-info me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-info">Auto-Backup Enabled</h4>
                    <span>Your QA configuration is automatically backed up to JSON files in the storage/app/backups
                        directory. The last 5 backups are kept.</span>
                </div>
            </div> --}}
            <div class="table-responsive">
                <table id="qas-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Feature Name</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Test Case</th>
                            <th>URL</th>
                            {{-- <th>Test Steps</th> --}}
                            {{-- <th>Expected Result</th> --}}
                            <th>Test Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Tester</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <livewire:qa-checklist-form />

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    @endpush

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <script>
        document.addEventListener('livewire:init', function () {
            window.addEventListener('hide-qaList', () => {
                $('#qaList').hide();
                $('#qaForm').show();
            });

            window.addEventListener('show-qaList', () => {
                $('#qaList').show();
                $('#qaForm').hide();
            });

            window.addEventListener('reload-qaList', () => {
                $('#qas-table').DataTable().ajax.reload();
            });
            
        });

    $(document).ready(function() {
    $('#qas-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route('administrator.qa.index') }}',
    columns: [
    { data: 'feature_name', name: 'feature_name' },
    { data: 'feature_category', name: 'feature_category' },
    { data: 'feature_subcategory', name: 'feature_subcategory' },
    { data: 'test_case', name: 'test_case' },
    { data: 'url', name: 'url' },
    // { data: 'test_steps', name: 'test_steps' },
    // { data: 'expected_result', name: 'expected_result' },
    { data: 'test_type', name: 'test_type' },
    { data: 'priority', name: 'priority' },
    { data: 'status', name: 'status' },
    { data: 'tester_name', name: 'tester_name' },
    { data: 'test_date', name: 'test_date' },
    { data: 'actions', name: 'actions', orderable: false, searchable: false }
    ],
    initComplete: function(settings, json) {
    // Custom JavaScript functionality can be added here
    console.log('DataTable initialized successfully');

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
                Livewire.dispatch('delete_qa', [this.getAttribute('data-kt-qa-id')]);
            }
        });
    });
});
    }
    });

    const importButton = $('#import-qa-button');
    const fileInput = $('#qa-file-input');
    const importForm = $('#import-qa-form');

    importButton.on('click', function() {
    fileInput.click();
    });

    fileInput.on('change', function() {
    if (this.files.length > 0) {
    importForm.submit();
    }
    });

    // Add confirmation for duplicate action
    $(document).on('submit', '.duplicate-form', function(e) {
    e.preventDefault();
    const form = $(this);
    Swal.fire({
    title: 'Duplicate QA Item',
    text: 'Are you sure you want to duplicate this QA item?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, duplicate it!',
    cancelButtonText: 'No, cancel',
    reverseButtons: true
    }).then((result) => {
    if (result.isConfirmed) {
    // Submit the form using AJAX
    $.ajax({
    url: form.attr('action'),
    type: 'POST',
    data: form.serialize(),
    success: function(response) {
    // Show success message
    Swal.fire({
    title: 'Success!',
    text: 'QA item duplicated successfully.',
    icon: 'success',
    confirmButtonText: 'OK'
    }).then(() => {
    // Reload the DataTable
    $('#qas-table').DataTable().ajax.reload();
    });
    },
    error: function(xhr) {
    // Show error message
    Swal.fire({
    title: 'Error!',
    text: 'Failed to duplicate QA item.',
    icon: 'error',
    confirmButtonText: 'OK'
    });
    }
    });
    }
    });
    });

    // Add confirmation for delete action
    $(document).on('submit', '.delete-form', function(e) {
    e.preventDefault();
    const form = $(this);
    Swal.fire({
    title: 'Delete QA Item',
    text: 'Are you sure you want to delete this QA item?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'No, cancel',
    reverseButtons: true
    }).then((result) => {
    if (result.isConfirmed) {
    // Submit the form using AJAX
    $.ajax({
    url: form.attr('action'),
    type: 'POST',
    data: form.serialize(),
    success: function(response) {
    // Show success message
    Swal.fire({
    title: 'Success!',
    text: 'QA item deleted successfully.',
    icon: 'success',
    confirmButtonText: 'OK'
    }).then(() => {
    // Reload the DataTable
    $('#qas-table').DataTable().ajax.reload();
    });
    },
    error: function(xhr) {
    // Show error message
    Swal.fire({
    title: 'Error!',
    text: 'Failed to delete QA item.',
    icon: 'error',
    confirmButtonText: 'OK'
    });
    }
    });
    }
    });
    });

    // Add manual backup functionality
    $('#backup-qa-button').on('click', function() {
    Swal.fire({
    title: 'Creating Backup',
    text: 'Please wait while we create a backup of your QA configuration...',
    allowOutsideClick: false,
    didOpen: () => {
    Swal.showLoading();
    }
    });

    // Trigger a dummy update to force backup
    $.ajax({
    url: '{{ route('administrator.qa.index') }}',
    type: 'GET',
    success: function() {
    Swal.fire({
    title: 'Success!',
    text: 'QA configuration has been backed up successfully.',
    icon: 'success',
    confirmButtonText: 'OK'
    });
    },
    error: function() {
    Swal.fire({
    title: 'Error!',
    text: 'Failed to create backup.',
    icon: 'error',
    confirmButtonText: 'OK'
    });
    }
    });
    });

    // Add restore button functionality
    $('#restore-qa-button').on('click', function() {
    Livewire.dispatch('openRestoreModal');
    });

    // Listen for success/error events
    Livewire.on('success', (message) => {
    Swal.fire({
    title: 'Success!',
    text: message,
    icon: 'success',
    confirmButtonText: 'OK'
    }).then(() => {
    $('#qas-table').DataTable().ajax.reload();
    });
    });

    Livewire.on('error', (message) => {
    Swal.fire({
    title: 'Error!',
    text: message,
    icon: 'error',
    confirmButtonText: 'OK'
    });
    });
    });
    </script>
    @endpush

    {{-- @livewire('qa.restore-modal') --}}

</x-default-layout>