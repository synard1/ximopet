<!-- FIFO Preview Modal -->
<div class="modal fade show" style="display: block;" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye text-primary me-2"></i>
                    @if($isEditing)
                    Preview Edit Mutasi FIFO
                    @else
                    Preview Mutasi FIFO
                    @endif
                </h5>
                <button type="button" class="btn-close" wire:click="closePreviewModal"></button>
            </div>

            <div class="modal-body">
                @if($isEditing)
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-edit me-2"></i>
                    <strong>Mode Edit Aktif</strong> - Data mutasi yang ada akan diperbarui
                    @if(!empty($existingMutationIds))
                    <br><small class="text-muted">ID Mutasi: {{ implode(', ', $existingMutationIds) }}</small>
                    @endif
                </div>
                @endif

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light-primary">
                            <div class="card-body text-center p-3">
                                <div class="fw-bold text-primary">{{ $fifoPreview['requested_quantity'] }}</div>
                                <small class="text-muted">Diminta</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-success">
                            <div class="card-body text-center p-3">
                                <div class="fw-bold text-success">{{ $fifoPreview['total_quantity'] }}</div>
                                <small class="text-muted">Dipenuhi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-warning">
                            <div class="card-body text-center p-3">
                                <div class="fw-bold text-warning">{{ $fifoPreview['batches_count'] }}</div>
                                <small class="text-muted">Batch</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card {{ $fifoPreview['can_fulfill'] ? 'bg-light-success' : 'bg-light-danger' }}">
                            <div class="card-body text-center p-3">
                                <div class="fw-bold {{ $fifoPreview['can_fulfill'] ? 'text-success' : 'text-danger' }}">
                                    {{ $fifoPreview['can_fulfill'] ? '✓ Siap' : '✗ Kurang' }}
                                </div>
                                <small class="text-muted">Status</small>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!$fifoPreview['can_fulfill'])
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Kuantitas tidak dapat dipenuhi!</strong><br>
                    Kekurangan: {{ number_format($fifoPreview['shortfall']) }} ekor
                </div>
                @endif

                <!-- Batch Details -->
                <h6 class="mb-3">Detail Batch FIFO</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Batch</th>
                                <th>Umur</th>
                                <th>Tersedia</th>
                                <th>Dimutasi</th>
                                <th>Sisa</th>
                                <th>Utilisasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fifoPreview['batches_preview'] as $index => $batch)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-bold">{{ $batch['batch_name'] }}</div>
                                    <small class="text-muted">{{ $batch['start_date'] }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $batch['age_days'] }}</span>
                                </td>
                                <td class="text-end">{{ number_format($batch['available_quantity']) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($batch['quantity_to_mutate'])
                                    }}</td>
                                <td class="text-end">{{ number_format($batch['remaining_after_mutation']) }}</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 15px;">
                                        <div class="progress-bar bg-success"
                                            style="width: {{ $batch['utilization_rate'] }}%">
                                            {{ $batch['utilization_rate'] }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>FIFO:</strong> Batch tertua dipilih terlebih dahulu ({{ $fifoPreview['fifo_order'] }})
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closePreviewModal">
                    <i class="fas fa-times me-2"></i>
                    Tutup
                </button>

                @if($fifoPreview['can_fulfill'])
                <button type="button" class="btn {{ $isEditing ? 'btn-warning' : 'btn-primary' }}"
                    wire:click="processFifoMutation" wire:loading.attr="disabled" wire:target="processFifoMutation">
                    <span wire:loading.remove wire:target="processFifoMutation">
                        @if($isEditing)
                        <i class="fas fa-save me-2"></i>
                        Update Mutasi
                        @else
                        <i class="fas fa-check me-2"></i>
                        Proses Mutasi
                        @endif
                    </span>
                    <span wire:loading wire:target="processFifoMutation">
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        @if($isEditing) Updating... @else Processing... @endif
                    </span>
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Backdrop -->
<div class="modal-backdrop fade show"></div>