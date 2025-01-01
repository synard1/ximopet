<div>
    <div class="modal fade" id="penjualanTernak" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-labelledby="penjualanTernakLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="penjualanTernakLabel">{{ $modalTitle }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" id="tanggal_harian" wire:model="tanggal_harian">
                                    <label for="tanggal_harian">Tanggal Trx Harian</label>
                                    @error('tanggal_harian') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" id="tanggal" wire:model="tanggal">
                                    <label for="tanggal">Tanggal Penjualan</label>
                                    @error('tanggal') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    @if($rekanan_id != null)
                                    <select class="form-select" id="rekanan_id" wire:model="rekanan_id" readonly disabled>
                                        @foreach ($rekanans as $rekanan)
                                            <option value="{{ $rekanan->id }}" @if($rekanan_id == $rekanan->id) selected @endif>{{ $rekanan->nama }}</option>
                                        @endforeach
                                    </select>
                                    @else
                                        <select class="form-select" id="rekanan_id" wire:model="rekanan_id">
                                            <option value="">Pilih Rekanan</option>
                                            @foreach($rekanans as $rekanan)
                                                <option value="{{ $rekanan->id }}">{{ $rekanan->nama }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <label for="rekanan_id">Rekanan</label>
                                    @error('rekanan_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="faktur" wire:model="faktur">
                                    <label for="faktur">No. Faktur</label>
                                    @error('faktur') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="tipe_transaksi" wire:model="tipe_transaksi">
                                        <option value="">Pilih Tipe Transaksi</option>
                                        <option value="penjualan">Penjualan</option>
                                        <option value="pembelian">Pembelian</option>
                                    </select>
                                    <label for="tipe_transaksi">Tipe Transaksi</label>
                                    @error('tipe_transaksi') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div> --}}
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="ternak_jual" wire:model="ternak_jual">
                                    <label for="ternak_jual">Jumlah</label>
                                    @error('ternak_jual') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="harga_jual" wire:model="harga_jual">
                                    <label for="harga_jual">Harga Jual</label>
                                    @error('harga_jual') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="total_berat" wire:model="total_berat" inputmode="decimal" step="0.01">
                                    <label for="total_berat">Berat (kg)</label>
                                    @error('total_berat') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                        </div>

                        <div class="row g-3">
                            
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    @if($isEdit)
                                    <select class="form-select" id="farm_id" wire:model="farm_id" readonly disabled>
                                        @if($isEdit)
                                            @foreach ($farms as $farm)
                                            <option value="{{ $farm->id }}" @if($farm_id == $farm->id) selected @endif>{{ $farm->nama }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @else
                                        <select class="form-select" id="farm_id" wire:model="farm_id">
                                            <option value="">Pilih Farm</option>
                                            @foreach($farms as $farm)
                                                <option value="{{ $farm->id }}">{{ $farm->nama }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    <label for="farm_id">Farm</label>
                                    @error('farm_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    @if($isEdit)
                                    <select class="form-select" id="kandang_id" wire:model="kandang_id" readonly disabled>
                                        @if($isEdit)
                                            @foreach ($kandangs as $kandang)
                                            <option value="{{ $kandang->id }}" @if($kandang_id == $farm->id) selected @endif>{{ $kandang->nama }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @else
                                        <select class="form-select" id="kandang_id" wire:model="kandang_id">
                                            <option value="">Pilih Kandang</option>
                                            @foreach($kandangs as $kandang)
                                                <option value="{{ $kandang->id }}">{{ $kandang->nama }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <label for="kandang_id">Kandang</label>
                                    @error('kandang_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="status" wire:model="status">
                                        <option value="">Pilih Status</option>
                                        <option value="selesai">Selesai</option>
                                        <option value="proses">Proses</option>
                                        <option value="batal">Batal</option>
                                    </select>
                                    <label for="status">Status</label>
                                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div> --}}
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="tanggal_beli" wire:model="tanggal_beli" readonly>
                                    <label for="tanggal_beli">Tanggal Beli</label>
                                    @error('tanggal_beli') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="harga_beli" wire:model="harga_beli" readonly>
                                    <label for="harga_beli">Harga Beli</label>
                                    @error('harga_beli') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            {{-- <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="harga_jual" wire:model="harga_jual">
                                    <label for="harga_jual">Harga Jual</label>
                                    @error('harga_jual') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="qty" wire:model="qty">
                                    <label for="qty">Quantity</label>
                                    @error('qty') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div> --}}
                            
                            <div class="col-md-2">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="umur" wire:model="umur" readonly>
                                    <label for="umur">Umur (hari)</label>
                                    @error('umur') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Simpan' }}</button>
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
        ternaksData: [],
        farmsData: [],
        kandangsData: [],
        // tanggalInput : document.getElementById('tanggal'),
        selectedDate : '',
        tanggalInput : '',

        init() {
            this.ternaksData = @json($this->ternaks);
            this.farmsData = @json($this->farms);
            this.kandangsData = @json($this->kandangs);

            document.addEventListener('livewire:init', () => {
                this.initEventListeners();
                console.log('Ternaks Data:');
                console.table(this.ternaksData);
                console.log('Kandang Data:');
                console.table(this.kandangsData);
            });
        },

        initEventListeners() {
            // const kandangSelect = document.getElementById('kandang_id');
            // if (kandangSelect) {
            //     kandangSelect.addEventListener('change', (e) => {
            //         const kandangId = e.target.value;
            //         const selectedKandang = this.kandangsData.find(k => k.id == kandangId);
            //         console.log('Selected Kandang:', selectedKandang);
            //     });
            // }
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
                    console.log('Selected date:', this.tanggalInput);
                    // Trigger recalculation of age when date changes
                    const kandangId = document.getElementById('kandang_id').value;
                    if (kandangId) {
                        this.fillTernakData(kandangId);
                    }
                });
            }
        },

        updateKandangOptions(farmId) {
            const kandangSelect = document.getElementById('kandang_id');
            console.log('farm : '+farmId);
            
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
                const ternak = this.ternaksData.find(t => t.id == selectedKandang.kelompok_ternak_id);
                if (ternak) {
                    this.setInputValue('tanggal_beli', this.formatDate(ternak.start_date));
                    this.setInputValue('harga_beli', ternak.hpp);
                    this.setInputValue('umur', this.calculateAge(ternak.start_date,this.tanggalInput));

                    
                    // Dispatch custom event to update Livewire component
                    // this.dispatchLivewireEvent('ternakDataFilled', {
                    //     tanggal_beli: ternak.tanggal_beli,
                    //     harga_beli: ternak.harga_beli,
                    // });
                }
            }
        },

        setInputValue(id, value) {
            const input = document.getElementById(id);
            if (input) {
                input.value = value;
            }
        },

        calculateAge(dateString,tanggalInput) {
            const birthDate = new Date(dateString);
            const today = new Date();
            console.log('tanggal : '+tanggalInput);
            

            if(tanggalInput){
                const endDate = new Date(tanggalInput);
                const diffTime = Math.abs(endDate  - birthDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                return diffDays;
            }else{
                const diffTime = Math.abs(today - birthDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                return diffDays;
            }

        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
    };

    PenjualanTernak.init();

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('penjualanTernak');
        modal.addEventListener('hidden.bs.modal', function () {
            Livewire.dispatch('resetForm');
        });
    });
</script>
@endpush