<div class="modal fade" tabindex="-1" role="dialog" id="enhanced_user_modal" wire:ignore.self>
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-user-plus me-2"></i>
                    {{ $edit_mode ? 'Edit User' : 'Create New User' }}
                </h5>
                <button type="button" class="btn-close" wire:click="closeModal()" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'basic' ? 'active' : '' }}"
                            wire:click="setActiveTab('basic')" type="button" role="tab">
                            <i class="fa fa-user me-2"></i>Basic Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'permissions' ? 'active' : '' }}"
                            wire:click="setActiveTab('permissions')" type="button" role="tab">
                            <i class="fa fa-shield-alt me-2"></i>Permissions
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'access' ? 'active' : '' }}"
                            wire:click="setActiveTab('access')" type="button" role="tab">
                            <i class="fa fa-map-marker-alt me-2"></i>Farm Access
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'advanced' ? 'active' : '' }}"
                            wire:click="setActiveTab('advanced')" type="button" role="tab">
                            <i class="fa fa-cog me-2"></i>Advanced Settings
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Basic Information Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'basic' ? 'show active' : '' }}" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label required">Full Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        wire:model="name" id="name" placeholder="Enter full name">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label required">Email Address</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        wire:model="email" id="email" placeholder="Enter email address">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="mb-3">
                                    <label for="role" class="form-label required">Role</label>
                                    <select class="form-select @error('role') is-invalid @enderror" wire:model="role"
                                        id="role">
                                        <option value="">Select Role</option>
                                        @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ ucwords($role->name) }}</option>
                                        @endforeach
                                    </select>
                                    @error('role') <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" wire:model="status" id="status">
                                        <option value="Aktif">Active</option>
                                        <option value="Tidak Aktif">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password"
                                        class="form-label {{ !$edit_mode ? 'required' : '' }}">Password</label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            wire:model="password" id="password" placeholder="Enter password">
                                        <button class="btn btn-outline-secondary" type="button"
                                            onclick="togglePassword('password')">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="mb-3">
                                    <label for="passwordConfirmation"
                                        class="form-label {{ !$edit_mode ? 'required' : '' }}">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control @error('passwordConfirmation') is-invalid @enderror"
                                            wire:model="passwordConfirmation" id="passwordConfirmation"
                                            placeholder="Confirm password">
                                        <button class="btn btn-outline-secondary" type="button"
                                            onclick="togglePassword('passwordConfirmation')">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('passwordConfirmation') <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Password Generator -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Password Generator</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">Length</label>
                                                <input type="number" class="form-control form-control-sm"
                                                    wire:model="generate_length" min="8" max="32">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Options</label>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:model="generate_uppercase" id="genUpper">
                                                    <label class="form-check-label" for="genUpper">A-Z</label>
                                                </div>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:model="generate_lowercase" id="genLower">
                                                    <label class="form-check-label" for="genLower">a-z</label>
                                                </div>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:model="generate_numbers" id="genNumbers">
                                                    <label class="form-check-label" for="genNumbers">0-9</label>
                                                </div>
                                                <div class="form-check form-check-sm">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:model="generate_symbols" id="genSymbols">
                                                    <label class="form-check-label" for="genSymbols">!@#$</label>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm mt-2"
                                            wire:click="generatePassword">
                                            <i class="fa fa-magic me-1"></i>Generate Password
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'permissions' ? 'show active' : '' }}" role="tabpanel">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6>User Permissions</h6>
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm me-2"
                                            wire:click="resetPermissions">
                                            <i class="fa fa-refresh me-1"></i>Reset
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            wire:click="enableAllPermissions">
                                            <i class="fa fa-check-square me-1"></i>Enable All
                                        </button>
                                    </div>
                                </div>

                                @foreach($availablePermissions as $category => $permissions)
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0 text-capitalize">{{ str_replace('_', ' ', $category) }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($permissions as $permission => $config)
                                            @if($config['enabled'])
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:click="togglePermission('{{ $category }}', '{{ $permission }}')"
                                                        @if($userPermissions[$category][$permission] ?? false) checked
                                                        @endif id="perm_{{ $category }}_{{ $permission }}">
                                                    <label class="form-check-label"
                                                        for="perm_{{ $category }}_{{ $permission }}">
                                                        <strong>{{ $config['label'] }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $config['description'] }}</small>
                                                    </label>
                                                </div>
                                            </div>
                                            @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Farm Access Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'access' ? 'show active' : '' }}" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Company</label>
                                    <select class="form-select" wire:model="company_id">
                                        <option value="">Select Company</option>
                                        @foreach($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Farms Access</label>
                                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                        @foreach($farms as $farm)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" wire:model="farm_ids"
                                                value="{{ $farm->id }}" id="farm_{{ $farm->id }}">
                                            <label class="form-check-label" for="farm_{{ $farm->id }}">
                                                {{ $farm->name }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="is_field_operator"
                                            id="isFieldOperator">
                                        <label class="form-check-label" for="isFieldOperator">
                                            Field Operator
                                        </label>
                                    </div>
                                    <small class="text-muted">Enable if user will work directly in the field</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Coops Access</label>
                                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                        @if(count($coops) > 0)
                                        @foreach($coops as $coop)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" wire:model="coop_ids"
                                                value="{{ $coop->id }}" id="coop_{{ $coop->id }}">
                                            <label class="form-check-label" for="coop_{{ $coop->id }}">
                                                {{ $coop->name }} ({{ $coop->farm_name ?? 'Unknown Farm' }})
                                            </label>
                                        </div>
                                        @endforeach
                                        @else
                                        <p class="text-muted">No coops available. Select farms first.</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Access Level</label>
                                    <select class="form-select" wire:model="access_level">
                                        <option value="basic">Basic</option>
                                        <option value="advanced">Advanced</option>
                                        <option value="expert">Expert</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'advanced' ? 'show active' : '' }}" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="employee_code" class="form-label">Employee Code</label>
                                    <input type="text" class="form-control" wire:model="employee_code"
                                        id="employee_code" placeholder="Enter employee code">
                                </div>

                                <div class="mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" wire:model="position" id="position"
                                        placeholder="Enter position">
                                </div>

                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" wire:model="department" id="department"
                                        placeholder="Enter department">
                                </div>

                                <div class="mb-3">
                                    <label for="supervisor_id" class="form-label">Supervisor</label>
                                    <select class="form-select" wire:model="supervisor_id" id="supervisor_id">
                                        <option value="">Select Supervisor</option>
                                        @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" wire:model="is_email_enabled"
                                            id="isEmailEnabled">
                                        <label class="form-check-label" for="isEmailEnabled">
                                            Enable Email Notifications
                                        </label>
                                    </div>
                                    <small class="text-muted">User will receive email notifications for important
                                        events</small>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">User Settings Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Role:</small><br>
                                                <strong>{{ ucwords($role ?? 'Not Set') }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Status:</small><br>
                                                <strong>{{ $status }}</strong>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Farms:</small><br>
                                                <strong>{{ count($farm_ids) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Coops:</small><br>
                                                <strong>{{ count($coop_ids) }}</strong>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Access Level:</small><br>
                                                <strong>{{ ucwords($access_level) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Field Operator:</small><br>
                                                <strong>{{ $is_field_operator ? 'Yes' : 'No' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeModal()">
                    <i class="fa fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" wire:click="store()">
                    <i class="fa fa-save me-1"></i>{{ $edit_mode ? 'Update User' : 'Create User' }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fa fa-eye';
    }
}

// Auto-show modal when isOpen is true
document.addEventListener('livewire:load', function () {
    Livewire.on('show-enhanced-user-modal', () => {
        const modal = new bootstrap.Modal(document.getElementById('enhanced_user_modal'));
        modal.show();
    });
});
</script>

<style>
    .required::after {
        content: " *";
        color: red;
    }

    .nav-tabs .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        color: #6c757d;
    }

    .nav-tabs .nav-link.active {
        border-bottom-color: #0d6efd;
        color: #0d6efd;
        background: none;
    }

    .tab-content {
        min-height: 400px;
    }

    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
</style>