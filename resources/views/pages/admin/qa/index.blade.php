<x-default-layout>
    <div class="card" id="qaList">
        <div class="card-header">
            <h3 class="card-title">QA Management</h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary ms-2" id="show-qa-form"
                    onclick="Livewire.dispatch('showQaForm')">
                    <i class="fa fa-plus"></i> Add New QA
                </button>

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

            Livewire.on('success', (message) => {
        Swal.fire({
            title: 'Success!',
            text: message,
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            // Destroy the existing DataTable instance
            $('#qas-table').DataTable().destroy();
            // Reinitialize the DataTable
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
                    { data: 'test_type', name: 'test_type' },
                    { data: 'priority', name: 'priority' },
                    { data: 'status', name: 'status' },
                    { data: 'tester_name', name: 'tester_name' },
                    { data: 'test_date', name: 'test_date' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                initComplete: function(settings, json) {
                    // Reinitialize event listeners
                    initializeEventListeners();
                }
            });
        });
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

    $(document).on('click', '[data-kt-action="delete_row"]', function () {
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
                Livewire.dispatch('delete', [this.getAttribute('data-kt-qa-id')]);
            }
        });
    });
    }
    });

    });

    

    function initializeEventListeners() {
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
                        Livewire.dispatch('delete', [this.getAttribute('data-kt-qa-id')]);
                    }
                });
            });
        });
    }

    // Initial call to set up event listeners
    initializeEventListeners();
    </script>
    @endpush

    {{-- @livewire('qa.restore-modal') --}}

</x-default-layout>