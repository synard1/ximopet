<div id="livewireRecordsContainer">
    @if($showForm)
    <h2 class="text-2xl font-bold mb-8 text-gray-800">📋 Manajemen Recording Ayam</h2>

    <form wire:submit.prevent="save" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-gray-600 font-medium">📅 Tanggal</label>
                <input type="date" wire:model.live="date" class="form-input rounded-lg border-gray-300"
                    max="{{ date('Y-m-d') }}">
                @error('date') <span class="error-text text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-gray-600 font-medium">Berat Hari Ini (gr)</label>
                <input type="number" step="0.01" wire:model="weight_today" class="form-input rounded-lg border-gray-300"
                    placeholder="Berat ayam hari ini">
                @error('weight_today') <span class="error-text text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-gray-600 font-medium">💀 Mati (Ekor)</label>
                <input type="number" wire:model="mortality" class="form-input rounded-lg border-gray-300"
                    placeholder="Jumlah ayam mati">
            </div>
            <div>
                <label class="block text-gray-600 font-medium">🛑 Afkir (Ekor)</label>
                <input type="number" wire:model="culling" class="form-input rounded-lg border-gray-300"
                    placeholder="Ayam tidak layak">
            </div>
            <div>
                <label class="block text-gray-600 font-medium">Jumlah Terjual (Ekor)</label>
                <input type="number" wire:model.live="sales_quantity" class="form-input rounded-lg border-gray-300"
                    placeholder="Jumlah ayam terjual">
                @error('sales_quantity') <span class="error-text text-sm text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-gray-600 font-medium">Berat Terjual (Ekor)</label>
                <input type="number" wire:model.live="sales_weight" class="form-input rounded-lg border-gray-300"
                    placeholder="Berat ayam terjual">
                @error('sales_weight') <span class="error-text text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

        </div>

        <!-- Tabel Penggunaan Item -->
        <div class="mt-8">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 text-left">Item</th>
                        <th class="px-4 py-2 text-left">Stock Tersedia</th>
                        <th class="px-4 py-2 text-left">Jumlah Digunakan</th>
                        {{-- <th class="px-4 py-2 text-left">Sisa Stock</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    @php
                    $usedQty = $itemQuantities[$item['item_id']] ?? 0;
                    $availableStock = $item['stock'];
                    $remainingStock = $availableStock - $usedQty;
                    @endphp
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $item['item_name'] }}</td>
                        <td class="px-4 py-2">{{ $availableStock }}</td>
                        <td class="px-4 py-2">
                            <input type="number" class="form-input qty-input w-24 rounded-lg border-gray-300"
                                wire:model="itemQuantities.{{ $item['item_id'] }}" min="0" max="{{ $availableStock }}"
                                {{ $availableStock <=0 ? 'disabled' : '' }}>
                        </td>
                        {{-- <td class="px-4 py-2">
                            {{ max(0, $remainingStock) }}
                        </td> --}}
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-gray-500">Tidak ada stok tersedia.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tombol Simpan -->
        <div class="flex justify-end mt-8">
            <!-- Tombol Simpan -->
            @if($isEditing)
            <button type="submit" class="btn-primary rounded-lg px-6 py-2" id='submitData'>💾 Ubah Data</button>
            @else
            <button type="submit" class="btn-primary rounded-lg px-6 py-2" id='submitData'>💾 Simpan Data</button>

            @endif
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
    </script>
    @endpush

    @push('styles')
    <style>
        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
            border-gray-300;
        }

        .form-input:focus {
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
    </style>
    @endpush
    @endif
</div>