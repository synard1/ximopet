<!-- resources/views/livewire/permission-info-monitor.blade.php -->
<div>
    <!-- resources/views/livewire/admin-monitoring/permission-info.blade.php -->
    @if(config('app.debug') && app()->environment('local'))
    <div class="position-fixed bottom-0 end-0 p-2" style="z-index: 11; max-width: 300px;">
        <div id="permissionToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header p-2 d-flex justify-content-between align-items-center">
                <strong class="me-auto">Permission Info</strong>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn-icon btn-sm minimize-permission-info me-2" aria-label="Minimize"
                        id="minimizeButton">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="toast-body p-2">
                <div class="mb-1">
                    <strong>Route:</strong> {{ $currentRoute }}
                </div>

                <div class="mb-1">
                    <strong>Your Roles:</strong>
                    <div style="max-height: 80px; overflow-y: auto;">
                        <ul class="mb-0 ps-3">
                            @forelse($userRoles as $role)
                            <li>{{ $role }}</li>
                            @empty
                            <li>No roles assigned</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="mb-1">
                    <strong>Your Permissions:</strong>
                    <div style="max-height: 100px; overflow-y: auto;">
                        <ul class="mb-0 ps-3">
                            @forelse($userPermissions as $permission)
                            <li>{{ $permission->name }}</li>
                            @empty
                            <li>No direct permissions assigned</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="mb-1">
                    <strong>Relevant Roles Check:</strong>
                    <div style="max-height: 80px; overflow-y: auto;">
                        <ul class="mb-0 ps-3">
                            @foreach($relevantRoles as $roleName => $hasRole)
                            <li class="{{ $hasRole ? 'text-success' : 'text-danger' }}">
                                {{ $roleName }} {{ $hasRole ? '✔' : '❌' }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="mb-1">
                    <strong>Relevant Permissions Check:</strong>
                    <div style="max-height: 100px; overflow-y: auto;">
                        <ul class="mb-0 ps-3">
                            @foreach($relevantPermissions as $permissionName => $hasPermission)
                            <li class="{{ $hasPermission ? 'text-success' : 'text-danger' }}">
                                {{ $permissionName }} {{ $hasPermission ? '✔' : '❌' }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button id="restorePermissionToast" class="btn btn-secondary btn-sm position-fixed bottom-0 end-0 me-2 mb-2"
        style="display: none; z-index: 11; padding: 5px 10px;">
        <i class="fas fa-info"></i>
    </button>

    <script>
        (function() {
        const permissionToast = document.getElementById('permissionToast');
        const minimizeButton = document.getElementById('minimizeButton');
        const restoreButton = document.getElementById('restorePermissionToast');

        if (minimizeButton) {
            minimizeButton.addEventListener('click', function () {
                permissionToast.style.display = 'none';
                restoreButton.style.display = 'block';
            });
        }

        if (restoreButton) {
            restoreButton.addEventListener('click', function () {
                permissionToast.style.display = 'block';
                restoreButton.style.display = 'none';
            });
        }
    })();
    </script>

    <style>
        .btn-icon {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
    </style>
    @endif
</div>