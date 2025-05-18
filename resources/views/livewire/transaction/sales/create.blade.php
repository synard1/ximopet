<div id="sales-form-container">
    @if ($showForm)
    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}">
        {{-- Transaction Information Section --}}
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>Informasi Transaksi
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal"
                                wire:model.live="tanggal">
                            <label for="tanggal">Tanggal Penjualan</label>
                            @error('tanggal')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            @if($pelanggan_id != null)
                            <select class="form-select @error('pelanggan_id') is-invalid @enderror" id="pelanggan_id"
                                wire:model.live="pelanggan_id" readonly disabled>
                                @foreach ($partners as $partner)
                                <option value="{{ $partner->id }}" @if($pelanggan_id==$partner->id) selected @endif>
                                    {{ $partner->name }}
                                </option>
                                @endforeach
                            </select>
                            @else
                            <select class="form-select @error('pelanggan_id') is-invalid @enderror" id="pelanggan_id"
                                wire:model.live="pelanggan_id">
                                <option value="">Pilih Pelanggan</option>
                                @foreach($partners as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                                @endforeach
                            </select>
                            @endif
                            <label for="pelanggan_id">Pelanggan</label>
                            @error('pelanggan_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control @error('invoice') is-invalid @enderror" id="invoice"
                                wire:model.live="invoice">
                            <label for="invoice">Invoice</label>
                            @error('invoice')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales Details Section --}}
        <div class="card mb-4 {{ !$requiredFieldsFilled ? 'opacity-50' : '' }}">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-cart me-2"></i>Detail Penjualan
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="number" class="form-control @error('ternak_jual') is-invalid @enderror"
                                id="ternak_jual" wire:model="ternak_jual" {{ !$requiredFieldsFilled ? 'disabled' : ''
                                }}>
                            <label for="ternak_jual">Jumlah</label>
                            @error('ternak_jual')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="number" class="form-control @error('harga_jual') is-invalid @enderror"
                                id="harga_jual" wire:model="harga_jual" {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
                            <label for="harga_jual">Harga Jual / Kg</label>
                            @error('harga_jual')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control @error('total_berat') is-invalid @enderror"
                                id="total_berat" wire:model="total_berat" inputmode="decimal" step="0.01" {{
                                !$requiredFieldsFilled ? 'disabled' : '' }}>
                            <label for="total_berat">Berat (kg)</label>
                            @error('total_berat')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Location Information Section --}}
        <div class="card mb-4 {{ !$requiredFieldsFilled ? 'opacity-50' : '' }}">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-geo-alt me-2"></i>Informasi Peternakan Ayam
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            @if($isEdit)
                            <select class="form-select @error('farm_id') is-invalid @enderror" id="farm_id"
                                wire:model="farm_id" readonly disabled>
                                @foreach ($farms as $farm)
                                <option value="{{ $farm->id }}" @if($farm_id==$farm->id) selected @endif>
                                    {{ $farm->name }}
                                </option>
                                @endforeach
                            </select>
                            @else
                            <select class="form-select @error('farm_id') is-invalid @enderror" id="farm_id"
                                wire:model="farm_id" {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
                                <option value="">Pilih Farm</option>
                                @foreach($farms as $farm)
                                <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                @endforeach
                            </select>
                            @endif
                            <label for="farm_id">Farm</label>
                            @error('farm_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            @if($isEdit)
                            <select class="form-select @error('kandang_id') is-invalid @enderror" id="kandang_id"
                                wire:model="kandang_id" readonly disabled>
                                @foreach ($kandangs as $kandang)
                                <option value="{{ $kandang->id }}" @if($kandang_id==$kandang->id) selected @endif>
                                    {{ $kandang->nama }}
                                </option>
                                @endforeach
                            </select>
                            @else
                            <select class="form-select @error('kandang_id') is-invalid @enderror" id="kandang_id"
                                wire:model.live="kandang_id" {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
                                <option value="">Pilih Kandang</option>
                                @foreach($kandangs as $kandang)
                                <option value="{{ $kandang->id }}">{{ $kandang->nama }}</option>
                                @endforeach
                            </select>
                            @endif
                            <label for="kandang_id">Kandang</label>
                            @error('kandang_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Information Section --}}
        <div class="card mb-4 {{ !$requiredFieldsFilled ? 'opacity-50' : '' }}">
            <div class="card-header bg-light card-title">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>Informasi Tambahan
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="tanggal_beli" wire:model="tanggal_beli"
                                readonly>
                            <label for="tanggal_beli">Tanggal Beli</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="harga_beli" wire:model="harga_beli" readonly>
                            <label for="harga_beli">Harga Beli</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="estimated_cost" wire:model="estimated_cost"
                                readonly>
                            <label for="estimated_cost">Estimasi Cost / Ekor</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="number" class="form-control" id="umur" wire:model="umur" readonly>
                            <label for="umur">Umur Jual (hari)</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(!$requiredFieldsFilled)
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            Silakan lengkapi informasi transaksi terlebih dahulu (Tanggal Penjualan, Pelanggan, dan Invoice)
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-light" wire:click='cancel'>
                <i class="bi bi-x-circle me-1"></i>Batal
            </button>
            <button type="submit" class="btn btn-primary" {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
                <i class="bi bi-save me-1"></i>{{ $isEdit ? 'Update' : 'Simpan' }}
            </button>
        </div>
    </form>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('resetForm', () => {
            // Reset form jika diperlukan
        });
    });
</script>
@endpush