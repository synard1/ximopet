<div id="livewireRecordsContainer">
    @if($showForm)
    <h2 class="text-2xl font-bold mb-8 text-gray-800">📋 Manajemen Recording Ayam</h2>

    <!-- Loading indicator for async history reload -->
    <div wire:loading wire:target="reloadHistoryData" class="my-4 text-blue-600 flex items-center">
        <svg class="animate-spin h-5 w-5 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        Memuat data history...
    </div>
    <button type="button" wire:click="reloadHistoryData" wire:loading.attr="disabled"
        class="btn btn-outline-primary btn-sm mb-4">
        <i class="fas fa-sync-alt mr-1"></i> Reload Data History
    </button>

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

        <div class="flex flex-wrap gap-4">
            <!-- Yesterday Weight -->
            {{-- @if($yesterdayData['weight'] > 0)
            <div class="bg-white rounded-lg p-3 border border-blue-100 flex items-center">
                <i class="fas fa-weight-hanging mr-2"></i>
                <div>
                    <p class="text-xs text-gray-500">Berat Kemarin</p>
                    <p class="text-sm font-semibold text-gray-800">{{ number_format($yesterdayData['weight'], 0) }} gr
                    </p>
                </div>
            </div>
            @endif --}}

            <!-- Yesterday Mortality -->
            {{-- @if($yesterdayData['total_depletion'] > 0)
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
            @endif --}}

            <!-- Yesterday Sales -->
            {{-- @if(isset($yesterdayData['sales']) && $yesterdayData['sales']['quantity'] > 0)
            <div class="bg-white rounded-lg p-3 border border-blue-100 flex items-center">
                <i class="fas fa-dollar-sign mr-2"></i>
                <div>
                    <p class="text-xs text-gray-500">Penjualan Kemarin</p>
                    <p class="text-sm font-semibold text-gray-800">
                        💰 {{ $yesterdayData['sales']['quantity'] }} Ekor
                        @if($yesterdayData['sales']['weight'] > 0)
                        <br><span class="text-xs text-gray-600">{{ number_format($yesterdayData['sales']['weight'], 0)
                            }} Kg</span>
                        @endif
                    </p>
                    <p class="text-xs text-gray-400">
                        @if($yesterdayData['sales']['status'] === 'finalized')
                        <span class="text-green-600">✓ Final</span>
                        @else
                        <span class="text-orange-600">📝 Draft</span>
                        @endif
                    </p>
                </div>
            </div>
            @endif --}}

            <!-- Yesterday Feed Usage -->
            {{-- @if($yesterdayData['feed_usage']['total_quantity'] > 0)
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
            @endif --}}

            <!-- Yesterday Supply Usage -->
            {{-- @if($yesterdayData['supply_usage']['total_quantity'] > 0)
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
            @endif --}}
        </div>
    </div>
    @elseif($date)
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <span class="text-sm text-gray-600">Tidak ada data kemarin untuk ditampilkan</span>
        </div>
    </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Form Inputs -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Date and Weight Section -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">📅 Informasi Dasar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input.group label="📅 Tanggal">
                            <input type="date" wire:model.live="date" class="form-control"
                                min="{{ $livestockStartDate ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                            @if($livestockStartDate)
                            <small class="text-muted mt-1 d-block">
                                📅 Tanggal masuk ternak: {{ $livestockStartDate }}
                            </small>
                            @endif
                            <x-input.error for="date" />
                        </x-input.group>

                        <x-input.group label="⚖️ Berat Hari Ini (gr)">
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
                    </div>
                </div>

                <!-- Depletion and Sales Section -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">🔄 Deplesi & Penjualan</h3>

                    <!-- Total Deplesi Summary -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">⚠️ Total Deplesi (Ekor)</label>
                        @if($isManualDepletionEnabled)
                        <!-- Manual Depletion Mode - Read Only Display -->
                        <div class="form-control bg-light flex items-center justify-between">
                            <span>{{ ($mortality ?? 0) + ($culling ?? 0) }} ekor</span>
                            <span class="badge bg-info text-white">Manual</span>
                        </div>

                        <!-- Manual Depletion Notice -->
                        <div class="alert alert-info mt-2 py-2" role="alert">
                            <small class="d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <div>
                                    <strong>Mode Manual Depletion Aktif:</strong>
                                    Data deplesi dikelola melalui menu <strong>"Manual Depletion"</strong> pada tabel
                                    livestock.
                                    <br>Input deplesi di form recording ini dinonaktifkan untuk mencegah duplikasi data.
                                </div>
                            </small>
                        </div>

                        <!-- Hidden inputs for maintaining data in manual mode -->
                        <input type="hidden" wire:model="mortality">
                        <input type="hidden" wire:model="culling">
                        @else
                        <!-- Recording Mode - Editable Inputs -->
                        <div class="form-control bg-light flex items-center justify-between mb-3">
                            <span>{{ ($mortality ?? 0) + ($culling ?? 0) }} ekor</span>
                            {{-- <span class="badge bg-success text-white">Recording</span> --}}
                        </div>

                        <!-- Depletion Input Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label text-sm">💀 Mati (Ekor)</label>
                                <input type="number" wire:model.live="mortality" class="form-control form-control-sm"
                                    min="0" placeholder="0" value="{{ $mortality ?? 0 }}">
                                <x-input.error for="mortality" />
                            </div>
                            <div>
                                <label class="form-label text-sm">🛑 Afkir (Ekor)</label>
                                <input type="number" wire:model.live="culling" class="form-control form-control-sm"
                                    min="0" placeholder="0" value="{{ $culling ?? 0 }}">
                                <x-input.error for="culling" />
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Sales Input Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label text-sm">💰 Jumlah Terjual (Ekor)</label>
                            <input type="number" wire:model.live="sales_quantity" class="form-control form-control-sm"
                                min="0" placeholder="0" value="{{ $sales_quantity ?? 0 }}">
                            <x-input.error for="sales_quantity" />
                        </div>
                        <div>
                            <label class="form-label text-sm">⚖️ Berat Terjual (Kg)</label>
                            <input type="number" wire:model.live="sales_weight" class="form-control form-control-sm"
                                min="0" placeholder="0" value="{{ $sales_weight ?? 0 }}">
                            <x-input.error for="sales_weight" />
                        </div>
                    </div>

                    <!-- Sales Status Display -->
                    @if(isset($sales_status) && $sales_quantity > 0)
                    <div class="mt-3">
                        <div class="alert {{ $sales_status === 'finalized' ? 'alert-success' : 'alert-warning' }} py-2"
                            role="alert">
                            <small class="d-flex align-items-center">
                                <i
                                    class="fas {{ $sales_status === 'finalized' ? 'fa-check-circle' : 'fa-clock' }} me-2"></i>
                                <div>
                                    <strong>Status Penjualan:</strong>
                                    @if($sales_status === 'finalized')
                                    <span class="text-success">✓ Final - Data penjualan telah difinalisasi</span>
                                    @else
                                    <span class="text-warning">📝 Draft - Data penjualan masih dalam status draft</span>
                                    @endif
                                </div>
                            </small>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Feed Usage Section -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    @if(!$isManualFeedUsageEnabled && Auth::user()->can('create feed usage'))
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">📦 Penggunaan Pakan</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item
                                        Pakan</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        Stock
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
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
                                    <td class="px-3 py-3 text-sm font-medium text-gray-900">
                                        {{ $item['item_name'] }}
                                    </td>
                                    <td class="px-3 py-3 text-sm">
                                        <span
                                            class="{{ $availableStock > 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium' }}">
                                            {{ number_format($availableStock, 0) }} Kg
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="number"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            wire:model="itemQuantities.{{ $item['item_id'] }}" min="0"
                                            max="{{ $availableStock }}" placeholder="0" {{ $availableStock <=0
                                            ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-4 text-center text-gray-500 text-sm">
                                        Tidak ada stok pakan tersedia
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @elseif($isManualFeedUsageEnabled)
                    <!-- Manual Feed Usage Notice -->
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">📦 Penggunaan Pakan</h3>
                    <div class="bg-blue-50 border border-blue-200 rounded p-4">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                            <div>
                                <strong>Mode Manual Feed Usage Aktif:</strong><br>
                                <small class="text-gray-600">
                                    Data penggunaan pakan dicatat melalui menu <strong>"Manual Usage"</strong> pada
                                    tabel livestock.
                                    Input otomatis di form ini dinonaktifkan untuk mencegah duplikasi data.
                                </small>
                            </div>
                        </div>
                    </div>
                    @elseif(!Auth::user()->can('create feed usage'))
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">📦 Penggunaan Pakan</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
                        <p class="text-sm text-yellow-800">Anda tidak memiliki izin untuk membuat penggunaan pakan.
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Summary Dashboard -->
            {{-- <div class="lg:col-span-4 space-y-6">
                <!-- Quick Stats Summary -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">📊 Ringkasan Hari Ini</h3>

                    <!-- Current Input Summary -->
                    <div class="space-y-4">
                        <!-- Weight Summary -->
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-weight-hanging text-blue-600 mr-2"></i>
                                <span class="text-sm text-gray-700">Berat Saat Ini</span>
                            </div>
                            <span class="font-semibold text-blue-800">
                                {{ $weight_today ? number_format($weight_today, 0) . ' gr' : '- gr' }}
                            </span>
                        </div>

                        <!-- Depletion Summary -->
                        <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-skull-crossbones text-red-600 mr-2"></i>
                                <span class="text-sm text-gray-700">Total Deplesi</span>
                            </div>
                            <span class="font-semibold text-red-800">
                                {{ ($mortality ?? 0) + ($culling ?? 0) }} ekor
                            </span>
                        </div>

                        <!-- Sales Summary -->
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                                <span class="text-sm text-gray-700">Penjualan</span>
                            </div>
                            <span class="font-semibold text-green-800">
                                {{ $sales_quantity ?? 0 }} ekor
                            </span>
                        </div>

                        <!-- Feed Usage Summary -->
                        @if(!$isManualFeedUsageEnabled)
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-utensils text-yellow-600 mr-2"></i>
                                <span class="text-sm text-gray-700">Pakan Hari Ini</span>
                            </div>
                            <span class="font-semibold text-yellow-800">
                                @php
                                $totalFeed = 0;
                                if(isset($itemQuantities) && is_array($itemQuantities)) {
                                $totalFeed = array_sum($itemQuantities);
                                }
                                @endphp
                                {{ number_format($totalFeed, 1) }} kg
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Livestock Summary -->
                @isset($livestockSummary)
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">🐔 Status Ternak</h3>
                    <div class="text-xs text-gray-500 mb-3">Hingga {{ $livestockSummary['date'] }}</div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Sisa Ternak</span>
                            <span class="font-semibold text-blue-600">{{ number_format($livestockSummary['remaining'])
                                }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total Terjual</span>
                            <span class="font-semibold text-green-600">{{ number_format($livestockSummary['sales'])
                                }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total Deplesi</span>
                            <span class="font-semibold text-red-600">{{ number_format($livestockSummary['deplesi'])
                                }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Feed Usage</span>
                            <span class="font-semibold text-yellow-600">{{
                                number_format($livestockSummary['feed_usage'], 1) }} kg</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Supply Usage</span>
                            <span class="font-semibold text-purple-600">{{
                                number_format($livestockSummary['supply_usage'], 1) }} unit</span>
                        </div>
                    </div>
                </div>
                @endisset

                <!-- Weight Comparison -->
                @if($weight_yesterday > 0 && $weight_today > 0)
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">📈 Perbandingan Berat</h3>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Kemarin</span>
                            <span class="font-semibold">{{ number_format($weight_yesterday, 0) }} gr</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Hari Ini</span>
                            <span class="font-semibold">{{ number_format($weight_today, 0) }} gr</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Selisih</span>
                            @php
                            $weightDiff = $weight_today - $weight_yesterday;
                            $isPositive = $weightDiff >= 0;
                            @endphp
                            <span class="font-semibold {{ $isPositive ? 'text-green-600' : 'text-red-600' }}">
                                {{ $isPositive ? '+' : '' }}{{ number_format($weightDiff, 0) }} gr
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Mode Status -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700 border-b pb-2">⚙️ Status Mode</h3>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Deplesi</span>
                            @if($isManualDepletionEnabled)
                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Manual</span>
                            @else
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Recording</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Feed Usage</span>
                            @if($isManualFeedUsageEnabled)
                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Manual</span>
                            @else
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Recording</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <div>
                <!-- Additional actions can go here -->
            </div>
            <div class="flex space-x-3">
                <button type="button" wire:click="closeForm" class="btn btn-outline-danger px-6 py-2">
                    Kembali ke Tabel
                </button>
                @if($isEditing)
                <button type="submit" class="btn btn-primary px-6 py-2" id='submitData'>
                    💾 Ubah Data
                </button>
                @else
                <button type="submit" class="btn btn-primary px-6 py-2" id='submitData'>
                    💾 Simpan Data
                </button>
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

        // Listen for data refresh events
        Livewire.on('refreshData', () => {
            console.log('🔄 Refreshing data after save...');
            // Force reload the page to ensure fresh data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });

        // Listen for data saved events
        Livewire.on('data-saved', () => {
            console.log('✅ Data saved successfully, refreshing...');
            // Force reload the page to ensure fresh data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        });

        // Listen for warning events (batch allocation errors)
        Livewire.on('warning', (data) => {
            console.log('⚠️ Warning received:', data);
            
            // Show warning modal/alert
            if (data.title && data.message) {
                // Use SweetAlert2 if available, otherwise use browser alert
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: data.title,
                        html: data.message.replace(/\n/g, '<br>'),
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#f59e0b',
                        customClass: {
                            popup: 'swal2-warning-popup'
                        }
                    });
                } else {
                    alert(`${data.title}\n\n${data.message}`);
                }
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
            border: 1px solid #d1d5db;
            border-radius: 6px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control-sm {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control-sm:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }

        .form-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: #374151;
        }

        .error-text {
            font-size: 12px;
            color: #ef4444;
            margin-top: 4px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .btn-primary:disabled {
            background-color: #9ca3af;
            border-color: #9ca3af;
            cursor: not-allowed;
        }

        .btn-outline-danger {
            background-color: transparent;
            color: #ef4444;
            border-color: #ef4444;
        }

        .btn-outline-danger:hover {
            background-color: #ef4444;
            color: white;
        }

        .btn-outline-primary {
            background-color: transparent;
            color: #3b82f6;
            border-color: #3b82f6;
        }

        .btn-outline-primary:hover {
            background-color: #3b82f6;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 9999px;
        }

        .bg-info {
            background-color: #3b82f6;
        }

        .bg-success {
            background-color: #10b981;
        }

        .bg-secondary {
            background-color: #6b7280;
        }

        .text-white {
            color: white;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .alert-info {
            background-color: #dbeafe;
            border-color: #93c5fd;
            color: #1e40af;
        }

        .alert-success {
            background-color: #d1fae5;
            border-color: #a7f3d0;
            color: #065f46;
        }

        .alert-warning {
            background-color: #fef3c7;
            border-color: #fcd34d;
            color: #92400e;
        }

        .text-green-600 {
            color: #059669;
        }

        .text-red-600 {
            color: #dc2626;
        }

        .text-blue-600 {
            color: #2563eb;
        }

        .text-yellow-600 {
            color: #d97706;
        }

        .text-purple-600 {
            color: #9333ea;
        }

        .text-success {
            color: #059669;
        }

        .text-warning {
            color: #d97706;
        }

        .text-muted {
            color: #6b7280;
        }

        .bg-light {
            background-color: #f9fafb;
        }

        /* Custom scrollbar for tables */
        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }

        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Warning popup styles */
        .swal2-warning-popup {
            border-left: 4px solid #f59e0b;
        }

        .swal2-warning-popup .swal2-title {
            color: #92400e;
        }

        .swal2-warning-popup .swal2-html-container {
            text-align: left;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Animation for cards */
        .bg-white {
            transition: box-shadow 0.2s;
        }

        .bg-white:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .grid.grid-cols-1.lg\\:grid-cols-12 {
                display: block;
            }

            .lg\\:col-span-8,
            .lg\\:col-span-4 {
                width: 100%;
            }

            .btn {
                width: 100%;
                margin-bottom: 8px;
            }

            .flex.justify-between {
                flex-direction: column;
                gap: 16px;
            }
        }
    </style>
    @endpush
    @endif
</div>