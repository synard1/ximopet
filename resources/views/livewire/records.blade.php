<div id="livewireRecordsContainer">
    @if($showForm)
    <h2 class="text-2xl font-bold mb-8 text-gray-800">📋 Manajemen Recording Ayam</h2>

    <!-- Yesterday Information Panel -->
    @if($yesterdayData && $yesterdayData['has_data'])
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between mb-3">
            <div class="text-lg font-semibold text-blue-800 flex items-center">
                <i class="fas fa-chart-bar mr-2"></i>
                Data Kemarin ({{ $yesterdayData['formatted_date'] }} - {{ $yesterdayData['day_name'] }})
            </div>
            <span class="text-sm text-blue-600 bg-blue-100 px-2 py-1 rounded-full">
                {{ $yesterdayData['summary'] }}
            </span>
        </div>

        {{-- <div class="flex flex-wrap gap-4">
            <!-- Yesterday Weight -->
            @if($yesterdayData['weight'] > 0)
            <div class="bg-white rounded-lg p-3 border border-blue-100 flex items-center">
                <i class="fas fa-weight-hanging mr-2"></i>
                <div>
                    <p class="text-xs text-gray-500">Berat Kemarin</p>
                    <p class="text-sm font-semibold text-gray-800">{{ number_format($yesterdayData['weight'], 0) }} gr
                    </p>
                </div>
            </div>
            @endif

            <!-- Yesterday Mortality -->
            @if($yesterdayData['total_depletion'] > 0)
            <div class="bg-white rounded-lg p-3 border border-blue-100 flex items-center">
                <i class="fas fa-skull-crossbones mr-2"></i>
                <div>
                    <p class="text-xs text-gray-500">Deplesi Kemarin</p>
                    <p class="text-sm font-semibold text-gray-800">
                        @if($yesterdayData['mortality'] > 0)
                        💀 {{ $yesterdayData['mortality'] }}
                        @endif
                        @if($yesterdayData['culling'] > 0)
                        🛑 {{ $yesterdayData['culling'] }}
                        @endif
                    </p>
                </div>
            </div>
            @endif

            <!-- Yesterday Feed Usage -->
            @if($yesterdayData['feed_usage']['total_quantity'] > 0)
            <div class="bg-white rounded-lg p-3 border border-blue-100 flex items-center">
                <i class="fas fa-utensils mr-2"></i>
                <div>
                    <p class="text-xs text-gray-500">Pakan Kemarin</p>
                    <p class="text-sm font-semibold text-gray-800">{{
                        number_format($yesterdayData['feed_usage']['total_quantity'], 1) }} kg</p>
                    @if($yesterdayData['feed_usage']['types_count'] > 1)
                    <p class="text-xs text-gray-400">{{ $yesterdayData['feed_usage']['types_count'] }} jenis</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Yesterday Supply Usage -->
            @if($yesterdayData['supply_usage']['total_quantity'] > 0)
            <div class="bg-white rounded-lg p-3 border border-blue-100 flex items-center">
                <i class="fas fa-flask mr-2"></i>
                <div>
                    <p class="text-xs text-gray-500">OVK Kemarin</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $yesterdayData['supply_usage']['types_count'] }}
                        jenis</p>
                    <p class="text-xs text-gray-400">{{ number_format($yesterdayData['supply_usage']['total_quantity'],
                        1) }} unit</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Detailed Yesterday Information (Collapsible) -->
        <div class="mt-4">
            <button type="button" class="text-sm text-blue-600 hover:text-blue-800 flex items-center"
                onclick="toggleYesterdayDetails()">
                <i class="fas fa-chevron-down mr-2"></i>
                Lihat Detail Kemarin
            </button>

            <div id="yesterday-details" class="hidden mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Yesterday Feed Details -->
                @if($yesterdayData['feed_usage']['total_quantity'] > 0)
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Detail Pakan Kemarin</h4>
                    @foreach($yesterdayData['feed_usage']['by_type'] as $feed)
                    <div class="flex justify-between items-center py-1">
                        <span class="text-xs text-gray-600">{{ $feed['name'] }}</span>
                        <span class="text-xs font-medium text-gray-800">{{ number_format($feed['total_quantity'], 1) }}
                            {{ $feed['unit'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Yesterday Supply Details -->
                @if($yesterdayData['supply_usage']['total_quantity'] > 0)
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Detail OVK Kemarin</h4>
                    @foreach($yesterdayData['supply_usage']['by_type'] as $supply)
                    <div class="flex justify-between items-center py-1">
                        <span class="text-xs text-gray-600">{{ $supply['name'] }}</span>
                        <span class="text-xs font-medium text-gray-800">{{ number_format($supply['total_quantity'], 1)
                            }} {{ $supply['unit'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div> --}}
    </div>
    @elseif($date)
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <span class="text-sm text-gray-600">Tidak ada data kemarin untuk ditampilkan</span>
        </div>
    </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-8">
        <div class="row g-3">
            <x-input.group col="6" label="📅 Tanggal">
                <input type="date" wire:model.live="date" class="form-control" max="{{ date('Y-m-d') }}">
                <x-input.error for="date" />
            </x-input.group>

            <x-input.group col="6" label="Berat Hari Ini (gr)">
                <input type="number" step="0.01" wire:model="weight_today" class="form-control"
                    placeholder="Berat ayam hari ini">
                @if($weight_yesterday > 0)
                <small class="text-muted mt-1 d-block">
                    📊 Kemarin: {{ number_format($weight_yesterday, 0) }}gr
                    @if($weight_today > 0)
                    | Kenaikan: {{ number_format($weight_today - $weight_yesterday, 0) }}gr
                    @endif
                </small>
                @endif
                <x-input.error for="weight_today" />
            </x-input.group>

            <!-- Total Deplesi Summary Only -->
            <x-input.group col="6" label="⚠️ Total Deplesi (Ekor)">
                @if($isManualDepletionEnabled)
                <!-- Manual Depletion Mode - Read Only Display -->
                <div class="form-control bg-light"
                    style="display: flex; align-items: center; justify-content: space-between;">
                    <span>{{ ($mortality ?? 0) + ($culling ?? 0) }} ekor</span>
                    <span class="badge bg-info text-white">Manual</span>
                </div>

                <!-- Manual Depletion Notice -->
                <div class="alert alert-info mt-2 py-2" role="alert">
                    <small class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>Mode Manual Depletion Aktif:</strong>
                            Data deplesi dikelola melalui menu <strong>"Manual Depletion"</strong> pada tabel livestock.
                            <br>Input deplesi di form recording ini dinonaktifkan untuk mencegah duplikasi data.
                        </div>
                    </small>
                </div>

                <!-- Hidden inputs for maintaining data in manual mode -->
                <input type="hidden" wire:model="mortality">
                <input type="hidden" wire:model="culling">
                @else
                <!-- Recording Mode - Editable Inputs -->
                <div class="form-control bg-light"
                    style="display: flex; align-items: center; justify-content: space-between;">
                    <span>{{ ($mortality ?? 0) + ($culling ?? 0) }} ekor</span>
                    <span class="badge bg-success text-white">Recording</span>
                </div>

                <!-- Depletion Input Fields -->
                <div class="row mt-2">
                    <div class="col-6">
                        <label class="form-label text-sm">💀 Mati (Ekor)</label>
                        <input type="number" wire:model.live="mortality" class="form-control form-control-sm" min="0"
                            placeholder="0" value="{{ $mortality ?? 0 }}">
                        <x-input.error for="mortality" />
                    </div>
                    <div class="col-6">
                        <label class="form-label text-sm">🛑 Afkir (Ekor)</label>
                        <input type="number" wire:model.live="culling" class="form-control form-control-sm" min="0"
                            placeholder="0" value="{{ $culling ?? 0 }}">
                        <x-input.error for="culling" />
                    </div>
                </div>

                <!-- Recording Mode Notice -->
                <div class="alert alert-success mt-2 py-2" role="alert">
                    <small class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <div>
                            <strong>Mode Recording Aktif:</strong>
                            Data deplesi dapat diinput langsung melalui form ini.
                        </div>
                    </small>
                </div>
                @endif

                <!-- Yesterday's Depletion Data -->
                @if($yesterdayData && $yesterdayData['total_depletion'] > 0)
                <small class="text-muted mt-1 d-block">
                    📊 Kemarin ({{ $yesterdayData['formatted_date'] }}): {{ $yesterdayData['total_depletion'] }} ekor
                    deplesi
                    @if($yesterdayData['is_manual_depletion'] ?? false)
                    <span class="badge bg-info text-white ms-1" style="font-size: 0.7em;">Manual</span>
                    @else
                    <span class="badge bg-success text-white ms-1" style="font-size: 0.7em;">Recording</span>
                    @endif
                    @if($yesterdayData['mortality'] > 0 || $yesterdayData['culling'] > 0)
                    <br>&nbsp;&nbsp;&nbsp;&nbsp;💀 Mati: {{ $yesterdayData['mortality'] }} | 🛑 Afkir: {{
                    $yesterdayData['culling'] }}
                    @endif
                </small>
                @elseif($date)
                <small class="text-muted mt-1 d-block">
                    📊 Kemarin: Tidak ada deplesi
                </small>
                @endif

                <!-- Current Day Breakdown -->
                <small class="text-muted mt-1 d-block">
                    <strong>Hari ini:</strong> 💀 Mati: {{ $mortality ?? 0 }} | 🛑 Afkir: {{ $culling ?? 0 }}
                    @if($isManualDepletionEnabled)
                    <span class="badge bg-secondary ms-2">Dikelola Manual</span>
                    @else
                    <span class="badge bg-primary ms-2">Dikelola Recording</span>
                    @endif
                </small>
            </x-input.group>

            <x-input.group col="6" label="Jumlah Terjual (Ekor)">
                <input type="number" wire:model.live="sales_quantity" class="form-control"
                    placeholder="Jumlah ayam terjual">
                <x-input.error for="sales_quantity" />
            </x-input.group>

            <x-input.group col="6" label="Berat Terjual (Ekor)">
                <input type="number" wire:model.live="sales_weight" class="form-control"
                    placeholder="Berat ayam terjual">
                <x-input.error for="sales_weight" />
            </x-input.group>
        </div>

        <!-- Container untuk Penggunaan Item - Side by Side -->
        <div class="mt-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Tabel Penggunaan Pakan -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    @if(!$isManualFeedUsageEnabled && Auth::user()->can('create feed usage'))
                    <h3 class="text-lg font-semibold mb-3 text-gray-700 border-b pb-2">📦 Penggunaan Pakan</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item
                                        Pakan</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Stock
                                    </th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Jumlah
                                        (Kg)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($items as $item)
                                @php
                                $usedQty = $itemQuantities[$item['item_id']] ?? 0;
                                $availableStock = $item['stock'];
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-2 text-sm font-medium text-gray-900">
                                        {{ $item['item_name'] }}
                                    </td>
                                    <td class="px-2 py-2 text-sm">
                                        <span
                                            class="{{ $availableStock > 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium' }}">
                                            {{ number_format($availableStock, 0) }} Kg
                                        </span>
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="number"
                                            class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            wire:model="itemQuantities.{{ $item['item_id'] }}" min="0"
                                            max="{{ $availableStock }}" placeholder="0" {{ $availableStock <=0
                                            ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-2 py-4 text-center text-gray-500 text-sm">
                                        Tidak ada stok pakan tersedia
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @elseif($isManualFeedUsageEnabled)
                    <!-- Manual Feed Usage Notice -->
                    <h3 class="text-lg font-semibold mb-3 text-gray-700 border-b pb-2">📦 Penggunaan Pakan</h3>
                    <div class="bg-info-50 border border-info-200 rounded p-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2 text-info"></i>
                            <div>
                                <strong>Mode Manual Feed Usage Aktif:</strong><br>
                                <small class="text-muted">
                                    Data penggunaan pakan dicatat melalui menu <strong>"Manual Usage"</strong> pada
                                    tabel livestock.
                                    Input otomatis di form ini dinonaktifkan untuk mencegah duplikasi data.
                                </small>
                            </div>
                        </div>
                    </div>
                    @elseif(!Auth::user()->can('create feed usage'))
                    <h3 class="text-lg font-semibold mb-3 text-gray-700 border-b pb-2">📦 Penggunaan Pakan</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                        <p class="text-sm text-yellow-800">Anda tidak memiliki izin untuk membuat penggunaan pakan.
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Tabel Penggunaan OVK/Supply -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    @if(Auth::user()->can('create supply usage'))
                    <h3 class="text-lg font-semibold mb-3 text-gray-700 border-b pb-2">🧪 Penggunaan OVK/Supply</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Supply/OVK</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Stock
                                    </th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Jumlah
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($availableSupplies as $supply)
                                @php
                                $stockCheck = $this->checkSupplyStock($supply->id, $this->livestockId);
                                $availableStock = $stockCheck['stock'] ?? 0;
                                $unitName = $supply->unit->name ?? 'Unit';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-2 text-sm">
                                        <div class="font-medium text-gray-900">{{ $supply->name }}</div>
                                        <div class="text-xs text-gray-500">({{ $supply->code }})</div>
                                    </td>
                                    <td class="px-2 py-2 text-sm">
                                        <span
                                            class="{{ $availableStock > 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium' }}">
                                            {{ number_format($availableStock, 0) }} {{ $unitName }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="flex items-center space-x-1">
                                            <input type="number"
                                                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                wire:model="supplyQuantities.{{ $supply->id }}" min="0"
                                                max="{{ $availableStock }}" placeholder="0" {{ $availableStock <=0
                                                ? 'disabled' : '' }}>
                                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $unitName
                                                }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-2 py-4 text-center text-gray-500 text-sm">
                                        Tidak ada supply OVK tersedia
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                        <p class="text-sm text-yellow-800">Anda tidak memiliki izin untuk membuat penggunaan supply.
                        </p>
                    </div>
                    @endif
                </div>

            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="d-flex justify-content-between my-4">
            <div>
                <button wire:click="refreshConfiguration" class="btn btn-outline-info rounded-lg px-4 py-2">
                    <i class="fas fa-sync-alt"></i> Segarkan Konfigurasi
                </button>
            </div>
            <div>
                <button wire:click="closeForm" class="btn btn-danger rounded-lg px-6 py-2 me-2">Kembali ke
                    Tabel</button>
                <!-- Tombol Simpan -->
                @if($isEditing)
                <button type="submit" class="btn btn-primary rounded-lg px-6 py-2" id='submitData'>💾 Ubah Data</button>
                @else
                <button type="submit" class="btn btn-primary rounded-lg px-6 py-2" id='submitData'>💾 Simpan
                    Data</button>
                @endif
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
    
        Livewire.on('noSubmit', () => {
            console.log('no submit');
            const saveButton = document.getElementById('submitData');
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.classList.remove('btn-primary');
                saveButton.classList.add('btn-secondary');
            } else {
                console.warn('Element with ID "submitData" not found.');
            }
        });
    
        const validateInput = (input) => {
            const stock = parseFloat(input.dataset.stock);
            let value = parseFloat(input.value) || 0;
            
            if (value > stock) {
                value = stock;
                input.value = value;
            }
            
            if (value < 0) {
                value = 0;
                input.value = value;
            }
        };
    
        // Add input event listener to all quantity inputs
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('input', function() {
                validateInput(this);
            });
        });
    });

    function openDepletionModal() {
        // Check if manual depletion is enabled
        const isManualDepletion = @json($isManualDepletionEnabled);
        
        if (isManualDepletion) {
            alert('Mode Manual Depletion aktif.\n\nData deplesi dikelola melalui menu "Manual Depletion" pada tabel livestock.\n\nSilakan gunakan fitur Manual Depletion untuk input detail deplesi.');
        } else {
            alert('Mode Recording aktif.\n\nData deplesi dapat diinput langsung melalui form ini menggunakan field "Mati" dan "Afkir" yang tersedia.');
        }
    }

    // Auto-update total when mortality or culling changes
    function updateDepletionTotal() {
        const mortality = parseInt(document.querySelector('input[wire\\:model\\.live="mortality"]')?.value || 0);
        const culling = parseInt(document.querySelector('input[wire\\:model\\.live="culling"]')?.value || 0);
        const total = mortality + culling;
        
        // Update total display if element exists
        const totalDisplay = document.querySelector('.form-control.bg-light span');
        if (totalDisplay) {
            totalDisplay.textContent = total + ' ekor';
        }
    }

    function toggleYesterdayDetails() {
        const details = document.getElementById('yesterday-details');
        const icon = event.target.querySelector('i');
        
        if (details.classList.contains('hidden')) {
            details.classList.remove('hidden');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            details.classList.add('hidden');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
    </script>
    @endpush

    @push('styles')
    <style>
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
            border-gray-300;
        }

        .form-control:focus {
            border-color: #007bff;
        }

        .error-text {
            font-size: 12px;
            color: red;
            text-sm;
            text-red-500;
        }

        .section-header {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-top: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s;
            rounded-lg;
            px-6;
            py-2;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .table-header,
        .table-cell {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
            border-gray-100;
        }



        .text-green-600 {
            color: #059669;
        }

        .text-red-600 {
            color: #dc2626;
        }
    </style>
    @endpush
    @endif
</div>