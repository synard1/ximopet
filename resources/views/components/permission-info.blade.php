@props(['permissionInfo'])

{{-- Only render this component in local environment and when debug is true --}}
@if(config('app.debug') && app()->environment('local'))

<div class="position-fixed bottom-0 end-0 p-2" style="z-index: 11; max-width: 300px;">
    <div id="permissionToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header p-2 d-flex justify-content-between align-items-center">
            <strong class="me-auto">Permission Info</strong>
            <div class="d-flex align-items-center">
                {{-- Minimize button --}}
                <button type="button" class="btn-icon btn-sm minimize-permission-info me-2" aria-label="Minimize"
                    id="minimizeButton">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="toast-body p-2">
            <div class="mb-1">
                <strong>Route:</strong> {{ $permissionInfo['current_route'] }}
            </div>

            <div class="mb-1">
                <strong>Your Roles:</strong>
                <div style="max-height: 80px; overflow-y: auto;">
                    <ul class="mb-0 ps-3">
                        @forelse($permissionInfo['user_roles'] as $role)
                        <li>{{ $role['name'] }}</li>
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
                        @forelse($permissionInfo['user_permissions'] as $permission)
                        <li>{{ $permission['name'] }}</li>
                        @empty
                        <li>No direct permissions assigned</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Display relevant roles and if the user has them --}}
            <div class="mb-1">
                <strong>Relevant Roles Check:</strong>
                <div style="max-height: 80px; overflow-y: auto;">
                    <ul class="mb-0 ps-3">
                        @forelse($permissionInfo['relevant_roles'] as $roleName => $hasRole)
                        <li class="{{ $hasRole ? 'text-success' : 'text-danger' }}">
                            {{ $roleName }} {{ $hasRole ? '✔' : '❌' }}
                        </li>
                        @empty
                        <li>No relevant roles defined</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Display relevant permissions and if the user has them --}}
            <div class="mb-1">
                <strong>Relevant Permissions Check:</strong>
                <div style="max-height: 100px; overflow-y: auto;">
                    <ul class="mb-0 ps-3">
                        @forelse($permissionInfo['relevant_permissions'] as $permissionName => $hasPermission)
                        <li class="{{ $hasPermission ? 'text-success' : 'text-danger' }}">
                            {{ $permissionName }} {{ $hasPermission ? '✔' : '❌' }}
                        </li>
                        @empty
                        <li>No relevant permissions defined</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Display route-level required info (if any, for context) --}}
            @if(!empty($permissionInfo['route_required_roles']) ||
            !empty($permissionInfo['route_required_permissions']))
            <div class="mt-2 pt-2 border-top">
                <div class="mb-1">
                    <strong>Route Required Roles:</strong>
                    <ul class="mb-0 ps-3">
                        @forelse($permissionInfo['route_required_roles'] as $role)
                        <li>{{ $role }}</li>
                        @empty
                        <li>None</li>
                        @endforelse
                    </ul>
                </div>
                <div class="mb-1">
                    <strong>Route Required Permissions:</strong>
                    <ul class="mb-0 ps-3">
                        @forelse($permissionInfo['route_required_permissions'] as $permission)
                        <li>{{ $permission }}</li>
                        @empty
                        <li>None</li>
                        @endforelse
                    </ul>
                </div>
                <div class="mt-2">
                    <strong>Route Access:</strong>
                    @if($permissionInfo['has_route_required_roles'] &&
                    $permissionInfo['has_route_required_permissions'])
                    <span class="badge bg-success">Granted</span>
                    @else
                    <span class="badge bg-danger">Denied</span>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

{{-- The small button to restore the toast --}}
<button id="restorePermissionToast" class="btn btn-secondary btn-sm position-fixed bottom-0 end-0 me-2 mb-2"
    style="display: none; z-index: 11; padding: 5px 10px;">
    <i class="fas fa-info"></i> {{-- Using info icon --}}
</button>

{{-- Place script directly here within an IIFE to avoid variable conflicts --}}
<script>
    (function() {
        // Console log will only appear if the Blade condition is met
        console.log('Permission Info Script started (inside IIFE).');

        // Select elements by their IDs and classes
        const permissionToast = document.getElementById('permissionToast');
        const minimizeButton = document.getElementById('minimizeButton'); // Use ID
        const restoreButton = document.getElementById('restorePermissionToast');

        console.log('Permission Info Toast:', permissionToast);
        console.log('Minimize Button:', minimizeButton);
        console.log('Restore Button:', restoreButton);

        // Add click listener for minimize button
        if (minimizeButton) {
            console.log('Minimize button found. Adding click listener.');
            minimizeButton.addEventListener('click', function () {
                console.log('Minimize button clicked.');
                if (permissionToast) {
                    permissionToast.style.display = 'none'; // Hide the main toast
                    console.log('Permission toast hidden.');
                }
                if (restoreButton) {
                    restoreButton.style.display = 'block'; // Show the restore button
                     console.log('Restore button shown.');
                }
            });
        } else {
            console.error('Minimize button with ID #minimizeButton not found!');
        }

        // Add click listener for the separate restore button
        if (restoreButton) {
            console.log('Restore button found. Adding click listener.');
            restoreButton.addEventListener('click', function () {
                 console.log('Restore button clicked.');
                if (permissionToast) {
                    permissionToast.style.display = 'block'; // Show the main toast
                     console.log('Permission toast shown.');
                }
                if (restoreButton) {
                    restoreButton.style.display = 'none'; // Hide the restore button
                    console.log('Restore button hidden.');
                }
            });
        } else {
            console.error('Restore button with id #restorePermissionToast not found!');
        }

        console.log('Permission Info Script finished (inside IIFE).');
    })(); // End of IIFE
</script>

<style>
    .btn-icon {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
    }

    .btn-icon i {
        /* Style the icon if needed */
    }
</style>

@endif {{-- End of APP_DEBUG and local environment check --}}