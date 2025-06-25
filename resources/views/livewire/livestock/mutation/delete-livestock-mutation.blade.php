<div>
    @if($showDeleteMutation)
    <h3 class="mb-3">Detail Mutasi Ternak</h3>
    @if($mutation)
    <div class="card mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Tanggal</dt>
                <dd class="col-sm-9">{{ $mutation->tanggal ? $mutation->tanggal->format('d-m-Y') : '-' }}</dd>

                <dt class="col-sm-3">Jenis Mutasi</dt>
                <dd class="col-sm-9">{{ $mutation->jenis ?? '-' }}</dd>

                <dt class="col-sm-3">Jumlah</dt>
                <dd class="col-sm-9">{{ number_format($mutation->jumlah ?? 0) }}</dd>

                <dt class="col-sm-3">Source Livestock</dt>
                <dd class="col-sm-9">{{ $mutation->sourceLivestock->name ?? '-' }}</dd>

                <dt class="col-sm-3">Destination Livestock</dt>
                <dd class="col-sm-9">{{ $mutation->destinationLivestock->name ?? '-' }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">{{ $mutation->status ?? '-' }}</dd>

                <dt class="col-sm-3">Metadata</dt>
                <dd class="col-sm-9">
                    <pre
                        class="mb-0">{{ json_encode($mutation->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </dd>
            </dl>
        </div>
    </div>

    <h5>Detail Item Mutasi</h5>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Batch</th>
                    <th>Quantity</th>
                    <th>Weight</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $item->batch->name ?? '-' }}</td>
                    <td>{{ number_format($item->quantity ?? 0) }}</td>
                    <td>{{ number_format($item->weight ?? 0, 2) }}</td>
                    <td>{{ number_format($item->price ?? 0, 2) }}</td>
                    <td>{{ $item->status ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada item mutasi</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($deleteSuccess)
    <div class="alert alert-success">Mutasi berhasil dihapus.</div>
    @elseif($deleteError)
    <div class="alert alert-danger">Gagal menghapus mutasi: {{ $deleteError }}</div>
    @endif

    @if(!$deleteSuccess)
    @if($confirmingDelete)
    <div class="alert alert-warning">
        <strong>Konfirmasi:</strong> Apakah Anda yakin ingin menghapus mutasi ini? Tindakan ini tidak dapat
        dibatalkan.<br>
        <button wire:click="deleteMutation" class="btn btn-danger btn-sm mt-2">Ya, Hapus</button>
        <button wire:click="cancelDelete" class="btn btn-secondary btn-sm mt-2">Batal</button>
    </div>
    @else
    <button wire:click="confirmDelete" class="btn btn-danger">Hapus Mutasi</button>
    @endif
    @endif
    @else
    <div class="alert alert-danger">Data mutasi tidak ditemukan.</div>
    @endif
    @endif
</div>