<div>
    <!-- Modal -->
    <div class="modal fade {{ $isOpen ? 'show' : '' }}" id="kt_modal_add_kandang" tabindex="-1" aria-hidden="true"
        style="display: {{ $isOpen ? 'block' : 'none' }};">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">{{ $isEdit ? 'Edit Kandang' : 'Tambah Kandang' }}</h2>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close" wire:click="closeModalFarm">
                        {!! getIcon('cross', 'fs-2x') !!}
                    </div>
                </div>

                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form wire:submit.prevent="store">
                        @if(!$isEdit)
                        <div class="mb-5">
                            <label class="form-label required">Farm</label>
                            <select wire:model="farm_id" class="form-select">
                                <option value="">Pilih Farm</option>
                                @foreach($farms as $farm)
                                <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                @endforeach
                            </select>
                            @error('farm_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-5">
                            <label class="form-label required">Kode Kandang</label>
                            <input type="text" wire:model="code" class="form-control"
                                placeholder="Masukkan kode kandang" />
                            @error('code') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        @endif

                        <div class="mb-5">
                            <label class="form-label required">Nama Kandang</label>
                            <input type="text" wire:model="name" class="form-control"
                                placeholder="Masukkan nama kandang" />
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-5">
                            <label class="form-label required">Kapasitas</label>
                            <input type="number" wire:model="capacity" class="form-control"
                                placeholder="Masukkan kapasitas kandang" />
                            @error('capacity') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-5">
                            <label class="form-label required">Status</label>
                            <select wire:model="status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                            @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-light me-3" wire:click="closeModalFarm">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Simpan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('showModal', () => {
                $('#kt_modal_add_kandang').modal('show');
            });

            Livewire.on('hideModal', () => {
                $('#kt_modal_add_kandang').modal('hide');
            });

            $('#kt_modal_add_kandang').on('hidden.bs.modal', function () {
                Livewire.dispatch('closeModal');
            });
        });
    </script> --}}
    @endpush
    @if($isOpen)
    <div class="modal-backdrop fade show"></div>
    @endif
</div>