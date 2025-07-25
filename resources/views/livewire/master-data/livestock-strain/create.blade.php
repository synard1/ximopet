<div>
    @if ($showForm)
    <form wire:submit.prevent="save">
        {{-- === Informasi Utama Livestock Strain === --}}
        <div class="row g-3">
            <x-input.group col="6" label="Kode">
                <input type="text" wire:model="code" class="form-control" placeholder="Masukkan Kode Strain" {{
                    $edit_mode ? 'readonly disabled' : '' }}>
                <x-input.error for="code" />
            </x-input.group>

            <x-input.group col="6" label="Nama Strain">
                <input type="text" wire:model="name" class="form-control" placeholder="Masukkan Nama Strain">
                <x-input.error for="name" />
            </x-input.group>

            <x-input.group col="6" label="Satuan Default">
                <select wire:model.live="unit_id" class="form-select">
                    <option value="">-- Pilih --</option>
                    @foreach ($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                <x-input.error for="unit_id" />
            </x-input.group>

            <x-input.group col="12" label="Deskripsi">
                <textarea wire:model="description" class="form-control" rows="3"
                    placeholder="Masukkan Deskripsi"></textarea>
                <x-input.error for="description" />
            </x-input.group>

            <x-input.group col="6" label="Status">
                <select wire:model="status" class="form-select">
                    <option value="">-- Pilih Status --</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <x-input.error for="status" />
            </x-input.group>
        </div>

        {{-- === Tab Section: Satuan Konversi === --}}
        <ul class="nav nav-tabs my-4" id="strainTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="conversion-tab" data-bs-toggle="tab" data-bs-target="#conversion"
                    type="button" role="tab">Satuan Konversi</button>
            </li>
        </ul>

        <div class="tab-content" id="strainTabContent">
            {{-- Tab Satuan Konversi --}}
            <div class="tab-pane fade show active" id="conversion" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Satuan</th>
                                <th>Nilai Konversi</th>
                                <th>Default Beli</th>
                                <th>Default Mutasi</th>
                                <th>Default Jual</th>
                                <th>Terkecil</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($conversion_units as $index => $conversion)
                            <tr>
                                <td>
                                    <select wire:model.defer="conversion_units.{{ $index }}.unit_id"
                                        class="form-select">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" @if($conversion['unit_id']==$unit->id) selected
                                            @endif>{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01"
                                        wire:model.defer="conversion_units.{{ $index }}.value" class="form-control">
                                </td>
                                <td>
                                    <input type="checkbox"
                                        wire:model="conversion_units.{{ $index }}.is_default_purchase"
                                        wire:click.prevent="toggleDefault('is_default_purchase', {{ $index }})">
                                </td>
                                <td>
                                    <input type="checkbox"
                                        wire:model="conversion_units.{{ $index }}.is_default_mutation"
                                        wire:click.prevent="toggleDefault('is_default_mutation', {{ $index }})">
                                </td>
                                <td>
                                    <input type="checkbox" wire:model="conversion_units.{{ $index }}.is_default_sale"
                                        wire:click.prevent="toggleDefault('is_default_sale', {{ $index }})">
                                </td>
                                <td>
                                    <input type="checkbox" wire:model="conversion_units.{{ $index }}.is_smallest"
                                        wire:click.prevent="toggleDefault('is_smallest', {{ $index }})">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm"
                                        wire:click="removeConversion({{ $index }})">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('conversion_units')
                <div class="alert alert-danger mt-2">{{ $message }}</div>
                @enderror

                <button type="button" class="btn btn-success mt-2" wire:click="addConversion">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Konversi
                </button>
            </div>
        </div>

        {{-- === Action Buttons === --}}
        <div class="d-flex justify-content-end my-4">
            <button type="button" class="btn btn-secondary me-2" wire:click="close">Batal</button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Simpan
            </button>
        </div>
    </form>
    @endif
</div>