<div class="modal fade" id="kt_modal_new_ekspedisi" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Data Ekspedisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="ekspedisiForm" class="form">
                    <div class="px-4">
                        @foreach ([
                            'kode_ekspedisi' => 'Kode Ekspedisi',
                            'name' => 'Nama Ekspedisi',
                            'alamat' => 'Alamat Ekspedisi',
                            'telp' => 'Telp',
                            'pic' => 'Contact Person',
                            'telp_pic' => 'Telp. Contact Person',
                            'email' => 'Email'
                        ] as $field => $label)
                            <div class="mb-3">
                                <label class="fw-semibold">{{ $label }}</label>
                                <input type="{{ $field === 'email' ? 'email' : 'text' }}" wire:model="{{ $field }}"
                                    class="form-control @error($field) is-invalid @enderror" placeholder="{{ $label }}">
                                @error($field)
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="storeEkspedisi()">Save changes</button>
            </div>
        </div>
    </div>
</div>
