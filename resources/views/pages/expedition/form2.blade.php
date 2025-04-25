<form wire:submit.prevent="save" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-gray-600 font-medium">📅 Tanggal</label>
            <input type="date" 
                wire:model.live="date" 
                class="form-input" 
                max="{{ date('Y-m-d') }}">            
            @error('date') <span class="error-text">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-gray-600 font-medium">Berat Hari Ini (gr)</label>
            <input type="number" step="0.01" wire:model="weight_today" class="form-input" placeholder="Berat ayam hari ini">
            @error('weight_today') <span class="error-text">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-gray-600 font-medium">💀 Mati (Ekor)</label>
            <input type="number" wire:model="mortality" class="form-input" placeholder="Jumlah ayam mati">
        </div>
        <div>
            <label class="block text-gray-600 font-medium">🛑 Afkir (Ekor)</label>
            <input type="number" wire:model="culling" class="form-input" placeholder="Ayam tidak layak">
        </div>
        <div>
            <label class="block text-gray-600 font-medium">Jumlah Terjual (Ekor)</label>
            <input type="number" wire:model.live="sales_quantity" class="form-input" placeholder="Jumlah ayam terjual">
            @error('sales_quantity') <span class="error-text">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-gray-600 font-medium">Berat Terjual (Ekor)</label>
            <input type="number" wire:model.live="sales_weight" class="form-input" placeholder="Berat ayam terjual">
            @error('sales_weight') <span class="error-text">{{ $message }}</span> @enderror
        </div>

    </div>

    <!-- Tabel Penggunaan Item -->
    <div class="mt-6">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
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
                            <input type="number" 
                                class="form-input qty-input w-24"
                                wire:model="itemQuantities.{{ $item['item_id'] }}"
                                min="0"
                                max="{{ $availableStock }}"
                                {{ $availableStock <= 0 ? 'disabled' : '' }}>
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

    <!-- Item Selection Table -->
    {{-- <div class="mt-6">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 text-left">Item</th>
                    <th class="px-4 py-2 text-left">Stock Tersedia</th>
                    <th class="px-4 py-2 text-left">Jumlah Digunakan</th>
                    <th class="px-4 py-2 text-left">Sisa Stock</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $item['item_name'] }}</td>
                        <td class="px-4 py-2">{{ $item['stock'] }}</td>
                        <td class="px-4 py-2">
                            <input type="number" 
                                class="form-input qty-input" 
                                wire:model.live="itemQuantities.{{ $item['item_id'] }}"
                                data-item-id="{{ $item['item_id'] }}"
                                data-stock="{{ $item['stock'] }}"
                                {{ $item['stock'] <= 0 ? 'disabled' : '' }}
                                min="0"
                                max="{{ $item['stock'] }}"
                                placeholder="0">
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" 
                                class="form-input qty-input" 
                                wire:model.live="itemQuantities.{{ $item['item_sisa'] }}"
                                readonly>
                        </td>
                        <td class="px-4 py-2">
                            {{ $item['stock'] - ($previousItemQuantities[$item['item_id']] ?? 0) }}                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div> --}}

    <!-- Tombol Simpan -->
    <div class="flex justify-end mt-6">
    <!-- Tombol Simpan -->
        @if($isEditing)
            <button type="submit" class="btn-primary" id='submitData'>💾 Ubah Data</button>
        @else
            <button type="submit" class="btn-primary" id='submitData'>💾 Simpan Data</button>

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