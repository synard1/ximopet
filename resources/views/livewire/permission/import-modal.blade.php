<div>
    <div class="modal fade" tabindex="-1" id="kt_modal_import" wire:ignore.self>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Import Roles & Permissions</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        {!! getIcon('cross', 'fs-1') !!}
                    </div>
                </div>

                <div class="modal-body">
                    @if($importErrors)
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($importErrors as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if($importStatus)
                    <div class="alert alert-info">
                        {{ $importStatus }}
                    </div>
                    @endif

                    <div class="mb-5">
                        <label class="form-label">Import Type</label>
                        <div class="d-flex gap-5">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" wire:model.live="importType" value="file"
                                    id="import_type_file" />
                                <label class="form-check-label" for="import_type_file">
                                    Upload File
                                </label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" wire:model.live="importType" value="backup"
                                    id="import_type_backup" />
                                <label class="form-check-label" for="import_type_backup">
                                    Use Backup
                                </label>
                            </div>
                        </div>
                    </div>

                    @if($importType === 'file')
                    <div class="mb-5">
                        <label class="form-label">Select JSON File</label>
                        <input type="file" class="form-control" wire:model="file" accept=".json" />
                        @error('file') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    @else
                    <div class="mb-5">
                        <label class="form-label">Select Backup</label>
                        <select class="form-select" wire:model.live="selectedBackupFile">
                            <option value="">Select a backup...</option>
                            @foreach($backups as $backup)
                            <option value="{{ $backup['filename'] }}">
                                {{ $backup['created_at'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('selectedBackupFile') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    @if($showComparison && $backupComparison)
                    <div class="mb-5">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Changes Comparison</h3>
                            </div>
                            <div class="card-body">
                                <!-- Roles Changes -->
                                @if(count($backupComparison['roles']['added']) > 0 ||
                                count($backupComparison['roles']['removed']) > 0 ||
                                count($backupComparison['roles']['modified']) > 0)
                                <div class="mb-5">
                                    <h4 class="mb-3">Roles Changes</h4>

                                    @if(count($backupComparison['roles']['added']) > 0)
                                    <div class="mb-3">
                                        <h5 class="text-success">New Roles</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Role Name</th>
                                                        <th>Permissions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($backupComparison['roles']['added'] as $role)
                                                    <tr>
                                                        <td>{{ $role['name'] }}</td>
                                                        <td>
                                                            @foreach($role['permissions'] as $permission)
                                                            <span class="badge badge-light-primary">{{
                                                                $permission['name'] }}</span>
                                                            @endforeach
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif

                                    @if(count($backupComparison['roles']['removed']) > 0)
                                    <div class="mb-3">
                                        <h5 class="text-danger">Removed Roles</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Role Name</th>
                                                        <th>Current Permissions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($backupComparison['roles']['removed'] as $role)
                                                    <tr>
                                                        <td>{{ $role['name'] }}</td>
                                                        <td>
                                                            @foreach($role['permissions'] as $permission)
                                                            <span class="badge badge-light-primary">{{ $permission
                                                                }}</span>
                                                            @endforeach
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif

                                    @if(count($backupComparison['roles']['modified']) > 0)
                                    <div class="mb-3">
                                        <h5 class="text-warning">Modified Roles</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Role Name</th>
                                                        <th>Current Permissions</th>
                                                        <th>Backup Permissions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($backupComparison['roles']['modified'] as $role)
                                                    <tr>
                                                        <td>{{ $role['name'] }}</td>
                                                        <td>
                                                            @foreach($role['existing_permissions'] as $permission)
                                                            <span class="badge badge-light-primary">{{ $permission
                                                                }}</span>
                                                            @endforeach
                                                        </td>
                                                        <td>
                                                            @foreach($role['backup_permissions'] as $permission)
                                                            <span class="badge badge-light-primary">{{ $permission
                                                                }}</span>
                                                            @endforeach
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <!-- Permissions Changes -->
                                @if(count($backupComparison['permissions']['added']) > 0 ||
                                count($backupComparison['permissions']['removed']) > 0)
                                <div class="mb-5">
                                    <h4 class="mb-3">Permissions Changes</h4>

                                    @if(count($backupComparison['permissions']['added']) > 0)
                                    <div class="mb-3">
                                        <h5 class="text-success">New Permissions</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Permission Name</th>
                                                        <th>Guard Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($backupComparison['permissions']['added'] as $permission)
                                                    <tr>
                                                        <td>{{ $permission['name'] }}</td>
                                                        <td>{{ $permission['guard_name'] }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif

                                    @if(count($backupComparison['permissions']['removed']) > 0)
                                    <div class="mb-3">
                                        <h5 class="text-danger">Removed Permissions</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Permission Name</th>
                                                        <th>Guard Name</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($backupComparison['permissions']['removed'] as $permission)
                                                    <tr>
                                                        <td>{{ $permission['name'] }}</td>
                                                        <td>{{ $permission['guard_name'] }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif

                    @if($importProgress > 0)
                    <div class="mb-5">
                        <label class="form-label">Progress</label>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: {{ $importProgress }}%"
                                aria-valuenow="{{ $importProgress }}" aria-valuemin="0" aria-valuemax="100">
                                {{ round($importProgress) }}%
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="importPermissions" @if($importProgress> 0
                        && $importProgress < 100) disabled @endif>
                            Import
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($showImportModal)
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        const modal = document.getElementById('kt_modal_import');
        const modalInstance = new bootstrap.Modal(modal);

        Livewire.on('showImportModal', () => {
            modalInstance.show();
        });

        Livewire.on('hideImportModal', () => {
            modalInstance.hide();
        });

        Livewire.on('closeImportModal', () => {
            setTimeout(() => {
                modalInstance.hide();
            }, 2000);
        });

        // Handle modal hidden event
        modal.addEventListener('hidden.bs.modal', () => {
            @this.closeImportModal();
        });

        // Prevent modal from closing when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                e.stopPropagation();
            }
        });
    });
</script>
@endpush