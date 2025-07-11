<div>
    @if($showForm)
    @php
    $isReadOnly = !$edit_mode && isset($usageId) && isset($usage) && $usage && in_array($usage->status, [
    \App\Models\SupplyUsage::STATUS_CANCELLED,
    \App\Models\SupplyUsage::STATUS_COMPLETED
    ]);
    $isCompleted = !$edit_mode && isset($usageId) && isset($usage) && $usage && $usage->status ===
    \App\Models\SupplyUsage::STATUS_COMPLETED;
    @endphp

    @if($isReadOnly)
    {{-- Read-only UI for cancelled or completed usage --}}
    <div class="alert {{ $isCompleted ? 'alert-success' : 'alert-warning' }} d-flex align-items-center p-5 mb-6">
        <i class="ki-duotone ki-information-5 fs-2hx {{ $isCompleted ? 'text-success' : 'text-warning' }} me-4">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <div class="d-flex flex-column">
            <h4 class="mb-1 {{ $isCompleted ? 'text-success' : 'text-warning' }}">
                {{ $isCompleted ? 'Data Sudah Selesai' : 'Data Sudah Dibatalkan' }}
            </h4>
            <span>
                {{ $isCompleted ? 'Data penggunaan supply ini sudah selesai diproses dan hanya dapat dilihat
                (read-only).' : 'Data penggunaan supply ini sudah dibatalkan dan hanya dapat dilihat (read-only).' }}
            </span>
        </div>
    </div>
    <form>
        {{-- Lokasi Penggunaan --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Lokasi Penggunaan</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <x-input.group col="md-4" label="Farm" for="farm_id">
                        <input type="text" class="form-control"
                            value="{{ $farms->firstWhere('id', $farm_id)->name ?? '' }}" readonly />
                    </x-input.group>
                    <x-input.group col="md-4" label="Kandang" for="coop_id">
                        <input type="text" class="form-control"
                            value="{{ $coops->firstWhere('id', $coop_id)->name ?? '' }}" readonly />
                    </x-input.group>
                    <x-input.group col="md-4" label="Livestock (Opsional)" for="livestock_id">
                        <input type="text" class="form-control"
                            value="{{ $livestocks->firstWhere('id', $livestock_id)->name ?? '-' }}" readonly />
                    </x-input.group>
                </div>
            </div>
        </div>
        {{-- Informasi Penggunaan --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Informasi Penggunaan</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <x-input.group col="md-6" label="Tanggal & Waktu Penggunaan" for="usage_date">
                        <input type="text" class="form-control" value="{{ $usage_date }}" readonly />
                    </x-input.group>
                    <x-input.group col="md-6" label="Catatan" for="notes">
                        <textarea class="form-control" rows="3" readonly>{{ $notes }}</textarea>
                    </x-input.group>
                </div>
            </div>
        </div>
        {{-- Item Supply --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Item Supply</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Supply Stock</th>
                                <th>Stock Tersedia</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Converted Qty</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                            <tr>
                                <td>{{ $availableSupplies[$loop->index]['supply_name'] ?? '-' }}</td>
                                <td>{{ number_format($item['available_stock'] ?? 0, 2) }}</td>
                                <td>{{ $item['quantity_taken'] }}</td>
                                <td>{{ $item['available_units'][$item['unit_id']]['name'] ?? '-' }}</td>
                                <td>{{ $item['converted_quantity'] }}</td>
                                <td>{{ $item['notes'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end my-4">
            <button type="button" wire:click="close" class="btn btn-secondary">
                <i class="ki-duotone ki-x fs-3 me-1"></i>
                Kembali
            </button>
        </div>
    </form>
    @else
    <h5 class="mb-4 fw-bold">Form Penggunaan Supply</h5>

    {{-- Disable for production --}}
    <!-- Stock Validation Summary Alert -->
    {{-- @if($hasStockValidationErrors)
    <div class="alert alert-danger d-flex align-items-center p-5 mb-6">
        <i class="ki-duotone ki-information-5 fs-2hx text-danger me-4">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <div class="d-flex flex-column">
            <h4 class="mb-1 text-danger">⚠️ Masalah Stock Ditemukan</h4>
            <span>Terdapat {{ $totalValidationErrors }} item dengan pemakaian yang melebihi stock tersedia. Harap
                periksa dan sesuaikan quantity.</span>
            <div class="mt-2">
                <button type="button" wire:click="validateAllStocks" class="btn btn-sm btn-light-danger me-2">
                    <i class="ki-duotone ki-check-circle fs-4 me-1"></i>
                    Validasi Ulang
                </button>
                <button type="button" wire:click="clearValidationErrors" class="btn btn-sm btn-light-secondary">
                    <i class="ki-duotone ki-trash fs-4 me-1"></i>
                    Clear Errors
                </button>
            </div>
        </div>
    </div>
    @endif --}}

    <!-- Alert for edit mode -->
    @if($edit_mode)
    <div class="alert alert-info d-flex align-items-center p-5 mb-6">
        <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <div class="d-flex flex-column">
            <h4 class="mb-1 text-info">Mode Edit</h4>
            <span>Anda sedang mengedit data penggunaan supply yang sudah ada.</span>
        </div>
    </div>
    @endif

    <form wire:submit.prevent="save">
        {{-- === Lokasi Penggunaan === --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Lokasi Penggunaan</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <x-input.group col="md-4" label="Farm" for="farm_id">
                        <select wire:model.live="farm_id" class="form-select" required>
                            <option value="">Pilih Farm</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                            @endforeach
                        </select>
                        <x-input.error for="farm_id" />
                    </x-input.group>

                    <x-input.group col="md-4" label="Kandang" for="coop_id">
                        <select wire:model.live="coop_id" class="form-select" required {{ !$farm_id ? 'disabled' : ''
                            }}>
                            <option value="">Pilih Kandang</option>
                            @foreach($coops as $coop)
                            <option value="{{ $coop->id }}">{{ $coop->name }}</option>
                            @endforeach
                        </select>
                        <x-input.error for="coop_id" />
                    </x-input.group>

                    <x-input.group col="md-4" label="Livestock (Opsional)" for="livestock_id">
                        <select wire:model.live="livestock_id" class="form-select" {{ !$coop_id ? 'disabled' : '' }}>
                            <option value="">Pilih Livestock (Opsional)</option>
                            @foreach($livestocks as $livestock)
                            <option value="{{ $livestock->id }}">{{ $livestock->name }}</option>
                            @endforeach
                        </select>
                        <x-input.error for="livestock_id" />
                    </x-input.group>
                </div>
            </div>
        </div>

        {{-- === Informasi Penggunaan === --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Informasi Penggunaan</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <x-input.group col="md-6" label="Tanggal & Waktu Penggunaan" for="usage_date">
                        <input type="datetime-local" wire:model.live="usage_date" class="form-control" required>
                        <x-input.error for="usage_date" />
                    </x-input.group>

                    <x-input.group col="md-6" label="Catatan" for="notes">
                        <textarea wire:model="notes" class="form-control" rows="3"
                            placeholder="Catatan tambahan..."></textarea>
                        <x-input.error for="notes" />
                    </x-input.group>
                </div>
            </div>
        </div>

        {{-- === Item Supply === --}}
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Item Supply</h3>
                <div class="card-toolbar">
                    <button type="button" wire:click="addItem" class="btn btn-sm btn-primary" {{ !$farm_id ? 'disabled'
                        : '' }}>
                        <i class="ki-duotone ki-plus fs-3"></i>
                        Tambah Item
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if(!$farm_id)
                <div class="alert alert-warning">
                    <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    Silakan pilih Farm terlebih dahulu untuk melihat supply yang tersedia.
                </div>
                @endif

                @if($farm_id && empty($availableSupplies))
                <div class="alert alert-info">
                    <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    Tidak ada supply dengan stock yang tersedia untuk farm ini.
                </div>
                @endif

                @if($farm_id && !empty($availableSupplies))
                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Supply Stock</th>
                                <th>Stock Tersedia</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Converted Qty</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                            @php
                            $hasStockError = isset($stockValidationErrors[$index]) &&
                            $stockValidationErrors[$index]['hasError'];
                            $stockError = $stockValidationErrors[$index] ?? null;
                            $rowClass = $hasStockError ? 'table-danger' : '';
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <!-- Supply Stock Selection -->
                                <td>
                                    <select wire:model.live="items.{{ $index }}.supply_stock_id"
                                        class="form-select @error('items.'.$index.'.supply_stock_id') is-invalid @enderror {{ $hasStockError ? 'border-danger' : '' }}"
                                        required>
                                        <option value="">Pilih Supply Stock</option>
                                        @foreach($availableSupplies as $supply)
                                        <option value="{{ $supply['id'] }}">
                                            {{ $supply['supply_name'] }} ({{ $supply['category'] }})
                                            @if($supply['batch_number'])
                                            - Batch: {{ $supply['batch_number'] }}
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('items.'.$index.'.supply_stock_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>

                                <!-- Available Stock -->
                                <td>
                                    <span class="badge badge-light-success fs-7">
                                        {{ number_format($item['available_stock'], 2) }}
                                    </span>
                                    @if($hasStockError)
                                    <br>
                                    <small class="text-danger">
                                        <i class="ki-duotone ki-warning fs-6 text-danger me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Kekurangan: {{ number_format($stockError['requestedQuantity'] -
                                        $stockError['availableStock'], 2) }}
                                    </small>
                                    @endif
                                </td>

                                <!-- Quantity Taken -->
                                <td>
                                    <input type="number" wire:model.live="items.{{ $index }}.quantity_taken"
                                        class="form-control @error('items.'.$index.'.quantity_taken') is-invalid @enderror {{ $hasStockError ? 'border-danger bg-light-danger' : '' }}"
                                        step="0.01" min="0.01" max="{{ $item['available_stock'] }}" placeholder="0.00"
                                        required>
                                    @error('items.'.$index.'.quantity_taken')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($hasStockError)
                                    <div class="text-danger small mt-1">
                                        <i class="ki-duotone ki-cross-circle fs-6 text-danger me-1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Melebihi {{ number_format($item['available_stock'], 2) }}
                                    </div>
                                    @endif
                                </td>

                                <!-- Unit -->
                                <td>
                                    <select wire:model.live="items.{{ $index }}.unit_id"
                                        class="form-select @error('items.'.$index.'.unit_id') is-invalid @enderror {{ $hasStockError ? 'border-danger' : '' }}"
                                        required>
                                        <option value="">Pilih Unit</option>
                                        @if(isset($item['available_units']))
                                        @foreach($item['available_units'] as $unit)
                                        <option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                    @error('items.'.$index.'.unit_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>

                                <!-- Converted Quantity -->
                                <td>
                                    <input type="number" wire:model="items.{{ $index }}.converted_quantity"
                                        class="form-control @error('items.'.$index.'.converted_quantity') is-invalid @enderror {{ $hasStockError ? 'border-danger bg-light-danger' : '' }}"
                                        step="0.01" min="0.01" placeholder="0.00" required readonly>
                                    @error('items.'.$index.'.converted_quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>

                                <!-- Notes -->
                                <td>
                                    <input type="text" wire:model="items.{{ $index }}.notes" class="form-control"
                                        placeholder="Catatan item...">
                                </td>

                                <!-- Validation Status -->
                                <td class="text-center">
                                    @if($hasStockError)
                                    <span class="badge badge-danger" title="{{ $stockError['message'] ?? '' }}">
                                        <i class="ki-duotone ki-cross-circle fs-6">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Error
                                    </span>
                                    @elseif(!empty($item['quantity_taken']) && !empty($item['supply_stock_id']))
                                    <span class="badge badge-success">
                                        <i class="ki-duotone ki-check-circle fs-6">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Valid
                                    </span>
                                    @else
                                    <span class="badge badge-secondary">
                                        <i class="ki-duotone ki-questionnaire-tablet fs-6">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Pending
                                    </span>
                                    @endif
                                </td>

                                <!-- Action -->
                                <td class="text-center">
                                    @if(count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="btn btn-sm btn-icon btn-light-danger" title="Hapus item">
                                        <i class="ki-duotone ki-trash fs-5">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                        </i>
                                    </button>
                                    @else
                                    <span class="text-muted small">Minimal 1 item</span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Stock Error Details Row -->
                            @if($hasStockError)
                            <tr class="table-danger">
                                <td colspan="9" class="py-2">
                                    <div class="alert alert-danger border-0 mb-0 p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-warning fs-2hx text-danger me-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 text-danger">
                                                    Validasi Stock Gagal - {{ $stockError['supplyName'] ?? 'Unknown
                                                    Supply' }}
                                                </h6>
                                                <p class="mb-2">{{ $stockError['message'] ?? 'Quantity melebihi stock
                                                    yang tersedia' }}</p>
                                                <div class="row text-sm">
                                                    <div class="col-md-3">
                                                        <strong>Stock Tersedia:</strong>
                                                        <span class="text-success">{{
                                                            number_format($stockError['availableStock'] ?? 0, 2)
                                                            }}</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Quantity Diminta:</strong>
                                                        <span class="text-danger">{{
                                                            number_format($stockError['requestedQuantity'] ?? 0, 2)
                                                            }}</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Kekurangan:</strong>
                                                        <span class="text-warning">{{
                                                            number_format(($stockError['requestedQuantity'] ?? 0) -
                                                            ($stockError['availableStock'] ?? 0), 2) }}</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <button type="button" wire:click="clearValidationErrors"
                                                            class="btn btn-sm btn-light-danger">
                                                            <i class="ki-duotone ki-abstract-1 fs-6 me-1">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                            Reset
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('items')
                <div class="alert alert-danger mt-3">{{ $message }}</div>
                @enderror
                @endif
            </div>
        </div>

        {{-- === Action Buttons === --}}
        <div class="d-flex justify-content-between my-4">
            <!-- Left side: Validation Controls -->
            {{-- <div>
                @if($realTimeValidation)
                <button type="button" wire:click="toggleRealTimeValidation" class="btn btn-sm btn-light-success me-2">
                    <i class="ki-duotone ki-check-circle fs-4 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Real-time Validation ON
                </button>
                @else
                <button type="button" wire:click="toggleRealTimeValidation" class="btn btn-sm btn-light-warning me-2">
                    <i class="ki-duotone ki-cross-circle fs-4 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Real-time Validation OFF
                </button>
                @endif

                <button type="button" wire:click="validateAllStocks" class="btn btn-sm btn-light-primary me-2">
                    <i class="ki-duotone ki-verify fs-4 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Validasi Semua Stock
                </button>

                @if($showDebug)
                <button type="button" wire:click="debugSaveConditions" class="btn btn-sm btn-light-info">
                    <i class="ki-duotone ki-information fs-4 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    Debug Save
                </button>
                @endif
            </div> --}}

            <!-- Right side: Main Actions -->
            <div>
                <button type="button" wire:click="close" class="btn btn-secondary me-2">
                    <i class="ki-duotone ki-x fs-3 me-1"></i>
                    Batal
                </button>

                @if($hasStockValidationErrors)
                <button type="button" class="btn btn-danger" disabled title="Perbaiki masalah stock terlebih dahulu">
                    <i class="ki-duotone ki-warning fs-3 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Tidak Dapat Disimpan
                </button>
                @elseif(!$canSave)
                <button type="button" class="btn btn-secondary" disabled title="Lengkapi semua field yang required">
                    <i class="ki-duotone ki-questionnaire-tablet fs-3 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Data Belum Lengkap
                </button>
                @else
                <button type="submit" class="btn btn-primary">
                    <span wire:loading.remove wire:target="save">
                        <i class="ki-duotone ki-check fs-3 me-1"></i>
                        {{ $edit_mode ? 'Update' : 'Simpan' }}
                    </span>
                    <span wire:loading wire:target="save">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Menyimpan...
                    </span>
                </button>
                @endif
            </div>
        </div>
    </form>

    @error('save_error')
    <div class="alert alert-danger mt-3">{{ $message }}</div>
    @enderror
    @endif

    <!-- Success/Error Messages -->
    @if(session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" id="autoCloseAlert">
        <i class="ki-duotone ki-check-circle fs-2hx text-success me-4">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @push('scripts')
    <script>
        // Auto-close success alerts
            setTimeout(function() {
                var alert = document.getElementById('autoCloseAlert');
                if(alert){
                    // Bootstrap 5 dismiss
                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                    console.log('Alert closed');
                }
            }, 3000);

            // Listen for Livewire events for real-time feedback
            document.addEventListener('livewire:initialized', function () {
                // Stock validation error event
                Livewire.on('stockValidationError', function (data) {
                    console.log('Stock validation error for item:', data);
                    
                    // Show toast notification
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Item #' + (data.index + 1) + ': ' + data.message, 'Stock Validation Error', {
                            timeOut: 5000,
                            progressBar: true
                        });
                    }
                    
                    // Add visual shake animation to the row
                    const itemRow = document.querySelector(`tr:nth-child(${data.index + 1})`);
                    if (itemRow) {
                        itemRow.style.animation = 'shake 0.5s ease-in-out';
                        setTimeout(() => {
                            itemRow.style.animation = '';
                        }, 500);
                    }
                });

                // Stock validation success event
                Livewire.on('stockValidationSuccess', function (data) {
                    console.log('Stock validation success for item:', data);
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Stock validation berhasil untuk item #' + (data.index + 1), 'Validation Success', {
                            timeOut: 3000
                        });
                    }
                });

                // Stock validation warning event
                Livewire.on('stockValidationWarning', function (message) {
                    console.log('Stock validation warning:', message);
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.warning(message, 'Stock Warning', {
                            timeOut: 5000,
                            progressBar: true
                        });
                    }
                });

                // Stock validation cleared event
                Livewire.on('stockValidationCleared', function (message) {
                    console.log('Stock validation cleared:', message);
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.info(message, 'Validation Cleared', {
                            timeOut: 3000
                        });
                    }
                });

                // General success/error/info events
                // Livewire.on('success', function (message) {
                //     if (typeof toastr !== 'undefined') {
                //         toastr.success(message, 'Success');
                //     }
                // });

                // Livewire.on('error', function (message) {
                //     if (typeof toastr !== 'undefined') {
                //         toastr.error(message, 'Error');
                //     }
                // });

                // Livewire.on('info', function (message) {
                //     if (typeof toastr !== 'undefined') {
                //         toastr.info(message, 'Info');
                //     }
                // });
            });
    </script>

    <style>
        /* Shake animation for validation errors */
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        /* Enhanced visual indicators */
        .table-danger {
            background-color: #f8d7da !important;
        }

        .border-danger {
            border-color: #dc3545 !important;
        }

        .bg-light-danger {
            background-color: #f8d7da !important;
        }

        /* Custom badge styling */
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-success {
            background-color: #198754;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        /* Enhanced form styling */
        .form-control.border-danger:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .form-select.border-danger:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
    </style>
    @endpush
    @endif
</div>