<div class="mb-2">
    <strong>Periode / Batch:</strong> {{ $reportData['livestock']->name ?? '-' }}
</div>
<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th class="text-center" rowspan="2">No</th>
                <th class="text-center" rowspan="2">Nama Pekerja</th>
                <th class="text-center" rowspan="2">Kandang</th>
                <th class="text-center" colspan="2">Periode Penugasan</th>
                <th class="text-center" rowspan="2">Peran</th>
                <th class="text-center" rowspan="2">Status</th>
                <th class="text-center" rowspan="2">Catatan</th>
            </tr>
            <tr>
                <th class="text-center">Mulai</th>
                <th class="text-center">Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData['batchWorkers'] as $index => $data)
            <tr>
                <td class="text-center">{{ (string)($index + 1) }}</td>
                <td>{{ $data->worker ? $data->worker->name : '-' }}</td>
                <td>{{ $data->livestock && $data->livestock->kandang ? $data->livestock->kandang->name : '-' }}</td>
                <td class="text-center">{{ $data->start_date ? \Carbon\Carbon::parse($data->start_date)->format('d/m/Y')
                    : '-' }}</td>
                <td class="text-center">{{ $data->end_date ? \Carbon\Carbon::parse($data->end_date)->format('d/m/Y') :
                    '-' }}</td>
                <td>{{ $data->role }}</td>
                <td class="text-center">
                    <span class="badge bg-{{ $data->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($data->status) }}
                    </span>
                </td>
                <td>{{ $data->notes ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Tidak ada data penugasan</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>