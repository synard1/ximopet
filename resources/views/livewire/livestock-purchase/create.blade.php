<div>
    @if ($showForm)

    <!-- Readonly/Locked Status Alert -->
    @if(in_array($status, ['in_coop', 'complete']) && !$tempAuthEnabled)
    <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <i class="ki-outline ki-lock fs-2 me-3 text-warning"></i>
            <div>
                <div class="fw-bold">Form dalam Mode Read-Only</div>
                <div class="text-muted small">Status: {{ ucfirst($status) }} - Data tidak dapat diubah tanpa autorisasi
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-warning" wire:click="requestTempAuth">
            <i class="ki-outline ki-shield-search fs-4 me-1"></i>
            Minta Autorisasi
        </button>
    </div>
    @endif

    <form wire:submit.prevent="save">
        {{--
        <!-- Header Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-cart-plus me-2"></i>
                    Fitur Pembelian Livestock
                </h5>
            </div>
        </div> --}}

        <!-- Main Form Section -->
        <div class="row">
            <!-- Left Column - Form Fields -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Field Wajib -->
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                @if($this->isReadonly())
                                {{-- Readonly mode: show plain text, no datepicker --}}
                                <input type="text" class="form-control-plaintext" value="{{ $date }}" readonly required>
                                @else
                                {{-- Editable mode: show datepicker --}}
                                <input wire:model="date" id="date" class="form-control" placeholder="Tanggal" x-data
                                    x-init="flatpickr($el, {
                                            enableTime: true,
                                            dateFormat: 'Y-m-d',
                                            defaultDate: '{{ $date }}',
                                        })">
                                @endif
                                <x-input.error for="date" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select wire:model.live="supplier_id" class="form-select" @if($this->isDisabled())
                                    disabled required @endif>
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @if($supplier_id==$vendor->id) selected @endif>
                                        {{ $vendor->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <x-input.error for="supplier_id" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Farm <span class="text-danger">*</span></label>
                                <select wire:model.live="farm_id" class="form-select" @if($this->isDisabled()) disabled
                                    required @endif>
                                    <option value="">-- Pilih Farm --</option>
                                    @foreach ($farms as $farm)
                                    <option value="{{ $farm->id }}" @if($farm_id==$farm->id) selected @endif>
                                        {{ $farm->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <x-input.error for="farm_id" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Kandang <span class="text-danger">*</span></label>
                                <select wire:model.live="coop_id" class="form-select" @if(!$farm_id ||
                                    $this->isDisabled()) disabled required @endif>
                                    <option value="">-- Pilih Kandang --</option>
                                    @foreach ($coops as $coop)
                                    <option value="{{ $coop->id }}" @if($coop_id==$coop->id) selected @endif>
                                        {{ $coop->name }} (Sisa: {{ $coop->capacity - $coop->quantity }})
                                    </option>
                                    @endforeach
                                </select>
                                <x-input.error for="coop_id" />
                            </div>

                            <!-- Field Opsional -->
                            <div class="col-md-6">
                                <label class="form-label">Nomor Invoice <span class="text-danger">*</span></label>
                                <input type="text" wire:model.live="invoice_number" class="form-control"
                                    placeholder="Masukkan nomor invoice" @if($this->isInvoiceNumberReadonly()) readonly
                                required
                                @endif>
                                <x-input.error for="invoice_number" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nama Batch</label>
                                <input type="text" wire:model="batch_name" class="form-control"
                                    placeholder="Masukkan nama batch atau kosongkan untuk otomatis"
                                    @if($this->isBatchNameReadonly()) readonly @endif>
                                <x-input.error for="batch_name" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Ekspedisi</label>
                                <select wire:model="expedition_id" class="form-select"
                                    @if($this->isExpeditionDisabled()) disabled
                                    @endif>
                                    <option value="">-- Pilih Ekspedisi --</option>
                                    @foreach ($expeditions as $expedition)
                                    <option value="{{ $expedition->id }}">{{ $expedition->name }}</option>
                                    @endforeach
                                </select>
                                <x-input.error for="expedition_id" />
                                <!-- Debug Info -->
                                @if(config('app.debug'))
                                <small class="text-muted">
                                    Status: {{ $this->getCurrentStatus() }}, Disabled: {{ $this->isExpeditionDisabled()
                                    ? 'Yes' : 'No' }},
                                    Expedition ID: {{ $expedition_id ?? 'null' }}
                                </small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Biaya Ekspedisi</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" step="0.01" wire:model.live="expedition_fee"
                                        class="form-control" placeholder="0.00" @if($this->isExpeditionReadonly())
                                    readonly
                                    @endif>
                                </div>
                                <x-input.error for="expedition_fee" />
                                <!-- Debug Info -->
                                @if(config('app.debug'))
                                <small class="text-muted">
                                    Status: {{ $this->getCurrentStatus() }}, Readonly: {{ $this->isExpeditionReadonly()
                                    ? 'Yes' : 'No' }},
                                    Expedition Fee: {{ $expedition_fee ?? 'null' }}
                                </small>
                                @endif
                            </div>

                            <!-- Keterangan -->
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <textarea wire:model="notes" class="form-control" rows="3"
                                    placeholder="Catatan tambahan..."
                                    @if($this->isNotesReadonly()) readonly @endif></textarea>
                                <x-input.error for="notes" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Summary -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-semibold">Ringkasan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small text-muted">TOTAL:</label>
                                <div class="form-control-plaintext fw-bold">Rp {{
                                    number_format($this->total['sub_total'], 0, ',', '.') }}</div>
                            </div>
                            {{-- <div class="col-12">
                                <label class="form-label small text-muted">DISKON:</label>
                                <div class="form-control-plaintext">Rp {{ number_format($this->total['discount'], 0,
                                    ',', '.') }}</div>
                            </div> --}}
                            <div class="col-12">
                                <label class="form-label small text-muted">BIAYA EKSPEDISI:</label>
                                <div class="form-control-plaintext">Rp {{ number_format($this->total['expedition_fee'],
                                    0, ',', '.') }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted">TOTAL + EKSPEDISI:</label>
                                <div class="form-control-plaintext fw-bold text-primary">Rp {{
                                    number_format($this->total['total'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php $canInputItems = $date && $supplier_id && $farm_id && $coop_id; @endphp

        <!-- Alert Informasi -->
        @if(!$canInputItems)
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Lengkapi informasi pembelian terlebih dahulu</strong><br>
            <small class="text-muted">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <b>Mandatory:</b> Mohon lengkapi <b>Tanggal</b>, <b>Supplier</b>, <b>Farm</b>, <b>Kandang</b>, <b>No
                    Invoice</b> terlebih dahulu sebelum menambah detail livestock.
            </small>
        </div>
        @endif

        <!-- Alert untuk status complete yang memerlukan ekspedisi -->
        @if($this->getCurrentStatus() === 'complete' && (empty($expedition_id) || empty($expedition_fee)))
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Detail Ekspedisi Diperlukan</strong><br>
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                <b>Status Complete:</b> Untuk status complete, <b>Ekspedisi</b> dan <b>Biaya Ekspedisi</b> harus diisi.
                Silakan lengkapi informasi ekspedisi di atas.
            </small>
        </div>
        @endif

        <!-- Debug Info for Expedition Fields -->
        @if(config('app.debug'))
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Debug Info - Expedition Fields</strong><br>
            <small class="text-muted">
                Current Status: <b>{{ $this->getCurrentStatus() }}</b><br>
                Expedition Disabled: <b>{{ $this->isExpeditionDisabled() ? 'Yes' : 'No' }}</b><br>
                Expedition Readonly: <b>{{ $this->isExpeditionReadonly() ? 'Yes' : 'No' }}</b><br>
                Expedition ID: <b>{{ $expedition_id ?? 'null' }}</b><br>
                Expedition Fee: <b>{{ $expedition_fee ?? 'null' }}</b>
            </small>
        </div>
        @endif

        <!-- Detail Livestock Section -->
        @if($canInputItems)
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-list-ul me-2"></i>
                    Detail Surat Pesanan
                </h6>
                <div>
                    @if (count($items) < $maxItems && !$this->isDisabled())
                        <button type="button" wire:click="addItem" class="btn btn-light btn-sm me-2">
                            <i class="bi bi-plus-circle me-1"></i> + Tambah
                        </button>
                        @endif
                        @if(!$this->isDisabled() && count($items) > 0)
                        <button type="button" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-trash me-1"></i> Hapus
                        </button>
                        @endif
                </div>
            </div>
            <div class="card-body p-0">
                @if(count($items) > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Livestock</th>
                                <th>Strains</th>
                                <th>Satuan</th>
                                <th>Jml</th>
                                <th>Harga (Rp)</th>
                                <th>Berat (gram)</th>
                                <th>Sub Total (Rp)</th>
                                <th width="50">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                            <tr>
                                <td>
                                    <select class="form-select form-select-sm"
                                        wire:model="items.{{ $index }}.livestock_strain_id" @if($this->isDisabled())
                                        disabled @endif>
                                        <option value="">-- Pilih Strains --</option>
                                        @foreach ($strains as $strain)
                                        <option value="{{ $strain->id }}">{{ $strain->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input.error for="items.{{ $index }}.livestock_strain_id" />
                                </td>
                                <td>
                                    <select class="form-select form-select-sm"
                                        wire:model="items.{{ $index }}.livestock_strain_standard_id"
                                        @if($this->isDisabled()) disabled @endif>
                                        <option value="">-- Pilih Standar --</option>
                                        @foreach ($standardStrains as $standardStrain)
                                        <option value="{{ $standardStrain->id }}">{{
                                            $standardStrain->livestock_strain_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm"
                                        wire:model.live="items.{{ $index }}.price_type" @if($this->isDisabled())
                                        disabled
                                        @endif>
                                        <option value="">-- Tipe --</option>
                                        <option value="per_unit">Per Ekor</option>
                                        <option value="total">Total</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="1" wire:model.live="items.{{ $index }}.quantity"
                                        class="form-control form-control-sm" placeholder="0" @if($this->isReadonly())
                                    readonly @endif />
                                    <x-input.error for="items.{{ $index }}.quantity" />
                                </td>
                                <td>
                                    <input type="number" step="0.01" wire:model.live="items.{{ $index }}.price_value"
                                        class="form-control form-control-sm" placeholder="0.00" @if($this->isReadonly())
                                    readonly @endif />
                                    <x-input.error for="items.{{ $index }}.price_value" />
                                </td>
                                <td>
                                    <input type="number" step="0.01" wire:model.live="items.{{ $index }}.weight_value"
                                        class="form-control form-control-sm" placeholder="0.00" @if($this->isReadonly())
                                    readonly @endif />
                                    <x-input.error for="items.{{ $index }}.weight_value" />
                                </td>
                                <td>
                                    <div class="form-control-plaintext small">Rp {{
                                        number_format($this->getItemSubTotal($index), 0, ',', '.') }}</div>
                                </td>
                                <td>
                                    @if(!$this->isDisabled() && count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-table fs-1 mb-3"></i>
                    <p class="mb-0">Belum ada item livestock yang ditambahkan</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" wire:click="cancel">
                <i class="bi bi-x-circle me-1"></i> Batal
            </button>

            @if($this->canSave())
            @can('create livestock purchasing')
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan
            </button>
            @endcan
            @endif
        </div>

        <!-- Debug Info for Save Button -->
        @if(config('app.debug'))
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Debug Info - Save Button</strong><br>
            <small class="text-muted">
                Current Status: <b>{{ $this->getCurrentStatus() }}</b><br>
                Can Save: <b>{{ $this->canSave() ? 'Yes' : 'No' }}</b><br>
                Is Disabled: <b>{{ $this->isDisabled() ? 'Yes' : 'No' }}</b><br>
                Has Permission: <b>{{ auth()->user()->can('create livestock purchasing') ? 'Yes' : 'No' }}</b>
            </small>
        </div>
        @endif
    </form>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger mt-3">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('error') }}
    </div>
    @endif

    @if (in_array($this->status, ['in_coop', 'complete']))
    <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Catatan:</strong> Form ini hanya dalam mode read-only.
    </div>
    @endif

    <script>
        // Event listener untuk Livewire events
        document.addEventListener('livewire:init', () => {
            // Listen untuk event item-updated
            Livewire.on('item-updated', (event) => {
                console.log('Item updated:', event.index);
                // Trigger re-render untuk update kalkulasi
                Livewire.dispatch('recalculate-totals');
            });

            // Listen untuk event all-items-recalculated
            Livewire.on('all-items-recalculated', () => {
                console.log('All items recalculated');
            });

            // Listen untuk event expedition-fee-updated
            Livewire.on('expedition-fee-updated', () => {
                console.log('Expedition fee updated');
            });
        });

        // Auto-recalculate saat input berubah
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[type="number"]') && e.target.name.includes('items')) {
                // Trigger Livewire update
                setTimeout(() => {
                    Livewire.dispatch('recalculate-totals');
                }, 100);
            }
        });

        // Auto-recalculate saat select berubah
        document.addEventListener('change', function(e) {
            if (e.target.matches('select') && e.target.name.includes('items')) {
                // Trigger Livewire update
                setTimeout(() => {
                    Livewire.dispatch('recalculate-totals');
                }, 100);
            }
        });
    </script>

</div>