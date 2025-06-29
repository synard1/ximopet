<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Company Selector for SuperAdmin --}}
    @if($showCompanySelector)
    <div class="card mb-5">
        <div class="card-header">
            <h3 class="card-title">Select Company to Manage</h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">As SuperAdmin, please select a company to manage its administrators:</p>

            @if($availableCompanies && count($availableCompanies) > 0)
            <div class="row">
                @foreach($availableCompanies as $company)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card border border-hover-primary cursor-pointer h-100"
                        wire:click="selectCompany('{{ $company->id }}')">
                        <div class="card-body text-center">
                            <div class="symbol symbol-50px mx-auto mb-3">
                                <div class="symbol-label fs-2 fw-semibold text-primary bg-light-primary">
                                    {{ substr($company->name, 0, 1) }}
                                </div>
                            </div>
                            <h5 class="card-title">{{ $company->name }}</h5>
                            <span class="badge badge-light-success">{{ $company->status }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <div class="text-muted">No active companies found.</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Stop rendering rest of component if company not selected --}}
    @php return; @endphp
    @endif

    {{-- Current Company Info for SuperAdmin --}}
    @if(auth()->user()->hasRole('SuperAdmin') && $companyId)
    <div class="alert alert-info d-flex align-items-center mb-5">
        <i class="ki-duotone ki-information-5 fs-2hx text-info me-4"><span class="path1"></span><span
                class="path2"></span><span class="path3"></span></i>
        <div class="flex-grow-1">
            <span class="fw-bold">Managing Company: </span>
            @php
            $currentCompany = \App\Models\Company::find($companyId);
            @endphp
            {{ $currentCompany->name ?? 'Unknown Company' }}
        </div>
        <button type="button" class="btn btn-sm btn-light-primary" wire:click="showCompanySelectorModal">
            Change Company
        </button>
    </div>
    @endif

    {{-- Admin Statistics Cards --}}
    <div class="row g-5 g-xl-8 mb-5">
        <div class="col-xl-3">
            <div class="card card-xl-stretch">
                <div class="card-body d-flex align-items-center">
                    <div class="symbol symbol-50px me-3">
                        <span class="symbol-label bg-light-primary">
                            <i class="ki-duotone ki-people fs-2x text-primary"><span class="path1"></span><span
                                    class="path2"></span><span class="path3"></span><span class="path4"></span><span
                                    class="path5"></span></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-gray-900 fw-bold fs-6">Total Users</div>
                        <div class="fw-semibold text-gray-400">{{ $adminStatistics['total_users'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-xl-stretch">
                <div class="card-body d-flex align-items-center">
                    <div class="symbol symbol-50px me-3">
                        <span class="symbol-label bg-light-success">
                            <i class="ki-duotone ki-crown fs-2x text-success"><span class="path1"></span><span
                                    class="path2"></span></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-gray-900 fw-bold fs-6">Total Admins</div>
                        <div class="fw-semibold text-gray-400">{{ $adminStatistics['total_admins'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-xl-stretch">
                <div class="card-body d-flex align-items-center">
                    <div class="symbol symbol-50px me-3">
                        <span class="symbol-label bg-light-warning">
                            <i class="ki-duotone ki-shield-tick fs-2x text-warning"><span class="path1"></span><span
                                    class="path2"></span><span class="path3"></span></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-gray-900 fw-bold fs-6">Default Admin</div>
                        <div class="fw-semibold text-gray-400">
                            {{ ($adminStatistics['has_default_admin'] ?? false) ? 'Set' : 'Not Set' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-xl-stretch">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3">
                            <span class="symbol-label bg-light-info">
                                <i class="ki-duotone ki-refresh fs-2x text-info"><span class="path1"></span><span
                                        class="path2"></span></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-gray-900 fw-bold fs-6">Actions</div>
                            <div class="fw-semibold text-gray-400">Manage Roles</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-light-primary" wire:click="refreshData">
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Default Admin Info --}}
    @if($defaultAdmin)
    <div class="card mb-5">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-45px me-3">
                        <div class="symbol-label fs-2 fw-semibold text-success bg-light-success">
                            {{ substr($defaultAdmin->user->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <span class="text-gray-900 fw-bold d-block fs-6">{{ $defaultAdmin->user->name }}</span>
                        <span class="text-gray-400 fw-semibold d-block fs-7">{{ $defaultAdmin->user->email }}</span>
                    </div>
                    <span class="badge badge-light-success fw-bold">Default Admin</span>
                </div>
                @if($this->canManageDefaultAdmin)
                <button type="button" class="btn btn-sm btn-light-warning" wire:click="openTransferModal">
                    Transfer Role
                </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Search and Actions --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold">Company Administrators</h3>
            </div>
            <div class="card-toolbar">
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3"
                            style="top: 50%; transform: translateY(-50%);">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" class="form-control form-control-sm ps-10" placeholder="Search users..."
                            wire:model.live="searchTerm">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            {{-- Current Admins --}}
            <div class="mb-8">
                <h4 class="text-gray-900 fw-bold mb-4">Current Administrators</h4>
                @if($this->filteredAdmins && count($this->filteredAdmins) > 0)
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 gy-7">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800">
                                <th>User</th>
                                <th>Role</th>
                                <th>Added On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->filteredAdmins as $admin)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px me-3">
                                            <div class="symbol-label fs-3 fw-semibold text-primary bg-light-primary">
                                                {{ substr($admin->user->name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="text-gray-900 fw-bold d-block fs-6">{{ $admin->user->name
                                                }}</span>
                                            <span class="text-gray-400 fw-semibold d-block fs-7">{{ $admin->user->email
                                                }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($admin->isDefaultAdmin)
                                    <span class="badge badge-success fw-bold">Default Admin</span>
                                    @else
                                    <span class="badge badge-primary fw-bold">Administrator</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-gray-600 fw-semibold">{{ $admin->created_at->format('M j, Y')
                                        }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        {{-- Set as Default Admin --}}
                                        @if(!$admin->isDefaultAdmin && $this->canManageDefaultAdmin)
                                        <button type="button" class="btn btn-light-warning"
                                            wire:click="setDefaultAdmin('{{ $admin->user_id }}')"
                                            wire:confirm="Are you sure you want to set {{ $admin->user->name }} as default admin?">
                                            Set Default
                                        </button>
                                        @endif

                                        {{-- Demote Admin --}}
                                        @if(!$admin->isDefaultAdmin)
                                        <button type="button" class="btn btn-light-danger"
                                            wire:click="openDemoteModal('{{ $admin->user_id }}', '{{ $admin->user->name }}')">
                                            Demote
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <div class="text-gray-400">No administrators found.</div>
                </div>
                @endif
            </div>

            {{-- Promotable Users --}}
            <div class="separator separator-dashed my-8"></div>
            <div>
                <h4 class="text-gray-900 fw-bold mb-4">Regular Users (Can be promoted)</h4>
                @if($this->filteredPromotableUsers && count($this->filteredPromotableUsers) > 0)
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 gy-7">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800">
                                <th>User</th>
                                <th>Joined On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->filteredPromotableUsers as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px me-3">
                                            <div class="symbol-label fs-3 fw-semibold text-info bg-light-info">
                                                {{ substr($user->user->name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="text-gray-900 fw-bold d-block fs-6">{{ $user->user->name
                                                }}</span>
                                            <span class="text-gray-400 fw-semibold d-block fs-7">{{ $user->user->email
                                                }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-gray-600 fw-semibold">{{ $user->created_at->format('M j, Y')
                                        }}</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light-success"
                                        wire:click="openPromoteModal('{{ $user->user_id }}', '{{ $user->user->name }}')">
                                        Promote to Admin
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <div class="text-gray-400">No users available for promotion.</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modals --}}
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                {{-- Promote Modal --}}
                @if($modalType === 'promote')
                <div class="modal-header">
                    <h3 class="modal-title">Promote User to Administrator</h3>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to promote <strong>{{ $selectedUserName }}</strong> to administrator?</p>
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" wire:model="setAsDefault" id="setAsDefault">
                        <label class="form-check-label" for="setAsDefault">
                            Set as Default Administrator
                            <div class="text-muted fs-7">This will remove the current default admin role if one exists.
                            </div>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeModal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="promoteUser">Promote User</button>
                </div>
                @endif

                {{-- Transfer Default Admin Modal --}}
                @if($modalType === 'transfer')
                <div class="modal-header">
                    <h3 class="modal-title">Transfer Default Admin Role</h3>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-4">Select a new default administrator:</p>
                    @if($admins && count($admins) > 1)
                    @foreach($admins as $admin)
                    @if(!$admin->isDefaultAdmin)
                    <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px me-3">
                                <div class="symbol-label fs-3 fw-semibold text-primary bg-light-primary">
                                    {{ substr($admin->user->name, 0, 1) }}
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $admin->user->name }}</div>
                                <div class="text-muted fs-7">{{ $admin->user->email }}</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary"
                            wire:click="transferDefaultAdmin('{{ $admin->user_id }}')"
                            wire:confirm="Are you sure you want to transfer default admin role to {{ $admin->user->name }}?">
                            Select
                        </button>
                    </div>
                    @endif
                    @endforeach
                    @else
                    <div class="text-center py-4">
                        <div class="text-muted">No other administrators available for transfer.</div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeModal">Cancel</button>
                </div>
                @endif

                {{-- Demote Modal --}}
                @if($modalType === 'demote')
                <div class="modal-header">
                    <h3 class="modal-title">Demote Administrator</h3>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to demote <strong>{{ $selectedUserName }}</strong> from administrator to
                        regular user?</p>
                    <div class="alert alert-warning">
                        <i class="ki-duotone ki-warning fs-2hx text-warning me-4"><span class="path1"></span><span
                                class="path2"></span><span class="path3"></span></i>
                        This user will lose all administrative privileges.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeModal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="demoteUser">Demote User</button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Company Selector Modal for SuperAdmin --}}
    @if($showCompanySelector && !$companyId)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Select Company to Manage</h3>
                    <button type="button" class="btn-close" wire:click="hideCompanySelector"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Choose a company to manage its administrators:</p>

                    @if($availableCompanies && count($availableCompanies) > 0)
                    <div class="row">
                        @foreach($availableCompanies as $company)
                        <div class="col-md-6 mb-3">
                            <div class="card border border-hover-primary cursor-pointer h-100"
                                wire:click="selectCompany('{{ $company->id }}')">
                                <div class="card-body text-center">
                                    <div class="symbol symbol-50px mx-auto mb-3">
                                        <div class="symbol-label fs-2 fw-semibold text-primary bg-light-primary">
                                            {{ substr($company->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <h6 class="card-title">{{ $company->name }}</h6>
                                    <span class="badge badge-light-success">{{ $company->status }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <div class="text-muted">No active companies found.</div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="hideCompanySelector">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>