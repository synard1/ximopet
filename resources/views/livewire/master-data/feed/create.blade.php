<div>
    @if ($showForm)
        <form wire:submit.prevent="save">
            {{-- === Informasi Utama === --}}
            <div class="row g-3">
                <x-input.group col="6" label="Kode">
                    <input type="text" wire:model="code" class="form-control">
                    <x-input.error for="code" />
                </x-input.group>

                <x-input.group col="6" label="Nama">
                    <input type="text" wire:model="name" class="form-control">
                    <x-input.error for="name" />
                </x-input.group>

                <x-input.group col="6" label="Jenis">
                    <select wire:model="type" class="form-select">
                        <option >-- Pilih --</option>
                        <option value="Feed">Feed</option>
                        <option value="Supplement">Supplement</option>
                        <option value="Medicine">Medicine</option>
                        <option value="Others">Others</option>
                    </select>
                    <x-input.error for="type" />
                </x-input.group>

                {{-- <x-input.group col="6" label="Satuan Default">
                    <x-select
                        wire:model.live="unit_id"
                        placeholder="Pilih Satuan"
                        :options="['' => '-- Pilih --'] + $units->pluck('name', 'id')->toArray()"
                        searchable
                        label=""
                        class="form-select"
                    />
                    <x-input.error for="unit_id" />
                </x-input.group> --}}
                <x-input.group col="6" label="Satuan Default">
                    <select wire:model.live="unit_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <x-input.error for="unit_id" />
                </x-input.group>
            </div>

            {{-- === Tab Section: Satuan Konversi, Zat Aktif, Lokasi, Data Lain === --}}
            <ul class="nav nav-tabs my-4" id="feedTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="conversion-tab" data-bs-toggle="tab" data-bs-target="#conversion" type="button" role="tab">Satuan Konversi</button>
                </li>
                {{-- <li class="nav-item" role="presentation">
                    <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab">Data Lain</button>
                </li> --}}
            </ul>

            <div class="tab-content" id="feedTabContent">
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
                                            <select wire:model.defer="conversion_units.{{ $index }}.unit_id" class="form-select">
                                                <option value="">-- Pilih --</option>
                                                @foreach ($units as $unit)
                                                    <option value="{{ $unit->id }}" @if($conversion['unit_id'] == $unit->id) selected @endif>{{ $unit->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" wire:model.defer="conversion_units.{{ $index }}.value" class="form-control">
                                        </td>
                                        <td>
                                            {{-- <input type="checkbox" wire:click="toggleDefault('is_default_purchase', {{ $index }})" {{ $conversion['is_default_purchase'] ? 'checked' : '' }}> --}}
                                            <input type="checkbox"
                                                wire:model="conversion_units.{{ $index }}.is_default_purchase"
                                                wire:click.prevent="toggleDefault('is_default_purchase', {{ $index }})">


                                        </td>
                                        <td>
                                            {{-- <input type="checkbox" wire:click="toggleDefault('is_default_mutation', {{ $index }})" {{ $conversion['is_default_mutation'] ? 'checked' : '' }}> --}}
                                            <input type="checkbox"
                                                wire:model="conversion_units.{{ $index }}.is_default_mutation"
                                                wire:click.prevent="toggleDefault('is_default_mutation', {{ $index }})">
                                        </td>
                                        <td>
                                            {{-- <input type="checkbox" wire:click="toggleDefault('is_default_sale', {{ $index }})" {{ $conversion['is_default_sale'] ? 'checked' : '' }}> --}}
                                            <input type="checkbox"
                                                wire:model="conversion_units.{{ $index }}.is_default_sale"
                                                wire:click.prevent="toggleDefault('is_default_sale', {{ $index }})">
                                        </td>
                                        <td>
                                            {{-- <input type="checkbox" wire:click="toggleDefault('is_smallest', {{ $index }})" {{ $conversion['is_smallest'] ? 'checked' : '' }}> --}}
                                            <input type="checkbox"
                                                wire:model="conversion_units.{{ $index }}.is_smallest"
                                                wire:click.prevent="toggleDefault('is_smallest', {{ $index }})">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" wire:click="removeConversion({{ $index }})">
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

                {{-- Tab Data Lain --}}
                {{-- <div class="tab-pane fade" id="other" role="tabpanel">
                    <div class="row g-3">
                        <x-input.group col="6" label="Volume">
                            <input type="text" wire:model="volume" class="form-control">
                        </x-input.group>

                        <x-input.group col="6" label="Keterangan">
                            <textarea wire:model="description" class="form-control" rows="3"></textarea>
                        </x-input.group>
                    </div>
                </div> --}}
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
