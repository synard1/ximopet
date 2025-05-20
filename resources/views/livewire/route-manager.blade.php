<x-default-layout>
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Route Manager</h3>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#routeModal">
                        Add New Route
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-5">
                    <div class="col-md-6">
                        <input type="text" class="form-control" wire:model.live="search" placeholder="Search routes...">
                    </div>
                    <div class="col-md-6 d-flex justify-content-end">
                        <button class="btn btn-warning me-2" wire:click="bulkToggleActive" wire:loading.attr="disabled">
                            Toggle Active
                        </button>
                        <button class="btn btn-danger" wire:click="bulkDelete" wire:loading.attr="disabled">
                            Delete Selected
                        </button>
                    </div>
                </div>

                @if (session()->has('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
                @endif

                @if (session()->has('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="w-25px">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" wire:model="selectAll">
                                    </div>
                                </th>
                                <th>Route Name</th>
                                <th>Path</th>
                                <th>Method</th>
                                <th>Middleware</th>
                                <th>Permission</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($routes as $route)
                            <tr>
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" wire:model="selectedRoutes"
                                            value="{{ $route->id }}">
                                    </div>
                                </td>
                                <td>{{ $route->route_name }}</td>
                                <td>{{ $route->route_path }}</td>
                                <td>{{ $route->method }}</td>
                                <td>
                                    @if ($route->middleware)
                                    @foreach ($route->middleware as $middleware)
                                    <span class="badge badge-light-primary me-1">{{ $middleware }}</span>
                                    @endforeach
                                    @endif
                                </td>
                                <td>{{ $route->permission_name }}</td>
                                <td>
                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox"
                                            wire:click="toggleActive({{ $route->id }})" {{ $route->is_active ? 'checked'
                                        : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-light-primary me-2"
                                        wire:click="edit({{ $route->id }})" data-bs-toggle="modal"
                                        data-bs-target="#routeModal">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-light-danger" wire:click="delete({{ $route->id }})"
                                        wire:confirm="Are you sure you want to delete this route?">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $routes->links() }}
                </div>
            </div>
        </div>

        <!-- Route Modal -->
        <div class="modal fade" id="routeModal" tabindex="-1" wire:ignore.self>
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingId ? 'Edit Route' : 'Add New Route' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="save">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <label class="form-label required">Route Name</label>
                                    <input type="text" class="form-control" wire:model="route_name"
                                        placeholder="Enter route name">
                                    @error('route_name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Route Path</label>
                                    <input type="text" class="form-control" wire:model="route_path"
                                        placeholder="Enter route path">
                                    @error('route_path') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">Method</label>
                                    <select class="form-select" wire:model="method">
                                        <option value="">Select Method</option>
                                        @foreach ($methods as $method)
                                        <option value="{{ $method }}">{{ $method }}</option>
                                        @endforeach
                                    </select>
                                    @error('method') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Permission Name</label>
                                    <input type="text" class="form-control" wire:model="permission_name"
                                        placeholder="Enter permission name">
                                    @error('permission_name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Middleware</label>
                                    <select class="form-select" wire:model="middleware" multiple>
                                        @foreach ($middlewareOptions as $middleware)
                                        <option value="{{ $middleware }}">{{ $middleware }}</option>
                                        @endforeach
                                    </select>
                                    @error('middleware') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" wire:model="description" rows="3"
                                        placeholder="Enter description"></textarea>
                                    @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-12">
                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" wire:model="is_active"
                                            id="isActive">
                                        <label class="form-check-label" for="isActive">Active</label>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end pt-5">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <span wire:loading.remove wire:target="save">Save</span>
                                    <span wire:loading wire:target="save"
                                        class="spinner-border spinner-border-sm align-middle me-2"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('routeUpdated', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('routeModal'));
                if (modal) {
                    modal.hide();
                }
            });
        });
    </script>
    @endpush
</x-default-layout>