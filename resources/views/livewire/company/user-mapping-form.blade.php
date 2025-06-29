<div>
    @if ($showForm)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">User Company Mapping</h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-light-danger" wire:click="closeMapping">
                    <i class="bi bi-x"></i> Close
                </button>
            </div>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">User</label>
                    <select wire:model="user_id" class="form-control rounded" required>
                        <option value="">Pilih User</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('user_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block mb-1 font-medium">Company</label>
                    <select wire:model.live="company_id" class="form-control rounded" required>
                        <option value="">Pilih Company</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror

                    @if($shouldAutoSetDefaults)
                    <div class="alert alert-info mt-2">
                        <i class="bi bi-info-circle"></i>
                        Company belum memiliki Default Admin. User ini akan otomatis dijadikan Admin dan Default Admin.
                    </div>
                    @endif

                    @if(session('company_admin_info'))
                    <div class="alert alert-info mt-2">
                        <i class="bi bi-info-circle"></i>
                        {{ session('company_admin_info') }}
                    </div>
                    @endif

                    @if(config('app.debug') && $company_id)
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="debugCompanyStatus">
                            🐛 Debug Company Status
                        </button>
                    </div>
                    @endif

                    @if(session('debug'))
                    <div class="alert alert-secondary mt-2">
                        <small><strong>Debug:</strong> {{ session('debug') }}</small>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger mt-2">
                        <i class="bi bi-exclamation-triangle"></i>
                        {{ session('error') }}
                    </div>
                    @endif
                </div>
                <div>
                    <label class="block mb-1 font-medium">Status</label>
                    <select wire:model="status" class="form-control rounded" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    @error('status')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="block mb-1 font-medium">Admin</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="isAdmin" id="isAdminCheck"
                                @checked($isAdmin) @disabled($shouldAutoSetDefaults)>
                            <label class="form-check-label" for="isAdminCheck">
                                Jadikan sebagai Admin
                                @if($shouldAutoSetDefaults)
                                <small class="text-muted">(Otomatis karena Default Admin)</small>
                                @endif
                            </label>
                        </div>
                        @error('isAdmin')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="block mb-1 font-medium">Default Admin</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" wire:model="isDefaultAdmin"
                                id="isDefaultAdminCheck" @checked($isDefaultAdmin) @disabled($shouldAutoSetDefaults)>
                            <label class="form-check-label" for="isDefaultAdminCheck">
                                Jadikan sebagai Default Admin
                                @if($shouldAutoSetDefaults)
                                <small class="text-muted">(Otomatis karena Default Admin)</small>
                                @endif
                            </label>
                        </div>
                        @error('isDefaultAdmin')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                        @if($companyHasDefaultAdmin && !$this->isCurrentDefaultAdmin())
                        <div class="alert alert-warning mt-2">
                            <small><i class="bi bi-exclamation-triangle"></i> Company sudah memiliki Default Admin.
                                Hanya bisa ada 1 Default Admin per company.</small>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-light border px-5 py-2 rounded hover:bg-gray-100 transition"
                        wire:click="closeMapping">Batal</button>
                    <button type="submit"
                        class="btn btn-primary px-5 py-2 rounded hover:bg-orange-600 transition">Simpan</button>
                </div>
                @if (session()->has('success'))
                <div class="mt-2 text-green-600">{{ session('success') }}</div>
                @endif
            </form>
        </div>
    </div>
    @endif

    @if(auth()->user() && auth()->user()->hasRole('SuperAdmin'))
    <div class="mb-3">
        @if($company_id)
        <button type="button" class="btn btn-danger btn-sm me-2" wire:click="confirmClearMappingForCompany">
            <i class="bi bi-trash"></i> Clear Mapping untuk Company Ini
        </button>
        @endif
        <button type="button" class="btn btn-danger btn-sm" wire:click="confirmClearAllMapping">
            <i class="bi bi-trash"></i> Clear Semua Mapping
        </button>
    </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', function () {
        // Notifikasi default admin auto-set
        Livewire.on('company-default-admin-check', function(data) {
            if (data.hasDefaultAdmin === false) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Company Default Admin',
                        text: data.message,
                        showConfirmButton: true,
                        confirmButtonText: 'Mengerti',
                        timer: 5000
                    });
                } else {
                    alert('Info: ' + data.message);
                }
            }
        });

        // Notifikasi konflik default admin
        Livewire.on('default-admin-conflict', function(data) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Default Admin Conflict',
                    text: data.message,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                });
            } else {
                alert('Warning: ' + data.message);
            }
        });

        // Konfirmasi clear mapping
        Livewire.on('confirm-clear-mapping', function(data) {
            let text = data.all ? 'Semua mapping user di semua company akan dihapus. Lanjutkan?' : 'Semua mapping user untuk company ini akan dihapus. Lanjutkan?';
            Swal.fire({
                title: 'Konfirmasi Hapus Mapping',
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    if (data.all) {
                        @this.call('clearAllMapping');
                    } else {
                        @this.call('clearMappingForCompany', data.company_id);
                    }
                }
            });
        });

        // Notifikasi hasil clear mapping
        Livewire.on('mapping-cleared', function(data) {
            console.log('EVENT mapping-cleared:', data);
            Swal.fire({
                icon: data.error ? 'error' : 'success',
                title: data.error ? 'Gagal' : 'Sukses',
                text: data.message,
                timer: 4000
            });
        });
    });
    </script>
    @endpush
</div>