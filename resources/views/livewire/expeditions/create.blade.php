<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ekspedisi') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-semibold text-primary"><i class="bi bi-truck me-2"></i> Data Ekspedisi</h5>
                        <button wire:click="$dispatch('showCreateModal')" class="btn btn-primary shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Baru
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">Kode</th>
                                    <th scope="col">Nama</th>
                                    <th scope="col">Kontak</th>
                                    <th scope="col">Telepon</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $row)
                                    <tr>
                                        <td>{{ $row->kode }}</td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ $row->contact_person ?? '-' }}</td>
                                        <td>{{ $row->phone_number ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $row->status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($row->status) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button wire:click="$dispatch('showEditModal', [@js($row->id)])" class="btn btn-sm btn-outline-primary shadow-sm">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button onclick="confirmDelete('{{ $row->id }}')" class="btn btn-sm btn-outline-danger shadow-sm">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                                {{-- <button wire:click="$dispatch('delete', [@js($row->id)])" class="btn btn-sm btn-outline-danger shadow-sm">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button> --}}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4">Tidak ada data ekspedisi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{-- {{ $data->links('vendor.livewire.bootstrap') }} --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="expeditionModal" tabindex="-1" aria-labelledby="expeditionModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="expeditionModalLabel"><i class="bi bi-truck me-2"></i> {{ $modalId ? 'Edit Ekspedisi' : 'Tambah Ekspedisi' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="closeModal"></button>
                </div>
                <form wire:submit.prevent="{{ $modalId ? 'update' : 'create' }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="kode" class="form-label">Kode</label>
                            <input wire:model.debounce.800ms="kode" type="text" class="form-control @error('kode') is-invalid @enderror" id="kode" placeholder="Kode Ekspedisi">
                            @error('kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama</label>
                            <input wire:model.debounce.800ms="name" type="text" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Nama Ekspedisi">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="contact_person" class="form-label">Kontak Person</label>
                            <input wire:model.debounce.800ms="contact_person" type="text" class="form-control @error('contact_person') is-invalid @enderror" id="contact_person" placeholder="Nama Kontak">
                            @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Nomor Telepon</label>
                            <input wire:model.debounce.800ms="phone_number" type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" placeholder="Nomor Telepon">
                            @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea wire:model.debounce.800ms="address" class="form-control @error('address') is-invalid @enderror" id="address" placeholder="Alamat"></textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea wire:model.debounce.800ms="description" class="form-control @error('description') is-invalid @enderror" id="description" placeholder="Deskripsi"></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select wire:model.debounce.800ms="status" class="form-select @error('status') is-invalid @enderror" id="status">
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeModal"><i class="bi bi-x-circle me-1"></i> Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-{{ session('type', 'info') }} alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('showCreateModal', () => {
            const modal = new bootstrap.Modal(document.getElementById('expeditionModal'));
            modal.show();
        });

        Livewire.on('showEditModal', (id) => {
            Livewire.dispatch('editShowModal', id);
            const modal = new bootstrap.Modal(document.getElementById('expeditionModal'));
            modal.show();
        });

        Livewire.on('closeModal', () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('expeditionModal'));
            if (modal) {
                modal.hide();
            }
        });

        window.confirmDelete = (id) => {
            Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Data ekspedisi akan dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Hanya dispatch event delete ke Livewire setelah konfirmasi
                    Livewire.dispatch('delete', [id]);
                }
            })
        };

        // document.addEventListener('delete', event => {
        //     console.log('Event delete di JavaScript terpanggil:', event.detail);
        //     Swal.fire({
        //         title: 'Apakah anda yakin?',
        //         text: "Data ekspedisi akan dihapus!",
        //         icon: 'warning',
        //         showCancelButton: true,
        //         confirmButtonColor: '#d33',
        //         cancelButtonColor: '#3085d6',
        //         confirmButtonText: 'Ya, hapus!',
        //         cancelButtonText: 'Batal'
        //     }).then((result) => {
        //         console.log('Hasil konfirmasi:', result);
        //         if (result.isConfirmed) {
        //             Livewire.dispatch('delete', event.detail);
        //             console.log('Event delete di-dispatch ke Livewire:', event.detail);
        //         }
        //     })
        // });

        // Livewire.on('delete', (id) => {
        //     Swal.fire({
        //         title: 'Apakah anda yakin?',
        //         text: "Data ekspedisi akan dihapus!",
        //         icon: 'warning',
        //         showCancelButton: true,
        //         confirmButtonColor: '#d33',
        //         cancelButtonColor: '#3085d6',
        //         confirmButtonText: 'Ya, hapus!'
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             Livewire.dispatch('delete', id);
        //         }
        //     })
        // });

        Livewire.on('show-alert', event => {
            const alertDiv = document.querySelector('.alert');
            if (alertDiv) {
                const bsAlert = new bootstrap.Alert(alertDiv);
                setTimeout(() => {
                    bsAlert.close();
                }, 2000);
            }
        });
    });
</script>