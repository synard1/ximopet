<div x-data="{ errors: [] }" x-on:validation-errors.window="errors = $event.detail.errors">
    <template x-if="errors.length > 0">
        <div class="alert alert-danger mb-3" x-show="errors.length > 0">
            <ul class="mb-0">
                <template x-for="error in errors" :key="error">
                    <li x-text="error"></li>
                </template>
            </ul>
            <button type="button" class="btn-close float-end" aria-label="Close" @click="errors = []"></button>
        </div>
    </template>

    @if ($showForm)
    <form wire:submit.prevent="save" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="card p-4 shadow-sm rounded overflow-auto" style="max-height: 400px;">
                <h5 class="fw-semibold text-primary">Informasi Pembelian</h5>
                <x-input.group label="Tanggal">
                    <input wire:model="date" id="date" class="form-control form-control-solid" placeholder="Tanggal"
                        @if($edit_mode) readonly required @endif @if($edit_mode) style="pointer-events: none;" @endif
                        x-data x-init="flatpickr($el, {
                            enableTime: true,
                            dateFormat: 'Y-m-d',
                            defaultDate: '{{ $date }}',
                        })">
                    <x-input.error for="date" />
                </x-input.group>

                <x-input.group label="Nama Batch">
                    <input type="text" wire:model="batch_name" class="form-control"
                        placeholder="Masukkan nama batch atau kosongkan untuk otomatis" @if($edit_mode) readonly @endif>
                    <x-input.error for="batch_name" />
                </x-input.group>

                <x-input.group label="Nomor Invoice">
                    <input type="text" wire:model="invoice_number" class="form-control"
                        placeholder="Masukkan nomor invoice" @if($edit_mode) readonly required @endif>
                    <x-input.error for="invoice_number" />
                </x-input.group>

                <x-input.group label="Supplier">
                    <select wire:model="supplier_id" class="form-select" @if($edit_mode) disabled required @endif>
                        <option value="">-- Pilih Supplier --</option>
                        @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @if($supplier_id==$vendor->id) selected @endif>
                            {{ $vendor->name }}
                        </option>
                        @endforeach
                    </select>
                    <x-input.error for="supplier_id" />
                </x-input.group>

                <x-input.group label="Ekspedisi">
                    <select wire:model="expedition_id" class="form-select">
                        <option value="">-- Pilih Ekspedisi --</option>
                        @foreach ($expeditions as $expedition)
                        <option value="{{ $expedition->id }}">{{ $expedition->name }}</option>
                        @endforeach
                    </select>
                    <x-input.error for="expedition_id" />
                </x-input.group>

                <x-input.group label="Farm">
                    <select wire:model.live="farm_id" class="form-select" @if($edit_mode) disabled required @endif>
                        <option value="">-- Pilih Farm --</option>
                        @foreach ($farms as $farm)
                        <option value="{{ $farm->id }}" @if($farm_id==$farm->id) selected @endif>
                            {{ $farm->name }}
                        </option>
                        @endforeach
                    </select>
                    <x-input.error for="farm_id" />
                </x-input.group>

                <x-input.group label="Kandang">
                    <select wire:model="coop_id" class="form-select" @if(!$farm_id || $edit_mode) disabled required
                        @endif>
                        {{ print_r($coop_id); }}

                        <option value="">-- Pilih Kandang {{ print_r($coop_id); }} --</option>
                        @foreach ($coops as $coop)
                        <option value="{{ $coop->id }}" @if($coop_id==$coop->id) selected @endif>
                            {{ $coop->name }} (Sisa Kapasitas: {{ $coop->capacity - $coop->quantity }})
                        </option>
                        @endforeach
                    </select>
                    <x-input.error for="coop_id" />
                </x-input.group>

                <x-input.group label="Biaya Ekspedisi">
                    <input type="number" step="0.01" wire:model="expedition_fee" class="form-control"
                        placeholder="Masukkan biaya ekspedisi">
                    <x-input.error for="expedition_fee" />
                </x-input.group>
            </div>
        </div>

        <hr class="my-4">

        <h5 class="fw-semibold text-primary"><i class="bi bi-box-seam me-2"></i>Detail Livestock</h5>

        @foreach ($items as $index => $item)
        <div class="card mb-3 p-3 border rounded bg-light position-relative overflow-auto" style="max-height: 400px;">
            @if (!empty($errorItems[$index]))
            <div class="alert alert-danger py-1 px-2 mb-2">{{ $errorItems[$index] }}</div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <x-input.group label="Jenis Strains">
                        <select class="form-select" wire:model="items.{{ $index }}.livestock_strain_id">
                            <option value="">-- Pilih Strains --</option>
                            @foreach ($strains as $strain)
                            <option value="{{ $strain->id }}">{{ $strain->name }}</option>
                            @endforeach
                        </select>
                        <x-input.error for="items.{{ $index }}.livestock_strain_id" />
                    </x-input.group>

                    <x-input.group label="Standar Strains">
                        <select class="form-select" wire:model="items.{{ $index }}.livestock_strain_standard_id">
                            <option value="">-- Pilih Standar Strains --</option>
                            @foreach ($standardStrains as $standardStrain)
                            <option value="{{ $standardStrain->id }}">{{ $standardStrain->livestock_strain_name }}
                            </option>
                            @endforeach
                        </select>
                        <x-input.error for="items.{{ $index }}.livestock_strain_standard_id" />
                    </x-input.group>

                    <x-input.group label="Jumlah">
                        <input type="number" step="1" wire:model="items.{{ $index }}.quantity" class="form-control"
                            placeholder="Masukkan jumlah" />
                        <x-input.error for="items.{{ $index }}.quantity" />
                    </x-input.group>

                    <x-input.group label="Harga">
                        <input type="number" step="0.01" wire:model="items.{{ $index }}.price_value"
                            class="form-control" placeholder="Masukkan harga" />
                        <x-input.error for="items.{{ $index }}.price_value" />
                    </x-input.group>
                </div>

                <div class="col-md-6">
                    <x-input.group label="Tipe Harga">
                        <select class="form-select" wire:model="items.{{ $index }}.price_type">
                            <option value="">-- Pilih Tipe Harga --</option>
                            <option value="per_unit">Harga per Ekor</option>
                            <option value="total">Harga Total</option>
                        </select>
                        <x-input.error for="items.{{ $index }}.price_type" />
                    </x-input.group>

                    <x-input.group label="Berat">
                        <input type="number" step="0.01" wire:model="items.{{ $index }}.weight_value"
                            class="form-control" placeholder="Berat dalam satuan gram" />
                        <x-input.error for="items.{{ $index }}.weight_value" />
                    </x-input.group>

                    <x-input.group label="Tipe Berat">
                        <select class="form-select" wire:model="items.{{ $index }}.weight_type">
                            <option value="">-- Pilih Tipe Berat --</option>
                            <option value="per_unit">Berat per Ekor</option>
                            <option value="total">Berat Total</option>
                        </select>
                        <x-input.error for="items.{{ $index }}.weight_type" />
                    </x-input.group>

                    <x-input.group label="Catatan">
                        <input type="text" wire:model="items.{{ $index }}.notes" class="form-control"
                            placeholder="Catatan tambahan..." />
                        <x-input.error for="items.{{ $index }}.notes" />
                    </x-input.group>
                </div>
            </div>

            <div class="col-md-1 d-flex align-items-end justify-content-end">
                <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>
        @endforeach

        @if (count($items) < $maxItems) <div class="mb-4">
            <button type="button" wire:click="addItem" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Tambah Livestock
            </button>
</div>
@endif
<div class="d-flex justify-content-end">
    <button type="button" class="btn btn-secondary" wire:click="cancel">Cancel</button>
    @can('create livestock purchasing')
    <button type="submit" class="btn btn-warning text-white">
        <i class="bi bi-save me-1"></i> Simpan Pembelian
    </button>
    @endcan
</div>
</form>
@endif

@if (session()->has('error'))
<div class="alert alert-danger mt-3">
    {{ session('error') }}
</div>
@endif
</div>