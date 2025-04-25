<div>
    <div class="mb-3">
        <label class="form-label">Tanggal Rollback</label>
        <input type="date" class="form-control" wire:model="date">
    </div>

    <div class="mb-3">
        <label class="form-label">Ternak</label>
        <select class="form-select" wire:model="ternak_id" wire:change="loadUsages">
            <option value="">-- Pilih Ternak --</option>
            @foreach($ternaks as $ternak)
                <option value="{{ $ternak->id }}">{{ $ternak->name }}</option>
            @endforeach
        </select>
    </div>

    @if ($usages)
        <div class="mb-3 mt-4">
            <h5>Detail Penggunaan yang Akan Di-Rollback</h5>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Pakan</th>
                        <th>Jumlah Digunakan</th>
                        <th>Jumlah Rollback</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usages as $index => $usage)
                        <tr>
                            <td>
                                {{ \App\Models\Item::find($usage['feed_id'])->name ?? 'Pakan Tidak Ditemukan' }}
                            </td>
                            <td>{{ $usage['quantity_taken'] }} kg</td>
                            <td>
                                <input type="number" min="0" max="{{ $usage['quantity_taken'] }}" step="0.01"
                                       wire:model="usages.{{ $index }}.quantity_to_rollback"
                                       class="form-control" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label">Alasan Rollback</label>
        <textarea class="form-control" wire:model="reason" rows="2" placeholder="Opsional"></textarea>
    </div>

    <div class="d-flex justify-content-between">
        <button wire:click="save" class="btn btn-success">💾 Simpan Rollback</button>
    </div>

    @if ($errors->has('rollback_error'))
        <div class="alert alert-danger mt-3">{{ $errors->first('rollback_error') }}</div>
    @endif

    @if (session()->has('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif
</div>
