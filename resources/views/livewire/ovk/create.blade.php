<div>
    @if ($showForm)
    <form wire:submit.prevent="save">

        <div class="row g-3">

            <x-input.group col="6" label="Tanggal">

                {{-- <input type="date" wire:model="date" class="form-control"> --}}
                <input wire:model.live="date" id="date" class="form-control form-control-solid" placeholder="Tanggal"
                    @if($edit_mode==true) readonly disabled @endif x-data x-init="flatpickr($el, {
                            enableTime: true,
                            dateFormat: 'Y-m-d',
                            defaultDate: '{{ $date }}', // Set initial date from Livewire
                        })">


                <x-input.error for="date" />


            </x-input.group>


            <x-input.group col="6" label="Batch Ayam">
                <select wire:model.live="livestock_id" class="form-select" @if($edit_mode) disabled @endif>
                    <option value="">-- Pilih Batch Ayam --</option>
                    @foreach ($livestocks as $livestock)
                    <option value="{{ $livestock->id }}" @if($livestock_id==$livestock->id) selected @endif>{{
                        $livestock->name }}</option>
                    @endforeach
                </select>
                <x-input.error for="farm_id" />
            </x-input.group>

        </div>




        <hr class="my-4">



        <div class="card mb-4 {{ !$requiredFieldsFilled ? 'opacity-50' : '' }}">
            <h5 class="fw-semibold text-primary"><i class="bi bi-box-seam me-2"></i>Detail Supply</h5>

            @foreach ($items as $index => $item)
            <div class="row g-3 mb-3 p-3 border rounded bg-light position-relative">
                @if (!empty($errorItems[$index]))
                <div class="alert alert-danger py-1 px-2 mb-2">{{ $errorItems[$index] }}</div>
                @endif

                <x-input.group col="4" label="Supply">
                    <select class="form-select" wire:model.live="items.{{ $index }}.supply_id">
                        <option value="">-- Pilih Item --</option>
                        @foreach($availableItems as $available)
                        <option value="{{ $available['id'] }}">{{ $available['name'] }} ({{ $available['type'] }})
                        </option>
                        @endforeach
                    </select>

                    <x-input.error for="items.{{ $index }}.supply_id" />
                </x-input.group>

                <x-input.group col="4" label="Jumlah">
                    <div class="input-group">
                        <input type="number" step="0.01" wire:model.live="items.{{ $index }}.quantity"
                            class="form-control" wire:change="updateUnitConversion({{ $index }})" />

                        <select wire:model.live="items.{{ $index }}.unit_id" class="form-select"
                            wire:change="updateUnitConversion({{ $index }})">
                            <option value="">-- Satuan --</option>
                            @foreach ($items[$index]['available_units'] ?? [] as $unitOption)
                            <option value="{{ $unitOption['unit_id'] }}">{{ $unitOption['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-input.error for="items.{{ $index }}.quantity" />
                    <x-input.error for="items.{{ $index }}.unit_id" />

                    @if (!empty($errorItems[$index]))
                    <div class="invalid-feedback d-block">
                        {{ $errorItems[$index] }}
                    </div>
                    @endif
                </x-input.group>

                <x-input.group col="3" label="Stock Tersedia">
                    <input type="number" class="form-control" value="{{ $items[$index]['available_stock'] ?? '0' }}"
                        readonly disabled>
                    <small class="text-muted">Sisa stock: {{ $items[$index]['current_supply'] ?? '0' }}</small>
                </x-input.group>

                <div class="col-md-1 d-flex align-items-end justify-content-end">
                    <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-outline-danger btn-sm" {{
                        !$requiredFieldsFilled ? 'disabled' : '' }}>
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>

            </div>
            @endforeach

            <div class="mb-4">
                <button type="button" wire:click="addItem" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Supply
                </button>
            </div>
        </div>



        <div class="d-flex justify-content-end">

            <button type="button" class="btn btn-secondary" wire:click="cancel">Cancel</button>



            <button type="submit" class="btn btn-warning text-white">

                <i class="bi bi-save me-1"></i> Simpan

            </button>

        </div>


    </form>
    @endif
</div>