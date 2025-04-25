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
        {{-- <div>
            <label class="block text-gray-600 font-medium">🐥 Umur (Hari)</label>
            <input type="number" wire:model="age" class="form-input" placeholder="Misal: 20">
            @error('age') <span class="error-text">{{ $message }}</span> @enderror
        </div> --}}
        <div>
            <label class="block text-gray-600 font-medium">📦 Stock Awal Ayam</label>
            <input type="number" wire:model="stock_start" class="form-input" placeholder="Jumlah ayam awal" readonly>
            @error('stock_start') <span class="error-text">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-gray-600 font-medium">📦 Stock Akhir Ayam</label>
            <input type="number" wire:model="stock_end" class="form-input" placeholder="Sisa ayam saat ini" readonly>
            @error('stock_end') <span class="error-text">{{ $message }}</span> @enderror
        </div>
    </div>

    <!-- Berat Badan -->
    <div class="section-header">⚖️ Berat Badan</div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-gray-600 font-medium">Berat Semalam (gr)</label>
            <input type="number" step="0.01" wire:model="weight_yesterday" class="form-input bg-gray-100" placeholder="Berat ayam kemarin" readonly>
            @error('weight_yesterday') <span class="error-text">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-gray-600 font-medium">Berat Hari Ini (gr)</label>
            <input type="number" step="0.01" wire:model="weight_today" class="form-input" placeholder="Berat ayam hari ini">
            @error('weight_today') <span class="error-text">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-gray-600 font-medium">Kenaikan Berat (gr)</label>
            <input type="number" step="0.01" wire:model="weight_gain" class="form-input bg-gray-100" readonly>
            @error('weight_gain') <span class="error-text">{{ $message }}</span> @enderror
        </div>
    </div>

    <!-- Deplesi -->
    <div class="section-header">🚨 Deplesi</div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-gray-600 font-medium">💀 Mati (Ekor)</label>
            <input type="number" wire:model="mortality" class="form-input" placeholder="Jumlah ayam mati">
        </div>
        <div>
            <label class="block text-gray-600 font-medium">🛑 Afkir (Ekor)</label>
            <input type="number" wire:model="culling" class="form-input" placeholder="Ayam tidak layak">
        </div>
        <div>
            <label class="block text-gray-600 font-medium">📉 Total Deplesi</label>
            <input type="number" wire:model="total_deplesi" class="form-input bg-gray-100" readonly>
        </div>
    </div>

    <!-- Penjualan -->
    <div class="section-header">💰 Penjualan</div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-gray-600 font-medium">Jumlah Terjual (Ekor)</label>
            <input type="number" wire:model.live="sales_quantity" class="form-input" placeholder="Jumlah ayam terjual">
            @error('sales_quantity') <span class="error-text">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-gray-600 font-medium">Harga Jual (Rp/Ekor)</label>
            <input type="number" wire:model.live="sales_price" class="form-input" placeholder="Harga per ekor">
            @error('sales_price') <span class="error-text">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-gray-600 font-medium">Total Penjualan (Rp)</label>
            <input type="number" wire:model="total_sales" class="form-input bg-gray-100" readonly>
            @error('total_sales') <span class="error-text">{{ $message }}</span> @enderror
        </div>
    </div>

    <!-- Item Selection Table -->
    <div class="mt-6">
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
                            {{ $item['stock'] - ($itemQuantities[$item['item_id']] ?? 0) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Tombol Simpan -->
    <div class="flex justify-end mt-6">
        <button type="submit" class="btn-primary" id='submitData'>💾 Simpan Data</button>
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