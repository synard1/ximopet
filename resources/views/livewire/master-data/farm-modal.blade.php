<div>
    <div class="modal fade {{ $isOpen ? 'show' : '' }}" id="kt_modal_farm" tabindex="-1" aria-hidden="true"
        style="display: {{ $isOpen ? 'block' : 'none' }};">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{{ $isEdit ? 'Edit Farm' : 'Tambah Farm' }}</h2>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close" wire:click="closeModalFarm">
                        {!! getIcon('cross', 'fs-2x') !!}
                    </div>
                </div>

                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form wire:submit.prevent="storeFarm" id="kt_modal_farm_form" class="form">
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Kode Farm</label>
                            <input type="text" wire:model.live="code"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Masukkan kode farm" {{
                                $isEdit ? 'readonly disable' : '' }} />
                            @error('code') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Nama Farm</label>
                            <input type="text" wire:model.live="name"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Masukkan nama farm" />
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Alamat</label>
                            <textarea wire:model.live="address" class="form-control form-control-solid mb-3 mb-lg-0"
                                rows="3" placeholder="Masukkan alamat farm"></textarea>
                            @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Nomor Telepon</label>
                            <input type="text" wire:model.live="phone_number"
                                class="form-control form-control-solid mb-3 mb-lg-0"
                                placeholder="Masukkan nomor telepon" />
                            @error('phone_number') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Kontak Person</label>
                            <input type="text" wire:model.live="contact_person"
                                class="form-control form-control-solid mb-3 mb-lg-0"
                                placeholder="Masukkan nama kontak person" />
                            @error('contact_person') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" wire:click="closeModalFarm">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">{{ $isEdit ? 'Update' : 'Simpan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($isOpen)
    <div class="modal-backdrop fade show"></div>
    @endif
</div>