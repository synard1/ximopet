<div>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9">
        @foreach($roles as $role)
        <!--begin::Col-->
        <div class="col-md-4">
            <!--begin::Card-->
            <div class="card card-flush h-md-100">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>{{ ucwords($role->name) }}</h2>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-1">
                    <!--begin::Users-->
                    <div class="fw-bold text-gray-600 mb-5">Total users with this role: {{ $role->users->count() }}
                    </div>
                    <!--end::Users-->
                    <!--begin::Permissions-->
                    <div class="d-flex flex-column text-gray-600">
                        @foreach($role->permissions->shuffle()->take(5) ?? [] as $permission)
                        <div class="d-flex align-items-center py-2">
                            <span class="bullet bg-primary me-3"></span>{{ ucfirst($permission->name) }}
                        </div>
                        @endforeach
                        @if($role->permissions->count() > 5)
                        <div class='d-flex align-items-center py-2'>
                            <span class='bullet bg-primary me-3'></span>
                            <em>and {{ $role->permissions->count()-5 }} more...</em>
                        </div>
                        @endif
                        @if($role->permissions->count() ===0)
                        <div class="d-flex align-items-center py-2">
                            <span class='bullet bg-primary me-3'></span>
                            <em>No permissions given...</em>
                        </div>
                        @endif
                    </div>
                    <!--end::Permissions-->
                </div>
                <!--end::Card body-->
                <!--begin::Card footer-->
                <div class="card-footer flex-wrap pt-0">
                    <a href="{{ route('user-management.roles.show', $role) }}"
                        class="btn btn-light btn-active-primary my-1 me-2">View Role</a>
                    <button type="button" class="btn btn-light btn-active-light-primary my-1"
                        data-role-id="{{ $role->name }}" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_update_role">Edit
                        Role</button>
                </div>
                <!--end::Card footer-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Col-->
        @endforeach

        @can('create roles')
        <!--begin::Add new card-->
        <div class="ol-md-4">
            <!--begin::Card-->
            <div class="card h-md-100">
                <!--begin::Card body-->
                <div class="card-body d-flex flex-center">
                    <!--begin::Button-->
                    <button type="button" class="btn btn-clear d-flex flex-column flex-center" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_update_role">
                        <!--begin::Illustration-->
                        <img src="{{ image('illustrations/sketchy-1/4.png') }}" alt="" class="mw-100 mh-150px mb-7" />
                        <!--end::Illustration-->
                        <!--begin::Label-->
                        <div class="fw-bold fs-3 text-gray-600 text-hover-primary">Add New Role</div>
                        <!--end::Label-->
                    </button>
                    <!--begin::Button-->
                </div>
                <!--begin::Card body-->
            </div>
            <!--begin::Card-->
        </div>
        <!--begin::Add new card-->
        @endcan
    </div>

    <div class="card mt-5">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Cari Role" />
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <button type="button" class="btn btn-light-primary me-3" wire:click="exportPermissions">
                        {!! getIcon('file-down', 'fs-2', '', 'i') !!}
                        Export
                    </button>
                    <button type="button" class="btn btn-light-primary me-3" wire:click="showRestoreModal">
                        {!! getIcon('file-up', 'fs-2', '', 'i') !!}
                        Restore Backup
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="showImport">
                        {!! getIcon('file-up', 'fs-2', '', 'i') !!}
                        Import
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-striped table-row-bordered gy-5 gs-7" id="roles-table">
                    <thead>
                        <tr class="fw-bold fs-6 text-gray-800">
                            <th>No</th>
                            <th>Nama Role</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('livewire.permission.import-modal')

    <!-- Restore Backup Modal -->
    <div class="modal fade" tabindex="-1" id="kt_modal_restore_backup">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Restore Backup</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        {!! getIcon('cross', 'fs-1') !!}
                    </div>
                </div>
                <div class="modal-body">
                    @if($restoreError)
                    <div class="alert alert-danger">
                        {{ $restoreError }}
                    </div>
                    @endif

                    @if($restoreStatus)
                    <div class="alert alert-success">
                        {{ $restoreStatus }}
                    </div>
                    @endif

                    <div class="mb-5">
                        <label class="form-label">Select Backup</label>
                        <select class="form-select" wire:model="selectedBackup">
                            <option value="">Select a backup...</option>
                            @foreach($backups as $backup)
                            <option value="{{ $backup['filename'] }}">
                                {{ $backup['created_at'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('selectedBackup') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="restoreBackup">Restore</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        var table = $('#roles-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("user-management.roles.data") }}',
                type: 'GET'
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'permissions', name: 'permissions'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            order: [[1, 'asc']],
            pageLength: 10
        });

        // Search functionality
        $('input[data-kt-user-table-filter="search"]').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Listen for refresh event
        Livewire.on('refreshDatatable', () => {
            table.ajax.reload();
        });

        // Handle restore modal
        Livewire.on('showRestoreModal', () => {
            $('#kt_modal_restore_backup').modal('show');
        });

        Livewire.on('hideRestoreModal', () => {
            $('#kt_modal_restore_backup').modal('hide');
        });

        // Handle import modal
        Livewire.on('showImportModal', () => {
            $('#kt_modal_import').modal('show');
        });

        Livewire.on('hideImportModal', () => {
            $('#kt_modal_import').modal('hide');
        });

        // Close modals when clicking outside
        $('.modal').on('click', function(e) {
            if ($(e.target).hasClass('modal')) {
                if ($(this).attr('id') === 'kt_modal_restore_backup') {
                    Livewire.dispatch('hideRestoreModal');
                } else if ($(this).attr('id') === 'kt_modal_import') {
                    Livewire.dispatch('hideImportModal');
                }
            }
        });
    });
</script>
@endpush