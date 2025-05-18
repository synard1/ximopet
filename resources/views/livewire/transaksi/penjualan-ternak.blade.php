<div>
    <div class="modal fade" id="penjualanTernak" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-labelledby="penjualanTernakLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                {{-- Modal Header --}}
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="penjualanTernakLabel">
                        <i class="bi bi-cart-check me-2"></i>{{ $modalTitle }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- Modal Body --}}
                <div class="modal-body">
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
                                    {{-- <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="tanggal_harian" wire:model="tanggal_harian">
                                            <label for="tanggal_harian">Tanggal Trx Harian</label>
                                            @error('tanggal_harian') 
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div> --}}
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="date" class="form-control @error('tanggal') is-invalid @enderror" 
                                                   id="tanggal" wire:model.live="tanggal">
                                            <label for="tanggal">Tanggal Penjualan</label>
                                            @error('tanggal') 
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            @if($partner_id != null)
                                                <select class="form-select @error('partner_id') is-invalid @enderror" 
                                                        id="partner_id" wire:model.live="partner_id" readonly disabled>
                                                    @foreach ($partners as $partner)
                                                        <option value="{{ $partner->id }}" @if($partner_id == $partner->id) selected @endif>
                                                            {{ $partner->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <select class="form-select @error('partner_id') is-invalid @enderror" 
                                                        id="partner_id" wire:model.live="partner_id">
                                                    <option value="">Pilih Pelanggan</option>
                                                    @foreach($partners as $partner)
                                                        <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                            <label for="partner_id">Pelanggan</label>
                                            @error('partner_id') 
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('faktur') is-invalid @enderror" 
                                                   id="faktur" wire:model="faktur">
                                            <label for="faktur">No. Faktur</label>
                                            @error('faktur') 
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
                                                   id="ternak_jual" wire:model="ternak_jual" 
                                                   {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
                                            <label for="ternak_jual">Jumlah</label>
                                            @error('ternak_jual') 
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="number" class="form-control @error('harga_jual') is-invalid @enderror" 
                                                   id="harga_jual" wire:model="harga_jual" 
                                                   {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
                                            <label for="harga_jual">Harga Jual</label>
                                            @error('harga_jual') 
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control @error('total_berat') is-invalid @enderror" 
                                                   id="total_berat" wire:model="total_berat" inputmode="decimal" step="0.01" 
                                                   {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
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
                                    <i class="bi bi-geo-alt me-2"></i>Informasi Lokasi
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            @if($isEdit)
                                                <select class="form-select @error('farm_id') is-invalid @enderror" 
                                                        id="farm_id" wire:model="farm_id" readonly disabled>
                                                    @foreach ($farms as $farm)
                                                        <option value="{{ $farm->id }}" @if($farm_id == $farm->id) selected @endif>
                                                            {{ $farm->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <select class="form-select @error('farm_id') is-invalid @enderror" 
                                                        id="farm_id" wire:model="farm_id" 
                                                        {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
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
                                                <select class="form-select @error('kandang_id') is-invalid @enderror" 
                                                        id="kandang_id" wire:model="kandang_id" readonly disabled>
                                                    @foreach ($kandangs as $kandang)
                                                        <option value="{{ $kandang->id }}" @if($kandang_id == $kandang->id) selected @endif>
                                                            {{ $kandang->nama }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <select class="form-select @error('kandang_id') is-invalid @enderror" 
                                                        id="kandang_id" wire:model.live="kandang_id" 
                                                        {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
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
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="tanggal_beli" wire:model="tanggal_beli" readonly>
                                            <label for="tanggal_beli">Tanggal Beli</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="harga_beli" wire:model="harga_beli" readonly>
                                            <label for="harga_beli">Harga Beli</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="umur" wire:model="umur" readonly>
                                            <label for="umur">Umur (hari)</label>
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
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i>Tutup
                            </button>
                            <button type="submit" class="btn btn-primary" {{ !$requiredFieldsFilled ? 'disabled' : '' }}>
                                <i class="bi bi-save me-1"></i>{{ $isEdit ? 'Update' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const PenjualanTernak = {
        livestocksData: [],
        farmsData: [],
        kandangsData: [],
        selectedDate: '',
        tanggalInput: '',

        init() {
            this.livestocksData = @json($this->livestocks);
            this.farmsData = @json($this->farms);
            this.kandangsData = @json($this->kandangs);

            document.addEventListener('livewire:init', () => {
                this.initEventListeners();
            });
        },

        initEventListeners() {
            const kandangSelect = document.getElementById('kandang_id');
            if (kandangSelect) {
                kandangSelect.addEventListener('change', (e) => {
                    const kandangId = e.target.value;
                    this.fillTernakData(kandangId);
                });
            }

            const farmSelect = document.getElementById('farm_id');
            if (farmSelect) {
                farmSelect.addEventListener('change', (e) => {
                    const farmId = e.target.value;
                    this.updateKandangOptions(farmId);
                });
            }

            const tanggalInput = document.getElementById('tanggal');
            if (tanggalInput) {
                tanggalInput.addEventListener('change', (e) => {
                    this.tanggalInput = e.target.value;
                    const kandangId = document.getElementById('kandang_id').value;
                    if (kandangId) {
                        this.fillTernakData(kandangId);
                    }
                });
            }
        },

        updateKandangOptions(farmId) {
            const kandangSelect = document.getElementById('kandang_id');
            if (kandangSelect) {
                kandangSelect.innerHTML = '<option value="">Pilih Kandang</option>';
                const filteredKandangs = this.kandangsData.filter(k => k.farm_id == farmId);
                filteredKandangs.forEach(kandang => {
                    const option = new Option(kandang.nama, kandang.id);
                    kandangSelect.add(option);
                });
            }
        },

        fillTernakData(kandangId) {
            const selectedKandang = this.kandangsData.find(k => k.id == kandangId);
            if (selectedKandang) {
                const ternak = this.livestocksData.find(t => t.id == selectedKandang.kelompok_ternak_id);
                if (ternak) {
                    this.setInputValue('tanggal_beli', this.formatDate(ternak.start_date));
                    this.setInputValue('harga_beli', ternak.hpp);
                    this.setInputValue('umur', this.calculateAge(ternak.start_date, this.tanggalInput));
                }
            }
        },

        setInputValue(id, value) {
            const input = document.getElementById(id);
            if (input) {
                input.value = value;
            }
        },

        calculateAge(dateString, tanggalInput) {
            const birthDate = new Date(dateString);
            const endDate = tanggalInput ? new Date(tanggalInput) : new Date();
            const diffTime = Math.abs(endDate - birthDate);
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toISOString().split('T')[0];
        },
    };

    PenjualanTernak.init();

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('penjualanTernak');
        modal.addEventListener('hidden.bs.modal', function () {
            Livewire.dispatch('resetForm');
        });
    });
</script>
@endpush