<div class="mb-2">
    <strong>Periode / Batch:</strong> {{ $reportData['livestock']->name ?? '-' }}
</div>
<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">Nama Pekerja</th>
                <th class="text-center">Kandang</th>
                <th class="text-center">Peran</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData['batchWorkers'] as $index => $data)
            <tr>
                <td class="text-center">{{ (string)($index + 1) }}</td>
                <td>{{ $data->worker ? $data->worker->name : '-' }}</td>
                <td>{{ $data->livestock && $data->livestock->kandang ? $data->livestock->kandang->name : '-' }}</td>
                <td>{{ $data->role }}</td>
                <td class="text-center">
                    <span class="badge bg-{{ $data->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($data->status) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada data penugasan</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>