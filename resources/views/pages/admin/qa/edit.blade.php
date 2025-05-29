<x-default-layout>


    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Menu</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('administrator.menu.update', $menu) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                        value="{{ old('name', $menu->name) }}" required>
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="label" class="form-label">Label</label>
                    <input type="text" class="form-control @error('label') is-invalid @enderror" id="label" name="label"
                        value="{{ old('label', $menu->label) }}" required>
                    @error('label')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="route" class="form-label">Route</label>
                    <input type="text" class="form-control @error('route') is-invalid @enderror" id="route" name="route"
                        value="{{ old('route', $menu->route) }}">
                    @error('route')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="icon" class="form-label">Icon</label>
                    <input type="text" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon"
                        value="{{ old('icon', $menu->icon) }}">
                    @error('icon')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <select class="form-control @error('location') is-invalid @enderror" id="location" name="location"
                        required>
                        <option value="sidebar" {{ old('location', $menu->location) == 'sidebar' ? 'selected' : ''
                            }}>Sidebar</option>
                        <option value="header" {{ old('location', $menu->location) == 'header' ? 'selected' : ''
                            }}>Header
                        </option>
                    </select>
                    @error('location')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="parent_id" class="form-label">Parent Menu</label>
                    <select class="form-control @error('parent_id') is-invalid @enderror" id="parent_id"
                        name="parent_id">
                        <option value="">None</option>
                        @foreach($parentMenus as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $menu->parent_id) == $parent->id ?
                            'selected'
                            : '' }}>
                            {{ $parent->label }}
                        </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="order_number" class="form-label">Order Number</label>
                    <input type="number" class="form-control @error('order_number') is-invalid @enderror"
                        id="order_number" name="order_number" value="{{ old('order_number', $menu->order_number) }}"
                        required>
                    @error('order_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Roles</label>
                    <div class="row">
                        @foreach($roles as $role)
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="role_{{ $role->id }}" name="roles[]"
                                    value="{{ $role->id }}" {{ in_array($role->id, old('roles',
                                $menu->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                <label class="form-check-label" for="role_{{ $role->id }}">
                                    {{ $role->name }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Permissions</label>
                    <div class="row">
                        @foreach($permissions as $permission)
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="permission_{{ $permission->id }}"
                                    name="permissions[]" value="{{ $permission->id }}" {{ in_array($permission->id,
                                old('permissions', $menu->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="text-end">
                    <a href="{{ route('administrator.menu.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Menu</button>
                </div>
            </form>
        </div>
    </div>
</x-default-layout>