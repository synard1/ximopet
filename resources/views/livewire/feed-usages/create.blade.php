<div>
    <div class="mb-3">
        <label for="ternak_id" class="form-label">Ternak</label>
        <select class="form-select" wire:model.live="ternak_id">
            <option value="">-- Pilih Ternak --</option>
            @foreach($ternaks as $ternak)
                <option value="{{ $ternak->id }}">{{ $ternak->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="date" class="form-label">Tanggal</label>
        <input type="date" class="form-control" wire:model.live="date">
    </div>

    <h5>Detail Pakan</h5>
    @forelse($usages as $index => $usage)
        <div class="row mb-2">
            <div class="col-md-6">
                <select class="form-select" wire:model="usages.{{ $index }}.feed_id">
                    <option value="">-- Jenis Pakan --</option>
                    @foreach($feeds as $feed)
                        @php
                            $feedId = $feed['feed_id'] ?? $feed->id;
                            $feedName = $feed['feed']['name'] ?? $feed['name'] ?? '-';
                            $availableQty = $feed['available_quantity'] ?? $feed['available'] ?? 0;

                            // Hindari duplikasi feed di baris lain
                            $alreadySelected = collect($usages)
                                ->pluck('feed_id')
                                ->filter(fn($val, $i) => $i !== $index)
                                ->contains($feedId);
                        @endphp

                        @if (!$alreadySelected || $usage['feed_id'] == $feedId)
                            <option value="{{ $feedId }}">
                                {{ $feedName }} (Tersisa: {{ number_format($availableQty, 2) }} Kg)
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" step="0.01" class="form-control" wire:model="usages.{{ $index }}.quantity" placeholder="Jumlah (Kg)">
            </div>
            <div class="col-md-2">
                <button type="button" wire:click="removeUsageRow({{ $index }})" class="btn btn-danger btn-sm">Hapus</button>
            </div>
        </div>
    @empty
        <div class="alert alert-info">Belum ada penggunaan pakan.</div>
    @endforelse

    {{-- @forelse($usages as $index => $usage)
        <div class="row mb-2">
            <div class="col-md-6">
                <select class="form-select" wire:model="usages.{{ $index }}.feed_id">
                    <option value="">-- Jenis Pakan --</option>
                    @foreach($feeds as $feed)
                        <option value="{{ $feed['feed_id'] ?? $feed->id }}">
                            {{ $feed->feed->name ?? $feed->name }} (Tersisa: {{ number_format($feed['available_quantity'] ?? $feed['available'], 2) }} Kg)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" step="0.01" class="form-control" wire:model="usages.{{ $index }}.quantity" placeholder="Jumlah (Kg)">
            </div>
            <div class="col-md-2">
                <button type="button" wire:click="removeUsageRow({{ $index }})" class="btn btn-danger btn-sm">Hapus</button>
            </div>
        </div>
    @empty
        <div class="alert alert-info">Belum ada penggunaan pakan.</div>
    @endforelse --}}

    <button type="button" wire:click="addUsageRow" class="btn btn-outline-primary mb-3">+ Tambah Baris</button>

    <div>
        <button type="button" wire:click="save" class="btn btn-success">💾 Simpan Penggunaan</button>
    </div>

    @error('save_error') <div class="alert alert-danger mt-2">{{ $message }}</div> @enderror
</div>
