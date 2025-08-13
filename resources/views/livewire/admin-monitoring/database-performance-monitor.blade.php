<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title">
                <i class="fas fa-database text-primary me-2"></i>
                Database Performance Monitor
            </h3>
            <div class="d-flex gap-2">
                <button wire:click="refreshData" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
                <button wire:click="exportData" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-download me-1"></i> Export
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-1">{{ number_format($summaryStats->total_operations ?? 0) }}</h4>
                        <small>Total Operations</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-1">{{ number_format($summaryStats->unique_models ?? 0) }}</h4>
                        <small>Unique Models</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-1">{{ number_format($summaryStats->total_slow_operations ?? 0) }}</h4>
                        <small>Slow Operations</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4 class="mb-1">{{ number_format($summaryStats->total_errors ?? 0) }}</h4>
                        <small>Errors</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Model</label>
                        <select wire:model.live="selectedModel" class="form-select">
                            <option value="">All Models</option>
                            @foreach($models as $model)
                            <option value="{{ $model['value'] }}">{{ $model['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Operation</label>
                        <select wire:model.live="selectedOperation" class="form-select">
                            <option value="">All Operations</option>
                            @foreach($operationTypes as $operation)
                            <option value="{{ $operation }}">{{ ucfirst($operation) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select wire:model.live="selectedStatus" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
                        <select wire:model.live="dateRange" class="form-select">
                            <option value="1">Last 24 Hours</option>
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button wire:click="resetFilters" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="btn-group" role="group">
                    <button wire:click="getBottleneckAnalysis" class="btn btn-outline-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i> Bottleneck Analysis
                    </button>
                    <button wire:click="getPerformanceTrends" class="btn btn-outline-info">
                        <i class="fas fa-chart-line me-1"></i> Performance Trends
                    </button>
                    <button wire:click="hideAnalysis" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Hide Analysis
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottleneck Analysis -->
        @if($showBottleneckAnalysis)
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Bottleneck Analysis
                </h5>
            </div>
            <div class="card-body">
                @php
                $bottlenecks = $this->getBottleneckAnalysisData();
                @endphp
                @if($bottlenecks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Model</th>
                                <th>Operation</th>
                                <th>Count</th>
                                <th>Avg Time (ms)</th>
                                <th>Max Time (ms)</th>
                                <th>Avg Queries</th>
                                <th>Slow Count</th>
                                <th>Error Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bottlenecks as $bottleneck)
                            <tr>
                                <td>{{ class_basename($bottleneck->model_class) }}</td>
                                <td>{{ ucfirst($bottleneck->operation_type) }}</td>
                                <td>{{ $bottleneck->operation_count }}</td>
                                <td class="text-warning">{{ number_format($bottleneck->avg_execution_time, 2) }}</td>
                                <td class="text-danger">{{ number_format($bottleneck->max_execution_time, 2) }}</td>
                                <td>{{ number_format($bottleneck->avg_query_count, 1) }}</td>
                                <td class="text-warning">{{ $bottleneck->slow_count }}</td>
                                <td class="text-danger">{{ $bottleneck->error_count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    No significant bottlenecks detected in the selected time range.
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Performance Trends -->
        @if($showTrends)
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Performance Trends
                </h5>
            </div>
            <div class="card-body">
                @php
                $trends = $this->getPerformanceTrendsData();
                @endphp
                @if($trends->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Model</th>
                                <th>Operations</th>
                                <th>Avg Time (ms)</th>
                                <th>Avg Memory (MB)</th>
                                <th>Success</th>
                                <th>Errors</th>
                                <th>Slow</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trends as $trend)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($trend->date)->format('M d, Y') }}</td>
                                <td>{{ class_basename($trend->model_class) }}</td>
                                <td>{{ $trend->operations }}</td>
                                <td>{{ number_format($trend->avg_execution_time, 2) }}</td>
                                <td>{{ number_format($trend->avg_memory_usage, 2) }}</td>
                                <td class="text-success">{{ $trend->success_count }}</td>
                                <td class="text-danger">{{ $trend->error_count }}</td>
                                <td class="text-warning">{{ $trend->slow_count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No trend data available for the selected time range.
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Performance Logs Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Performance Logs</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Model</th>
                                <th>Operation</th>
                                <th>Status</th>
                                <th>Execution Time</th>
                                <th>Memory</th>
                                <th>Queries</th>
                                <th>User</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('M d, H:i:s') }}</td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ class_basename($log->model_class) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ ucfirst($log->operation_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->status === 'success')
                                    <span class="badge bg-success">Success</span>
                                    @elseif($log->status === 'slow')
                                    <span class="badge bg-warning">Slow</span>
                                    @else
                                    <span class="badge bg-danger">Error</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->execution_time_ms > 1000)
                                    <span class="text-danger fw-bold">
                                        {{ number_format($log->execution_time_ms, 2) }}ms
                                    </span>
                                    @elseif($log->execution_time_ms > 500)
                                    <span class="text-warning fw-bold">
                                        {{ number_format($log->execution_time_ms, 2) }}ms
                                    </span>
                                    @else
                                    <span class="text-success">
                                        {{ number_format($log->execution_time_ms, 2) }}ms
                                    </span>
                                    @endif
                                </td>
                                <td>{{ number_format($log->memory_usage_mb, 2) }} MB</td>
                                <td>
                                    {{ $log->query_count }}
                                    @if($log->slow_query_count > 0)
                                    <span class="badge bg-warning ms-1">{{ $log->slow_query_count }} slow</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->user)
                                    {{ $log->user->name }}
                                    @else
                                    <span class="text-muted">System</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info"
                                        onclick="showLogDetails('{{ $log->id }}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <br>No performance logs found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Performance Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function showLogDetails(logId) {
    // Load log details via AJAX and show in modal
    fetch(`/admin/performance-logs/${logId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('logDetailsContent').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('logDetailsModal')).show();
        });
}

// Auto-refresh functionality
document.addEventListener('livewire:init', () => {
    Livewire.on('start-auto-refresh', (data) => {
        setInterval(() => {
            @this.refreshData();
        }, data.interval);
    });
});
</script>
@endpush