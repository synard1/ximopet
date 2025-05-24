<div>
    @if ($showForm)
    <form wire:submit.prevent="save">
        <div class="row g-3">
            <x-input.group col="6" label="Tanggal">
                <input wire:model="date" id="date" class="form-control form-control-solid" placeholder="Tanggal"
                    @if($edit_mode==true) readonly disabled @endif x-data x-init="flatpickr($el, {
                        enableTime: true,
                        dateFormat: 'Y-m-d',
                        defaultDate: '{{ $date }}',
                    })">
                <x-input.error for="date" />
            </x-input.group>

            <x-input.group col="6" label="Nomor Invoice">
                <input type="text" wire:model="invoice_number" class="form-control">
                <x-input.error for="invoice_number" />
            </x-input.group>

            <x-input.group col="6" label="Supplier">
                <select wire:model="supplier_id" class="form-select">
                    <option value="">-- Pilih Supplier --</option>
                    @foreach ($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @if($supplier_id==$vendor->id) selected @endif>
                        {{ $vendor->name }}
                    </option>
                    @endforeach
                </select>
                <x-input.error for="supplier_id" />
            </x-input.group>

            <x-input.group col="6" label="Ekspedisi">
                <select wire:model="expedition_id" class="form-select">
                    <option value="">-- Pilih Ekspedisi --</option>
                    @foreach ($expeditions as $expedition)
                    <option value="{{ $expedition->id }}">{{ $expedition->name }}</option>
                    @endforeach
                </select>
                <x-input.error for="expedition_id" />
            </x-input.group>

            <x-input.group col="6" label="Farm">
                <select wire:model="farm_id" class="form-select">
                    <option value="">-- Pilih Farm --</option>
                    @foreach ($farms as $farm)
                    <option value="{{ $farm->id }}" @if($farm_id==$farm->id) selected @endif>
                        {{ $farm->name }}
                    </option>
                    @endforeach
                </select>
                <x-input.error for="farm_id" />
            </x-input.group>

            <x-input.group col="6" label="Kandang">
                <select wire:model="kandang_id" class="form-select" @if(!$farm_id) disabled @endif>
                    <option value="">-- Pilih Kandang --</option>
                    @foreach ($kandangs as $kandang)
                    <option value="{{ $kandang->id }}" @if($kandang_id==$kandang->id) selected @endif>
                        {{ $kandang->nama }}
                    </option>
                    @endforeach
                </select>
                <x-input.error for="kandang_id" />
            </x-input.group>

            <x-input.group col="6" label="Biaya Ekspedisi">
                <input type="number" step="0.01" wire:model="expedition_fee" class="form-control">
                <x-input.error for="expedition_fee" />
            </x-input.group>
        </div>

        <hr class="my-4">

        <h5 class="fw-semibold text-primary"><i class="bi bi-box-seam me-2"></i>Detail Livestock</h5>

        @foreach ($items as $index => $item)
        <div class="row g-3 mb-3 p-3 border rounded bg-light position-relative">
            @if (!empty($errorItems[$index]))
            <div class="alert alert-danger py-1 px-2 mb-2">{{ $errorItems[$index] }}</div>
            @endif

            <x-input.group col="4" label="Jenis Breed">
                <select class="form-select" wire:model="items.{{ $index }}.livestock_breed_id">
                    <option value="">-- Pilih Breed --</option>
                    @foreach ($breeds as $breed)
                    <option value="{{ $breed->id }}">{{ $breed->name }}</option>
                    @endforeach
                </select>
                <x-input.error for="items.{{ $index }}.livestock_breed_id" />
            </x-input.group>

            <x-input.group col="4" label="Jumlah">
                <input type="number" step="1" wire:model="items.{{ $index }}.jumlah" class="form-control" />
                <x-input.error for="items.{{ $index }}.jumlah" />
            </x-input.group>

            <x-input.group col="3" label="Harga per Ekor">
                <input type="number" step="0.01" wire:model="items.{{ $index }}.harga_per_ekor" class="form-control" />
                <x-input.error for="items.{{ $index }}.harga_per_ekor" />
            </x-input.group>

            <div class="col-md-1 d-flex align-items-end justify-content-end">
                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>
        @endforeach

        <div class="mb-4">
            <button type="button" wire:click="addItem" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Tambah Livestock
            </button>
        </div>

        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary" wire:click="cancel">Cancel</button>
            <button type="submit" class="btn btn-warning text-white">
                <i class="bi bi-save me-1"></i> Simpan Pembelian
            </button>
        </div>
    </form>
    @endif
</div>