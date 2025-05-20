<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Batch Worker Report</h3>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" wire:model.live="startDate">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" wire:model.live="endDate">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Farm</label>
                    <select class="form-select" wire:model.live="farmId">
                        <option value="">All Farms</option>
                        @foreach($farms as $farm)
                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Worker</label>
                    <select class="form-select" wire:model.live="workerId">
                        <option value="">All Workers</option>
                        @foreach($workers as $worker)
                        <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="status">
                        <option value="">All Status</option>
                        @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Export Format</label>
                    <select class="form-select" wire:model="exportFormat">
                        <option value="excel">Excel</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-primary me-2" wire:click="export">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                    <button class="btn btn-secondary" wire:click="resetFilters">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Filters
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-5">
                @foreach($summary as $item)
                <div class="col-md-3">
                    <div class="card bg-light-primary">
                        <div class="card-body">
                            <h6 class="card-title text-primary">{{ ucfirst($item->status) }}</h6>
                            <div class="d-flex flex-column">
                                <span>Total Assignments: {{ $item->total_assignments }}</span>
                                <span>Unique Workers: {{ $item->unique_workers }}</span>
                                <span>Unique Batches: {{ $item->unique_batches }}</span>
                                <span>Unique Farms: {{ $item->unique_farms }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Data Table -->
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th>Batch</th>
                            <th>Worker</th>
                            <th>Farm</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Created By</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batchWorkers as $item)
                        <tr>
                            <td>{{ $item->batch->name }}</td>
                            <td>{{ $item->worker->name }}</td>
                            <td>{{ $item->farm->name }}</td>
                            <td>{{ $item->start_date->format('Y-m-d') }}</td>
                            <td>{{ $item->end_date ? $item->end_date->format('Y-m-d') : '-' }}</td>
                            <td>
                                <span
                                    class="badge badge-light-{{ $item->status === 'active' ? 'success' : ($item->status === 'completed' ? 'primary' : 'danger') }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>{{ $item->notes ?: '-' }}</td>
                            <td>{{ $item->creator->name }}</td>
                            <td>{{ $item->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">No data found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-end mt-5">
                {{ $batchWorkers->links() }}
            </div>
        </div>
    </div>
</div>