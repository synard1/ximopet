<x-default-layout>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Menu Management</h3>
            <div class="card-toolbar">
                <a href="{{ route('administrator.menu.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Menu
                </a>
                <a href="{{ route('administrator.menu.export') }}" class="btn btn-secondary ms-2">
                    <i class="fa fa-download"></i> Export Config
                </a>
                <button type="button" class="btn btn-secondary ms-2" id="import-menu-button">
                    <i class="fa fa-upload"></i> Import Config
                </button>
                <button type="button" class="btn btn-info ms-2" id="backup-menu-button">
                    <i class="fa fa-save"></i> Manual Backup
                </button>
                <button type="button" class="btn btn-warning ms-2" id="restore-menu-button">
                    <i class="fa fa-history"></i> Restore Backup
                </button>

                <form action="{{ route('administrator.menu.import') }}" method="POST" enctype="multipart/form-data"
                    id="import-menu-form" style="display: none;">
                    @csrf
                    <input type="file" name="menu_file" id="menu-file-input" accept=".json">
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info d-flex align-items-center p-5 mb-10">
                <i class="ki-duotone ki-information-5 fs-2qx text-info me-4">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-info">Auto-Backup Enabled</h4>
                    <span>Your menu configuration is automatically backed up to JSON files in the storage/app/backups
                        directory. The last 5 backups are kept.</span>
                </div>
            </div>
            <div class="table-responsive">
                <table id="menus-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Label</th>
                            <th>Route</th>
                            <th>Location</th>
                            <th>Order</th>
                            <th>Roles</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
        $(document).ready(function() {
            $('#menus-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('administrator.menu.index') }}',
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'label', name: 'label' },
                    { data: 'route', name: 'route' },
                    { data: 'location', name: 'location' },
                    { data: 'order_number', name: 'order_number' },
                    { 
                        data: 'roles',
                        name: 'roles.name',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            let roleBadges = '';
                            // The backend is already sending HTML string, just return it
                            return data; 
                        }
                    },
                    { 
                        data: 'permissions',
                        name: 'permissions.name',
                        orderable: false,
                        searchable: false,
                         render: function(data, type, row) {
                            let permissionBadges = '';
                            // The backend is already sending HTML string, just return it
                            return data;
                        }
                    },
                    { 
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            let actions = `
                                <div class="d-flex justify-content-end flex-shrink-0">
                                    <a href="/administrator/menu/${row.id}/edit" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                                        <i class="ki-duotone ki-pencil fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </a>
                                    <form action="/administrator/menu/${row.id}/duplicate" method="POST" class="d-inline duplicate-form">
                                        @csrf
                                        <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="Duplicate">
                                            <i class="ki-duotone ki-copy fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </button>
                                    </form>
                                    <form action="/administrator/menu/${row.id}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                                            <i class="ki-duotone ki-trash fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                                <span class="path5"></span>
                                            </i>
                                        </button>
                                    </form>
                                </div>
                            `;
                            return actions;
                        }
                    }
                ]
            });

            const importButton = $('#import-menu-button');
            const fileInput = $('#menu-file-input');
            const importForm = $('#import-menu-form');

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
                    title: 'Duplicate Menu Item',
                    text: 'Are you sure you want to duplicate this menu item?',
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
                                    text: 'Menu item duplicated successfully.',
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Reload the DataTable
                                    $('#menus-table').DataTable().ajax.reload();
                                });
                            },
                            error: function(xhr) {
                                // Show error message
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to duplicate menu item.',
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
                    title: 'Delete Menu Item',
                    text: 'Are you sure you want to delete this menu item?',
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
                                    text: 'Menu item deleted successfully.',
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Reload the DataTable
                                    $('#menus-table').DataTable().ajax.reload();
                                });
                            },
                            error: function(xhr) {
                                // Show error message
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to delete menu item.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });

            // Add manual backup functionality
            $('#backup-menu-button').on('click', function() {
                Swal.fire({
                    title: 'Creating Backup',
                    text: 'Please wait while we create a backup of your menu configuration...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Trigger a dummy update to force backup
                $.ajax({
                    url: '{{ route('administrator.menu.index') }}',
                    type: 'GET',
                    success: function() {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Menu configuration has been backed up successfully.',
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
            $('#restore-menu-button').on('click', function() {
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
                    $('#menus-table').DataTable().ajax.reload();
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

    @livewire('menu.restore-modal')

</x-default-layout>