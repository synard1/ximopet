<div>
    <form wire:submit.prevent="savePurchase">
        @foreach ($items as $index => $item)
            <div class="row mb-3">
                <div class="col-md-4">
                    <select wire:model="items.{{ $index }}.item_id" class="form-control select2" data-index="{{ $index }}">
                        <option value="">Pilih Item</option>
                        @foreach($allItems as $availableItem)
                            <option value="{{ $availableItem->id }}">{{ $availableItem->itemCategory->name .' - '.$availableItem->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" wire:model="items.{{ $index }}.qty" class="form-control" placeholder="Qty">
                </div>
                <div class="col-md-3">
                    <input type="number" wire:model="items.{{ $index }}.harga" class="form-control" placeholder="Harga">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger" wire:click="removeItem({{ $index }})">Hapus</button>
                </div>
            </div>
        @endforeach

        <button type="button" class="btn btn-primary" wire:click="addItem">Tambah Item</button>
        <button type="submit" class="btn btn-success">Simpan Pembelian</button>
    </form>
    @push('scripts')
    <script>
        $(document).ready(function() {
            initializeSelect2();
            Livewire.hook('message.processed', () => {
                initializeSelect2();
            });
        });

        function initializeSelect2() {
            console.log('select2 initialized');
            
            setTimeout(function() {
                $('.select2').each(function() {
                    let index = $(this).data('index');
                    $(this).select2();
                    $(this).on('change', function() {
                        @this.set('items.' + index + '.item_id', $(this).val());
                    });
                });
            }, 100); // Tunda 100 milidetik
        }

        

            document.addEventListener('livewire:init', function () {
                Livewire.on('select2-initial', () => {
                    initializeSelect2();
                });

            });
    </script>
    @endpush
</div>