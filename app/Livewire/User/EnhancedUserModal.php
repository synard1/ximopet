<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Models\CompanyUser;
use App\Config\CompanyConfig;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class EnhancedUserModal extends Component
{
    use WithFileUploads;

    public $user_id = null;
    public $name;
    public $email;
    public $role;
    public $avatar;
    public $saved_avatar;
    public $password = '';
    public $passwordConfirmation = '';
    public $user;

    // User permissions based on CompanyConfig
    public $userPermissions = [];
    public $availablePermissions = [];

    // Company and farm settings
    public $company_id;
    public $farm_ids = [];
    public $coop_ids = [];
    public $employee_code;
    public $position;
    public $department;
    public $supervisor_id;
    public $access_level = 'basic';
    public $is_field_operator = false;
    public $is_email_enabled = false;
    public $status = 'Aktif';

    // Tab management
    public $activeTab = 'basic';

    public $edit_mode = false;
    public $isOpen = 0;

    public $generate_length = 12;
    public $generate_uppercase = true;
    public $generate_lowercase = true;
    public $generate_numbers = true;
    public $generate_symbols = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'role' => 'required|string',
        'avatar' => 'nullable|sometimes|image|max:1024',
        'password' => 'required|min:8|same:passwordConfirmation',
        'passwordConfirmation' => 'required',
        'employee_code' => 'nullable|string|max:255',
        'position' => 'nullable|string|max:255',
        'department' => 'nullable|string|max:255',
        'supervisor_id' => 'nullable|exists:users,id',
        'farm_ids' => 'nullable|array',
        'farm_ids.*' => 'exists:farms,id',
        'coop_ids' => 'nullable|array',
        'coop_ids.*' => 'exists:coops,id',
        'access_level' => 'required|in:basic,advanced,expert',
        'is_field_operator' => 'boolean',
        'is_email_enabled' => 'boolean',
        'status' => 'required|in:Aktif,Tidak Aktif',
    ];

    protected $messages = [
        'name.required' => 'Nama harus diisi',
        'name.string' => 'Nama harus berupa teks',
        'name.max' => 'Nama maksimal 255 karakter',
        'email.required' => 'Email harus diisi',
        'email.email' => 'Format email tidak valid',
        'email.unique' => 'Email sudah digunakan',
        'role.required' => 'Role harus dipilih',
        'password.required' => 'Password harus diisi',
        'password.min' => 'Password minimal 8 karakter',
        'password.same' => 'Konfirmasi password tidak cocok',
        'passwordConfirmation.required' => 'Konfirmasi password harus diisi',
        'status.required' => 'Status harus dipilih',
        'status.in' => 'Status harus Aktif atau Tidak Aktif',
        'access_level.required' => 'Level akses harus dipilih',
        'access_level.in' => 'Level akses tidak valid',
        'supervisor_id.exists' => 'Supervisor tidak ditemukan',
        'farm_ids.*.exists' => 'Farm tidak ditemukan',
        'coop_ids.*.exists' => 'Kandang tidak ditemukan',
    ];

    protected $listeners = [
        'delete_user' => 'deleteUser',
        'update_user' => 'updateUser',
        'edit' => 'edit',
        'new_user' => 'create',
        'suspendUser' => 'suspendUser',
        'cancelSuspension' => 'cancelSuspension',
        'new_user_company' => 'createCompanyUser',
    ];

    public function mount()
    {
        $this->initializePermissions();
        $this->initializeDefaultSettings();
    }

    /**
     * Initialize available permissions based on CompanyConfig
     */
    protected function initializePermissions()
    {
        // Get company config based on current user's company
        $currentUserMapping = CompanyUser::getUserMapping();

        if ($currentUserMapping && $currentUserMapping->company) {
            $companyConfig = $currentUserMapping->company->config ?? CompanyConfig::getDefaultConfig();
        } else {
            $companyConfig = CompanyConfig::getDefaultConfig();
        }

        // Build available permissions structure
        $this->availablePermissions = $this->buildPermissionsFromConfig($companyConfig);

        // Initialize empty user permissions
        foreach ($this->availablePermissions as $category => $permissions) {
            foreach ($permissions as $permission => $config) {
                $this->userPermissions[$category][$permission] = false;
            }
        }
    }

    /**
     * Build permissions structure from CompanyConfig
     */
    protected function buildPermissionsFromConfig($config)
    {
        $permissions = [];

        // Farm Management permissions
        $permissions['farm_management'] = [
            'manage_farms' => [
                'label' => 'Kelola Farm',
                'description' => 'Dapat menambah, edit, dan hapus data farm',
                'enabled' => true,
            ],
            'manage_coops' => [
                'label' => 'Kelola Kandang',
                'description' => 'Dapat menambah, edit, dan hapus data kandang',
                'enabled' => true,
            ],
            'view_all_farms' => [
                'label' => 'Lihat Semua Farm',
                'description' => 'Dapat melihat data semua farm dalam perusahaan',
                'enabled' => true,
            ],
            'transfer_livestock' => [
                'label' => 'Transfer Ternak',
                'description' => 'Dapat melakukan transfer ternak antar kandang/farm',
                'enabled' => true,
            ],
        ];

        // Livestock management permissions
        if (isset($config['livestock'])) {
            $permissions['livestock'] = [
                'manage_livestock' => [
                    'label' => 'Kelola Ternak',
                    'description' => 'Dapat menambah, edit, dan hapus data ternak',
                    'enabled' => $config['livestock']['enabled'] ?? true,
                ],
                'feed_usage' => [
                    'label' => 'Pemakaian Pakan',
                    'description' => 'Dapat mencatat dan mengatur pemakaian pakan',
                    'enabled' => $config['livestock']['feed_usage']['enabled'] ?? true,
                ],
                'mortality_recording' => [
                    'label' => 'Pencatatan Kematian',
                    'description' => 'Dapat mencatat kematian ternak',
                    'enabled' => $config['livestock']['depletion_tracking']['types']['mortality']['enabled'] ?? true,
                ],
                'health_management' => [
                    'label' => 'Manajemen Kesehatan',
                    'description' => 'Dapat mengelola data kesehatan ternak',
                    'enabled' => $config['livestock']['health_management']['enabled'] ?? true,
                ],
                'weight_recording' => [
                    'label' => 'Pencatatan Berat',
                    'description' => 'Dapat mencatat berat ternak',
                    'enabled' => $config['livestock']['weight_tracking']['enabled'] ?? true,
                ],
                'batch_management' => [
                    'label' => 'Manajemen Batch',
                    'description' => 'Dapat mengelola batch ternak',
                    'enabled' => $config['livestock']['recording_method']['allow_multiple_batches'] ?? true,
                ],
            ];
        }

        // Purchasing permissions
        if (isset($config['purchasing'])) {
            $permissions['purchasing'] = [
                'livestock_purchase' => [
                    'label' => 'Pembelian Ternak',
                    'description' => 'Dapat melakukan pembelian ternak',
                    'enabled' => $config['purchasing']['livestock_purchase']['enabled'] ?? true,
                ],
                'feed_purchase' => [
                    'label' => 'Pembelian Pakan',
                    'description' => 'Dapat melakukan pembelian pakan',
                    'enabled' => $config['purchasing']['feed_purchase']['enabled'] ?? true,
                ],
                'supply_purchase' => [
                    'label' => 'Pembelian Supply/OVK',
                    'description' => 'Dapat melakukan pembelian supply dan OVK',
                    'enabled' => $config['purchasing']['supply_purchase']['enabled'] ?? true,
                ],
                'approve_purchases' => [
                    'label' => 'Approve Pembelian',
                    'description' => 'Dapat menyetujui pembelian',
                    'enabled' => true,
                ],
                'manage_suppliers' => [
                    'label' => 'Kelola Supplier',
                    'description' => 'Dapat mengelola data supplier',
                    'enabled' => true,
                ],
            ];
        }

        // Sales & Marketing permissions
        $permissions['sales'] = [
            'manage_sales' => [
                'label' => 'Kelola Penjualan',
                'description' => 'Dapat mengelola penjualan ternak',
                'enabled' => true,
            ],
            'manage_customers' => [
                'label' => 'Kelola Pelanggan',
                'description' => 'Dapat mengelola data pelanggan',
                'enabled' => true,
            ],
            'price_management' => [
                'label' => 'Manajemen Harga',
                'description' => 'Dapat mengatur harga jual',
                'enabled' => true,
            ],
            'delivery_management' => [
                'label' => 'Manajemen Pengiriman',
                'description' => 'Dapat mengelola pengiriman ternak',
                'enabled' => true,
            ],
        ];

        // Reporting permissions
        $permissions['reporting'] = [
            'view_reports' => [
                'label' => 'Lihat Laporan',
                'description' => 'Dapat melihat berbagai laporan',
                'enabled' => true,
            ],
            'export_reports' => [
                'label' => 'Export Laporan',
                'description' => 'Dapat mengexport laporan ke Excel/PDF',
                'enabled' => true,
            ],
            'advanced_analytics' => [
                'label' => 'Analitik Lanjutan',
                'description' => 'Dapat mengakses dashboard dan analitik lanjutan',
                'enabled' => true,
            ],
            'financial_reports' => [
                'label' => 'Laporan Keuangan',
                'description' => 'Dapat melihat laporan keuangan',
                'enabled' => true,
            ],
        ];

        // System Administration permissions
        $permissions['administration'] = [
            'user_management' => [
                'label' => 'Manajemen User',
                'description' => 'Dapat mengelola user dan permissions',
                'enabled' => true,
            ],
            'system_settings' => [
                'label' => 'Pengaturan Sistem',
                'description' => 'Dapat mengubah pengaturan sistem',
                'enabled' => true,
            ],
            'audit_trail' => [
                'label' => 'Audit Trail',
                'description' => 'Dapat melihat log aktivitas sistem',
                'enabled' => true,
            ],
            'backup_restore' => [
                'label' => 'Backup & Restore',
                'description' => 'Dapat melakukan backup dan restore data',
                'enabled' => true,
            ],
        ];

        return $permissions;
    }

    /**
     * Initialize default settings
     */
    protected function initializeDefaultSettings()
    {
        $this->company_id = auth()->user()->company_id ?? null;
        $this->status = 'Aktif';
        $this->is_email_enabled = false;
        $this->access_level = 'basic';
        $this->is_field_operator = false;
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $roles = auth()->user()->getAvailableRoles();
        $roles_description = config('xolution.company_role_descriptions', []);

        foreach ($roles as $i => $role) {
            $roles[$i]->description = $roles_description[$role->name] ?? '';
        }

        // Get dropdown data
        $companies = \App\Models\Company::select('id', 'name')->orderBy('name')->get();

        // Get farms based on user access
        $farmsQuery = DB::table('farms')->select('id', 'name');
        if (!auth()->user()->hasRole('SuperAdmin')) {
            $currentUserMapping = CompanyUser::getUserMapping();
            if ($currentUserMapping) {
                $farmsQuery->where('company_id', $currentUserMapping->company_id);
            }
        }
        $farms = $farmsQuery->orderBy('name')->get();

        // Get coops based on selected farms or user access
        $coopsQuery = DB::table('coops')->select('id', 'name', 'farm_id');
        if (!empty($this->farm_ids)) {
            $coopsQuery->whereIn('farm_id', $this->farm_ids);
        } elseif (!auth()->user()->hasRole('SuperAdmin')) {
            $currentUserMapping = CompanyUser::getUserMapping();
            if ($currentUserMapping) {
                $coopsQuery->whereExists(function ($query) use ($currentUserMapping) {
                    $query->select(DB::raw(1))
                        ->from('farms')
                        ->whereRaw('farms.id = coops.farm_id')
                        ->where('farms.company_id', $currentUserMapping->company_id);
                });
            }
        }
        $coops = $coopsQuery->orderBy('name')->get();

        // Get supervisors (users with Supervisor or Administrator role)
        $supervisors = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Supervisor', 'Administrator']);
        })->where('id', '!=', $this->user_id)->select('id', 'name')->get();

        return view('livewire.user.enhanced-user-modal', compact(
            'roles',
            'companies',
            'farms',
            'coops',
            'supervisors'
        ));
    }

    public function openModal()
    {
        $this->isOpen = true;
        $this->activeTab = 'basic';
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->reset();
        $this->initializePermissions();
        $this->initializeDefaultSettings();
    }

    protected function rules()
    {
        $rules = $this->rules;

        // Modify email validation for edit mode
        if ($this->edit_mode && $this->user_id) {
            $rules['email'] = 'required|email|unique:users,email,' . $this->user_id;
        }

        // Password is optional in edit mode
        if ($this->edit_mode) {
            $rules['password'] = 'nullable|min:8|same:passwordConfirmation';
            $rules['passwordConfirmation'] = 'nullable';
        }

        return $rules;
    }

    public function create()
    {
        $this->reset();
        $this->initializePermissions();
        $this->initializeDefaultSettings();
        $this->edit_mode = false;
        $this->openModal();
    }

    public function store()
    {
        try {
            // Validate the form input data
            $this->validate();

            DB::transaction(function () {
                // Prepare the data for creating/updating user
                $userData = [
                    'name' => $this->name,
                    'email' => $this->email,
                ];

                // Handle password
                if (!$this->edit_mode || !empty($this->password)) {
                    $userData['password'] = Hash::make($this->password);
                }

                if (!$this->edit_mode) {
                    $userData['email_verified_at'] = now();
                }

                // Create or update user
                if ($this->edit_mode && $this->user_id) {
                    $user = User::findOrFail($this->user_id);
                    $user->update($userData);
                } else {
                    $user = User::create($userData);
                }

                // Handle role assignment
                if ($this->edit_mode) {
                    $user->syncRoles($this->role);
                } else {
                    $user->assignRole($this->role);
                }

                // Handle CompanyUser mapping
                if (auth()->user()->hasRole('Administrator')) {
                    $currentUserMapping = CompanyUser::getUserMapping();

                    if ($currentUserMapping) {
                        CompanyUser::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'company_id' => $this->company_id ?? $currentUserMapping->company_id,
                            ],
                            [
                                'isAdmin' => $this->role === 'Administrator',
                                'status' => strtolower($this->status) === 'aktif' ? 'active' : 'inactive',
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]
                        );
                    }
                }

                // Save user permissions and farm settings
                $this->saveUserPermissions($user);
                $this->saveUserFarmAccess($user);

                // Log the action
                Log::info('User ' . ($this->edit_mode ? 'updated' : 'created'), [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $this->role,
                    'permissions' => $this->userPermissions,
                    'farm_access' => $this->farm_ids,
                    'coop_access' => $this->coop_ids,
                    'action_by' => auth()->id(),
                ]);

                if ($this->edit_mode) {
                    $this->dispatch('success', __('User berhasil diperbarui'));
                } else {
                    $this->dispatch('success', __('User baru berhasil dibuat'));
                }
            });

            $this->closeModal();
        } catch (\Exception $e) {
            Log::error('Error saving user', [
                'error' => $e->getMessage(),
                'user_data' => [
                    'name' => $this->name,
                    'email' => $this->email,
                    'role' => $this->role,
                ],
            ]);

            $this->dispatch('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Save user permissions to database
     */
    protected function saveUserPermissions($user)
    {
        $user->update([
            'custom_permissions' => json_encode($this->userPermissions),
            'settings' => json_encode([
                'employee_code' => $this->employee_code,
                'position' => $this->position,
                'department' => $this->department,
                'supervisor_id' => $this->supervisor_id,
                'access_level' => $this->access_level,
                'is_field_operator' => $this->is_field_operator,
                'is_email_enabled' => $this->is_email_enabled,
                'farm_ids' => $this->farm_ids,
                'coop_ids' => $this->coop_ids,
            ]),
        ]);
    }

    /**
     * Save user farm access
     */
    protected function saveUserFarmAccess($user)
    {
        // Save farm operators if user is field operator
        if ($this->is_field_operator && !empty($this->farm_ids)) {
            // Delete existing farm operators
            DB::table('farm_operators')->where('user_id', $user->id)->delete();

            // Insert new farm operators
            $farmOperators = [];
            foreach ($this->farm_ids as $farmId) {
                $farmOperators[] = [
                    'user_id' => $user->id,
                    'farm_id' => $farmId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($farmOperators)) {
                DB::table('farm_operators')->insert($farmOperators);
            }
        }
    }

    public function updateUser($id)
    {
        $this->edit_mode = true;
        $user = User::with('roles')->find($id);

        if (!$user) {
            $this->dispatch('error', 'User tidak ditemukan');
            return;
        }

        $this->user_id = $user->id;
        $this->saved_avatar = $user->profile_photo_url;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()->name ?? '';
        $this->user = $user;

        // Load user permissions
        $this->loadUserPermissions($user);

        // Load user settings
        $this->loadUserSettings($user);

        $this->openModal();
    }

    /**
     * Load user permissions from database
     */
    protected function loadUserPermissions($user)
    {
        if (!empty($user->custom_permissions)) {
            $permissions = json_decode($user->custom_permissions, true);
            if (is_array($permissions)) {
                $this->userPermissions = array_merge($this->userPermissions, $permissions);
            }
        }
    }

    /**
     * Load user settings from database
     */
    protected function loadUserSettings($user)
    {
        if (!empty($user->settings)) {
            $settings = json_decode($user->settings, true);
            if (is_array($settings)) {
                $this->employee_code = $settings['employee_code'] ?? '';
                $this->position = $settings['position'] ?? '';
                $this->department = $settings['department'] ?? '';
                $this->supervisor_id = $settings['supervisor_id'] ?? null;
                $this->access_level = $settings['access_level'] ?? 'basic';
                $this->is_field_operator = $settings['is_field_operator'] ?? false;
                $this->is_email_enabled = $settings['is_email_enabled'] ?? false;
                $this->farm_ids = $settings['farm_ids'] ?? [];
                $this->coop_ids = $settings['coop_ids'] ?? [];
            }
        }

        // Load company user data
        $companyUser = CompanyUser::where('user_id', $user->id)->first();
        if ($companyUser) {
            $this->company_id = $companyUser->company_id;
            $this->status = $companyUser->status === 'active' ? 'Aktif' : 'Tidak Aktif';
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deletion of current user
            if ($id == Auth::id()) {
                $this->dispatch('error-modal', [
                    'title' => 'Tidak Bisa Hapus User',
                    'icon' => 'warning',
                    'blockers' => ['Anda tidak dapat menghapus akun Anda sendiri.'],
                    'text' => 'Aksi ini tidak diizinkan.',
                ]);
                return;
            }

            // Check CompanyUser delete permissions (default admin protection)
            $canDeleteCheck = CompanyUser::canDeleteUser($id);
            if (!$canDeleteCheck['can_delete']) {
                $this->dispatch('error-modal', [
                    'title' => 'Tidak Bisa Hapus User',
                    'icon' => 'warning',
                    'blockers' => [$canDeleteCheck['reason']],
                    'text' => 'User ini tidak dapat dihapus karena memiliki peran khusus.',
                ]);
                return;
            }

            // Check if user can be deleted (data relationships)
            if (!$user->canBeDeleted()) {
                $this->dispatch('error-modal', [
                    'title' => 'Tidak Bisa Hapus User',
                    'icon' => 'warning',
                    'blockers' => ['User masih memiliki data terkait yang harus dihapus terlebih dahulu.'],
                    'text' => 'Hapus semua data terkait sebelum menghapus user ini.',
                ]);
                return;
            }

            DB::transaction(function () use ($user) {
                // Delete related CompanyUser records
                CompanyUser::where('user_id', $user->id)->delete();

                // Delete farm operators
                DB::table('farm_operators')->where('user_id', $user->id)->delete();

                // Delete the user
                $user->delete();
            });

            Log::info('User deleted', [
                'deleted_user_id' => $user->id,
                'deleted_user_name' => $user->name,
                'deleted_by' => auth()->id(),
            ]);

            $this->dispatch('success', 'User berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting user', [
                'error' => $e->getMessage(),
                'user_id' => $id,
            ]);

            $this->dispatch('error-modal', [
                'title' => 'Gagal Menghapus User',
                'icon' => 'error',
                'blockers' => [$e->getMessage()],
                'text' => 'Terjadi kesalahan saat menghapus user.',
            ]);
        }
    }

    public function suspendUser($id)
    {
        try {
            $user = User::findOrFail($id);

            // Update user status
            $user->update(['status' => 'suspended']);

            // Update CompanyUser status
            CompanyUser::where('user_id', $id)->update(['status' => 'inactive']);

            Log::info('User suspended', [
                'suspended_user_id' => $user->id,
                'suspended_user_name' => $user->name,
                'suspended_by' => auth()->id(),
            ]);

            $this->dispatch('success', 'User berhasil disuspend');
        } catch (\Exception $e) {
            $this->dispatch('error', 'Gagal suspend user: ' . $e->getMessage());
        }
    }

    public function cancelSuspension()
    {
        $this->dispatch('error', 'Suspension dibatalkan');
    }

    public function edit($id)
    {
        $this->updateUser($id);
    }

    public function createCompanyUser()
    {
        $this->isOpen = true;
    }

    public function generatePassword()
    {
        $characters = '';

        if ($this->generate_lowercase) {
            $characters .= 'abcdefghijklmnopqrstuvwxyz';
        }

        if ($this->generate_uppercase) {
            $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        if ($this->generate_numbers) {
            $characters .= '0123456789';
        }

        if ($this->generate_symbols) {
            $characters .= '!@#$%^&*()_+{}[]|:;<>,.?';
        }

        if (empty($characters)) {
            $this->dispatch('error', 'Pilih minimal satu jenis karakter untuk generate password');
            return;
        }

        $password = '';
        $charactersLength = strlen($characters);

        for ($i = 0; $i < $this->generate_length; $i++) {
            $password .= $characters[rand(0, $charactersLength - 1)];
        }

        $this->password = $password;
        $this->passwordConfirmation = $password;

        $this->dispatch('success', 'Password berhasil digenerate');
    }

    /**
     * Toggle permission for user
     */
    public function togglePermission($category, $permission)
    {
        $this->userPermissions[$category][$permission] = !$this->userPermissions[$category][$permission];
    }

    /**
     * Reset user permissions to default
     */
    public function resetPermissions()
    {
        foreach ($this->availablePermissions as $category => $permissions) {
            foreach ($permissions as $permission => $config) {
                $this->userPermissions[$category][$permission] = false;
            }
        }

        $this->dispatch('success', 'Permissions berhasil direset');
    }

    /**
     * Set all permissions to active
     */
    public function enableAllPermissions()
    {
        foreach ($this->availablePermissions as $category => $permissions) {
            foreach ($permissions as $permission => $config) {
                if ($config['enabled']) {
                    $this->userPermissions[$category][$permission] = true;
                }
            }
        }

        $this->dispatch('success', 'Semua permissions berhasil diaktifkan');
    }
}
