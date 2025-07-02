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
    <style>
        /* Responsive SweetAlert2 Modal */
        .swal-responsive-popup {
            max-width: 90vw !important;
            width: 600px !important;
            min-width: 500px !important;
            font-size: 15px !important;
            max-height: 90vh !important;
            overflow: hidden !important;
        }

        .swal-responsive-title {
            font-size: 1.5rem !important;
            padding: 0.75rem 0 !important;
            margin-bottom: 0.5rem !important;
        }

        .swal-responsive-content {
            padding: 0 !important;
            margin: 0 !important;
            max-height: calc(90vh - 180px) !important;
            overflow-y: auto !important;
        }

        .swal-responsive-actions {
            margin-top: 1.5rem !important;
            gap: 1rem !important;
            padding-top: 1rem !important;
            border-top: 1px solid #e9ecef !important;
        }

        /* Import preview content styling */
        .import-preview {
            padding: 0.5rem 0 !important;
        }

        .import-preview .row.g-2 {
            margin-bottom: 1rem !important;
        }

        .import-preview .text-center.p-2 {
            padding: 1rem !important;
            border-radius: 8px !important;
        }

        .import-preview .fw-bold {
            font-size: 1.5rem !important;
            margin-bottom: 0.25rem !important;
        }

        .import-preview small.text-muted {
            font-size: 0.875rem !important;
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .swal-responsive-popup {
                margin: 1rem !important;
                max-width: calc(100vw - 2rem) !important;
                width: auto !important;
                min-width: auto !important;
            }

            .swal-responsive-title {
                font-size: 1.25rem !important;
            }

            .import-preview .fw-bold {
                font-size: 1.25rem !important;
            }

            .swal-responsive-actions {
                gap: 0.5rem !important;
            }

            .swal-responsive-actions button {
                font-size: 0.875rem !important;
                padding: 0.5rem 1rem !important;
            }
        }

        @media (max-width: 576px) {
            .swal-responsive-popup {
                margin: 0.5rem !important;
                max-width: calc(100vw - 1rem) !important;
            }

            .import-preview .badge-sm {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
            }
        }

        /* Alert and badge styling */
        .alert-sm {
            padding: 0.75rem 1rem !important;
            font-size: 0.875rem !important;
            margin-bottom: 0 !important;
        }

        .badge-sm {
            font-size: 0.8rem !important;
            padding: 0.35rem 0.65rem !important;
            margin: 0.1rem !important;
        }

        /* Format badge styling */
        .import-preview .badge.fs-6 {
            font-size: 1rem !important;
            padding: 0.5rem 1rem !important;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <script>
        $(document).ready(function() {
            const table = $('#menus-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('administrator.menu.index') }}',
                rowReorder: false, // We'll use SortableJS
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
                ],
                drawCallback: function() {
                    // Aktifkan drag & drop setelah dataTable render
                    const tbody = document.querySelector('#menus-table tbody');
                    if (tbody && !tbody.classList.contains('sortable-enabled')) {
                        Sortable.create(tbody, {
                            animation: 150,
                            handle: 'td',
                            onEnd: function (evt) {
                                // Ambil urutan baru
                                let items = [];
                                $('#menus-table tbody tr').each(function(index) {
                                    const id = $(this).find('a[href*="/edit"]').attr('href').match(/menu\/(\d+)\/edit/)[1];
                                    items.push({ id: id, order_number: index + 1 });
                                });
                                // Kirim ke backend
                                $.ajax({
                                    url: '{{ route('administrator.menu.update-order') }}',
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}',
                                        items: items
                                    },
                                    success: function(res) {
                                        Swal.fire('Success', 'Menu order updated!', 'success');
                                        table.ajax.reload(null, false);
                                    },
                                    error: function() {
                                        Swal.fire('Error', 'Failed to update order!', 'error');
                                    }
                                });
                            }
                        });
                        tbody.classList.add('sortable-enabled');
                    }
                }
            });

            const importButton = $('#import-menu-button');
            const fileInput = $('#menu-file-input');
            const importForm = $('#import-menu-form');

            importButton.on('click', function() {
                fileInput.click();
            });

            fileInput.on('change', function() {
                console.log('File input changed, files:', this.files);
                if (this.files.length > 0) {
                    console.log('File selected:', this.files[0].name);
                    showImportPreview(this.files[0]);
                }
            });

            function showImportPreview(file) {
                console.log('showImportPreview called with file:', file.name);
                
                // Show loading
                Swal.fire({
                    title: 'Analyzing File',
                    text: 'Please wait while we analyze your menu configuration...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Create FormData for file upload
                const formData = new FormData();
                formData.append('menu_file', file);
                formData.append('_token', '{{ csrf_token() }}');

                console.log('Making AJAX request to:', '{{ route('administrator.menu.import-preview') }}');

                // Call preview endpoint
                $.ajax({
                    url: '{{ route('administrator.menu.import-preview') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Preview response:', response);
                        if (response && response.success) {
                            if (response.preview && response.validation) {
                                showPreviewModal(response.preview, response.validation, file);
                            } else {
                                console.error('Invalid response structure:', response);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Invalid response structure from server. Check console for details.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response?.error || 'Failed to analyze menu file.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Preview error:', xhr);
                        let errorMessage = 'Failed to analyze menu file.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }

            function showPreviewModal(preview, validation, file) {
                console.log('showPreviewModal called with:', { preview, validation });
                
                // Provide default values to prevent undefined errors
                const safePreview = {
                    format: preview?.format || 'unknown',
                    total_menus: preview?.total_menus || 0,
                    parent_menus: preview?.parent_menus || 0,
                    child_menus: preview?.child_menus || 0,
                    unique_roles: preview?.unique_roles || 0,
                    unique_permissions: preview?.unique_permissions || 0,
                    roles: preview?.roles || [],
                    permissions: preview?.permissions || []
                };

                const safeValidation = {
                    errors: validation?.errors || [],
                    warnings: validation?.warnings || []
                };

                let validationHtml = '';
                if (safeValidation.errors.length > 0) {
                    validationHtml = `
                        <div class="alert alert-danger">
                            <h6>Validation Errors:</h6>
                            <ul class="mb-0">
                                ${safeValidation.errors.map(error => `<li>${error}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                let warningsHtml = '';
                if (safeValidation.warnings.length > 0) {
                    warningsHtml = `
                        <div class="alert alert-warning">
                            <h6>Warnings:</h6>
                            <ul class="mb-0">
                                ${safeValidation.warnings.map(warning => `<li>${warning}</li>`).join('')}
                            </ul>
                        </div>
                    `;
                }

                const previewHtml = `
                    <div class="import-preview">
                        ${validationHtml}
                        ${warningsHtml}
                        
                        <!-- Summary Section -->
                        <div class="row g-3 mb-4">
                            <div class="col-4">
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="fw-bold text-primary">${safePreview.total_menus}</div>
                                    <small class="text-muted">Total Menus</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="fw-bold text-success">${safePreview.unique_roles}</div>
                                    <small class="text-muted">Roles</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="fw-bold text-info">${safePreview.unique_permissions}</div>
                                    <small class="text-muted">Permissions</small>
                                </div>
                            </div>
                        </div>

                        <!-- Format Detection -->
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <span class="badge badge-${safePreview.format === 'legacy' ? 'warning' : 'success'} fs-6">
                                ${safePreview.format.toUpperCase()} Format
                            </span>
                        </div>

                        <!-- Roles & Permissions -->
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted mb-2">ROLES FOUND:</h6>
                                    <div>
                                        ${safePreview.roles.length > 0 ? 
                                            safePreview.roles.slice(0, 5).map(role => `<span class="badge badge-primary badge-sm me-1 mb-1">${role}</span>`).join('') +
                                            (safePreview.roles.length > 5 ? `<span class="badge badge-secondary badge-sm">+${safePreview.roles.length - 5} more</span>` : '') :
                                            '<span class="text-muted">No roles found</span>'
                                        }
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <h6 class="fw-bold text-muted mb-2">PERMISSIONS FOUND:</h6>
                                    <div>
                                        ${safePreview.permissions.length > 0 ? 
                                            safePreview.permissions.slice(0, 5).map(permission => `<span class="badge badge-info badge-sm me-1 mb-1">${permission}</span>`).join('') +
                                            (safePreview.permissions.length > 5 ? `<span class="badge badge-secondary badge-sm">+${safePreview.permissions.length - 5} more</span>` : '') :
                                            '<span class="text-muted">No permissions found</span>'
                                        }
                                    </div>
                                </div>
                            </div>
                        </div>

                        ${safePreview.format === 'legacy' ? `
                            <div class="alert alert-warning alert-sm mt-3 py-2">
                                <i class="fa fa-info-circle me-1"></i>
                                <small><strong>Legacy Format:</strong> Integer IDs will be converted to UUIDs.</small>
                            </div>
                        ` : ''}
                    </div>
                `;

                const canImport = safeValidation.errors.length === 0;

                Swal.fire({
                    title: 'Import Preview',
                    html: previewHtml,
                    width: '600px',
                    padding: '1.5rem',
                    showCancelButton: true,
                    confirmButtonText: canImport ? 'Import Configuration' : 'Fix Errors First',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: canImport ? '#198754' : '#dc3545',
                    cancelButtonColor: '#6c757d',
                    buttonsStyling: true,
                    heightAuto: false,
                    scrollbarPadding: false,
                    customClass: {
                        popup: 'swal-responsive-popup',
                        title: 'swal-responsive-title',
                        htmlContainer: 'swal-responsive-content',
                        actions: 'swal-responsive-actions'
                    },
                    preConfirm: () => {
                        if (!canImport) {
                            Swal.showValidationMessage('Please fix validation errors before importing');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed && canImport) {
                        // Proceed with actual import
                        proceedWithImport(file);
                    }
                });
            }

            function proceedWithImport(file) {
                // Show import progress
                Swal.fire({
                    title: 'Importing Menu Configuration',
                    text: 'Please wait while we import your menu configuration...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Create FormData for actual import
                const formData = new FormData();
                formData.append('menu_file', file);
                formData.append('_token', '{{ csrf_token() }}');

                // Submit to actual import endpoint
                $.ajax({
                    url: '{{ route('administrator.menu.import') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Handle redirect response
                        window.location.reload();
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to import menu configuration.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            title: 'Import Failed!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }

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