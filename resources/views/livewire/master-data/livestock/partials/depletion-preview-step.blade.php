<!-- Preview Summary -->
<div class="card bg-light-info mb-5">
    <div class="card-body">
        <h5 class="card-title">Depletion Summary</h5>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3"><strong>Total Quantity:</strong> {{ number_format($previewData['total_quantity']) }}
                </div>
                <div class="mb-3"><strong>Depletion Type:</strong> {{ ucfirst($depletionType) }}</div>
                <div class="mb-3"><strong>Date:</strong> {{ $depletionDate }}</div>
            </div>
            <div class="col-md-6">
                <div class="mb-3"><strong>Batches Affected:</strong> {{ count($previewData['batches_preview'] ?? []) }}
                </div>
                <div class="mb-3">
                    <strong>Can Process:</strong>
                    @if($canProcess)
                    <span class="badge badge-success">Yes</span>
                    @else
                    <span class="badge badge-danger">No</span>
                    @endif
                </div>
                @if($isEditing)
                <div class="mb-3"><strong>Mode:</strong> <span class="badge badge-warning">Update Mode</span></div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Batch Details -->
<div class="mb-5">
    <h5 class="mb-4">Batch Details</h5>
    @foreach($previewData['batches_preview'] ?? [] as $batch)
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <strong>{{ $batch['batch_name'] }}</strong><br>
                    <small class="text-muted">Age: {{ $batch['batch_age_days'] ?? 0 }} days</small>
                </div>
                <div class="col-md-2">
                    <strong>Quantity:</strong><br> {{ number_format($batch['requested_quantity']) }}
                </div>
                <div class="col-md-3">
                    <strong>Available:</strong><br> {{ number_format($batch['available_quantity']) }}
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong><br>
                    @if($batch['can_fulfill'])
                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Can fulfill</span>
                    @else
                    <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Cannot fulfill</span>
                    @if(isset($batch['shortfall']) && $batch['shortfall'] > 0)
                    <br><small class="text-danger">Shortfall: {{ number_format($batch['shortfall']) }}</small>
                    @endif
                    @endif
                </div>
            </div>
            @if($batch['note'])
            <div class="mt-2">
                <strong>Note:</strong> {{ $batch['note'] }}
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>