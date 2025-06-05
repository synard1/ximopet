<div id="livewireRecordsContainer">
    @if($showForm)
    <h2 class="text-2xl font-bold mb-8 text-gray-800">📋 Manajemen Recording Ayam</h2>

    <form wire:submit.prevent="save" class="space-y-8">
        <div class="row g-3">
            <x-input.group col="6" label="📅 Tanggal">
                <input type="date" wire:model.live="date" class="form-control" max="{{ date('Y-m-d') }}">
                <x-input.error for="date" />
            </x-input.group>

            <x-input.group col="6" label="Berat Hari Ini (gr)">
                <input type="number" step="0.01" wire:model="weight_today" class="form-control"
                    placeholder="Berat ayam hari ini">
                <x-input.error for="weight_today" />
            </x-input.group>

            <x-input.group col="6" label="💀 Mati (Ekor)">
                <input type="number" wire:model="mortality" class="form-control" placeholder="Jumlah ayam mati">
            </x-input.group>

            <x-input.group col="6" label="🛑 Afkir (Ekor)">
                <input type="number" wire:model="culling" class="form-control" placeholder="Ayam tidak layak">
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

        <!-- Tabel Penggunaan Item -->
        <div class="mt-8">
            @if(Auth::user()->can('create feed usage'))
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
                            <input type="number" class="form-control qty-input w-24 rounded-lg border-gray-300"
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
            @else
            <div class="alert alert-warning" role="alert">
                Anda tidak memiliki izin untuk membuat penggunaan pakan.
            </div>
            @endif
        </div>

        <!-- Tombol Simpan -->
        <div class="d-flex justify-content-end my-4">
            <button wire:click="closeForm" class="btn btn-danger rounded-lg px-6 py-2">Kembali ke Tabel</button>
            <!-- Tombol Simpan -->
            @if($isEditing)
            <button type="submit" class="btn btn-primary rounded-lg px-6 py-2" id='submitData'>💾 Ubah Data</button>
            @else
            <button type="submit" class="btn btn-primary rounded-lg px-6 py-2" id='submitData'>💾 Simpan Data</button>
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
    </style>
    @endpush
    @endif
</div>