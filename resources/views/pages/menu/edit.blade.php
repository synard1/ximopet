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
                    <div class="input-group">
                        <input type="text" class="form-control @error('icon') is-invalid @enderror" id="icon"
                            name="icon" value="{{ old('icon', $menu->icon) }}" autocomplete="off">
                        <span class="input-group-text" id="icon-preview">
                            <i class="fa {{ old('icon', $menu->icon) }}"></i>
                        </span>
                        <button type="button" class="btn btn-outline-secondary" id="icon-picker-btn">Pilih Icon</button>
                    </div>
                    @error('icon')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Modal Icon Picker -->
                <div class="modal fade" id="iconPickerModal" tabindex="-1" aria-labelledby="iconPickerModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="iconPickerModalLabel">Pilih Icon</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="icon-list"
                                    style="max-height:400px;overflow-y:auto;display:flex;flex-wrap:wrap;gap:10px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @push('scripts')
                <script>
                    // Ambil list icon dari backend config/fontawesome.php
                    const faFreeSolidIcons = @json(config('fontawesome'));
                    document.addEventListener('DOMContentLoaded', function() {
                        // Update preview on input change
                        const iconInput = document.getElementById('icon');
                        const iconPreview = document.querySelector('#icon-preview i');
                        iconInput.addEventListener('input', function() {
                            if (faFreeSolidIcons.includes(this.value)) {
                                iconPreview.className = 'fa ' + this.value;
                            } else {
                                iconPreview.className = '';
                            }
                        });

                        // Open modal
                        document.getElementById('icon-picker-btn').addEventListener('click', function() {
                            const modal = new bootstrap.Modal(document.getElementById('iconPickerModal'));
                            modal.show();
                        });

                        // Load icons to modal
                        let iconsLoaded = false;
                        document.getElementById('iconPickerModal').addEventListener('show.bs.modal', function() {
                            if (iconsLoaded) return;
                            const iconList = document.getElementById('icon-list');
                            faFreeSolidIcons.forEach(icon => {
                                const iconEl = document.createElement('div');
                                iconEl.style.cursor = 'pointer';
                                iconEl.style.width = '60px';
                                iconEl.style.textAlign = 'center';
                                iconEl.innerHTML = `<i class=\"fa ${icon}\" style=\"font-size:24px;\"></i><div style=\"font-size:10px;\">${icon}</div>`;
                                iconEl.onclick = function() {
                                    iconInput.value = 'fa-solid ' + icon;
                                    iconPreview.className = 'fa ' + icon;
                                    bootstrap.Modal.getInstance(document.getElementById('iconPickerModal')).hide();
                                };
                                iconList.appendChild(iconEl);
                            });
                            iconsLoaded = true;
                        });
                    });
                </script>
                @endpush

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