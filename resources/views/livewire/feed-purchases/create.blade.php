<div>
    @if ($showForm)
    <form wire:submit.prevent="save">
        <div class="row g-3">
            <x-input.group col="6" label="Tanggal">
                <input wire:model="date" id="date" class="form-control form-control-solid" placeholder="Tanggal"
                    @if($edit_mode==true) readonly disabled @endif x-data x-init="flatpickr($el, {
                            enableTime: true,
                            dateFormat: 'Y-m-d',
                            defaultDate: '{{ $date }}', // Set initial date from Livewire
                        })">
                <x-input.error for="date" />
            </x-input.group>

            <x-input.group col="6" label="Nomor Invoice">
                <input type="text" wire:model="invoice_number" class="form-control" @if($this->isDisabled()) disabled
                @endif>
                <x-input.error for="invoice_number" />
            </x-input.group>

            <x-input.group col="6" label="Supplier">
                <select wire:model="supplier_id" class="form-select" @if($this->isDisabled()) disabled @endif>
                    <option value="">-- Pilih Supplier --</option>
                    @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @if($supplier_id==$vendor->id) selected @endif>{{ $vendor->name }}
                    </option>
                    @endforeach
                </select>
                <x-input.error for="supplier_id" />
            </x-input.group>

            <x-input.group col="6" label="Ekspedisi">
                <select wire:model="master_expedition_id" class="form-select" @if($this->isDisabled()) disabled @endif>
                    <option value="">-- Pilih Ekspedisi --</option>
                    @foreach ($expeditions as $expedition)
                    <option value="{{ $expedition->id }}">{{ $expedition->name }}
                    </option>
                    @endforeach
                </select>
                <x-input.error for="master_expedition_id" />
            </x-input.group>

            <x-input.group col="6" label="Batch Ayam">
                <select wire:model="livestock_id" class="form-select" @if($this->isDisabled()) disabled @endif>
                    <option value="">-- Pilih Batch Ayam --</option>
                    @foreach ($livestocks as $livestock)
                    <option value="{{ $livestock->id }}" @if($livestock_id==$livestock->id) selected @endif>{{
                        $livestock->name }}</option>
                    @endforeach
                </select>
                <x-input.error for="livestock_id" />
            </x-input.group>

            <x-input.group col="6" label="Biaya Ekspedisi">
                <input type="number" step="0.01" wire:model="expedition_fee" class="form-control"
                    @if($this->isDisabled()) disabled @endif>
                <x-input.error for="expedition_fee" />
            </x-input.group>
        </div>

        <hr class="my-4">
        <h5 class="fw-semibold text-primary"><i class="bi bi-box-seam me-2"></i>Detail Pakan</h5>

        @foreach ($items as $index => $item)
        <div class="row g-3 mb-3 p-3 border rounded bg-light position-relative">
            @if (!empty($errorItems[$index]))
            <div class="alert alert-danger py-1 px-2 mb-2">{{ $errorItems[$index] }}</div>
            @endif

            <x-input.group col="4" label="Jenis Pakan">
                <select class="form-select" @if($this->isDisabled()) disabled @endif
                    wire:model="items.{{ $index }}.feed_id"
                    wire:change="updateUnitConversion({{ $index }})">

                    <option value="">-- Pilih --</option>
                    @foreach ($feedItems as $feed)
                    <option value="{{ $feed->id }}">{{ $feed->name }}</option>
                    @endforeach
                </select>
                <x-input.error for="items.{{ $index }}.feed_id" />
            </x-input.group>

            <x-input.group col="4" label="Jumlah">
                <div class="input-group">
                    <input type="number" step="0.01" wire:model="items.{{ $index }}.quantity" class="form-control"
                        wire:change="updateUnitConversion({{ $index }})" @if($this->isDisabled()) disabled @endif />

                    <select wire:model="items.{{ $index }}.unit_id" class="form-select"
                        wire:change="updateUnitConversion({{ $index }})" @if($this->isDisabled()) disabled @endif>
                        <option value="">-- Satuan --</option>
                        @foreach ($items[$index]['available_units'] ?? [] as $unitOption)
                        <option value="{{ $unitOption['unit_id'] }}">{{ $unitOption['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input.error for="items.{{ $index }}.quantity" />
                <x-input.error for="items.{{ $index }}.unit_id" />
            </x-input.group>

            <x-input.group col="3" label="Harga per Unit">
                <input type="number" step="0.01" wire:model="items.{{ $index }}.price_per_unit" class="form-control"
                    @if($this->isDisabled()) disabled @endif />
                <x-input.error for="items.{{ $index }}.price_per_unit" />
            </x-input.group>

            @if(!$this->isDisabled())
            <div class="col-md-1 d-flex align-items-end justify-content-end">
                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
            @endif
        </div>
        @endforeach

        <div class="mb-4">
            @if($this->isDisabled())
            <button type="button" class="btn btn-success" disabled>
                <i class="bi bi-plus-circle me-1"></i> Tambah Pakan
            </button>
            @else
            <button type="button" wire:click="addItem" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Tambah Pakan
            </button>
            @endif
        </div>
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary" wire:click="cancel">Cancel</button>
            @if(!$this->isDisabled())
            @can('create feed purchasing')
            <button type="submit" class="btn btn-warning text-white">
                <i class="bi bi-save me-1"></i> Simpan Pembelian
            </button>
            @endcan
            @endif
        </div>
    </form>
    @endif
</div>