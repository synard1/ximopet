<div class="text-center mb-5">
    <i class="fas fa-check-circle fs-5x text-success mb-4"></i>
    <h3 class="text-success">
        {{ $isEditing ? 'Depletion Updated Successfully!' : 'Depletion Processed Successfully!' }}
    </h3>
    <div class="text-muted">{{ $successMessage }}</div>
</div>

<div class="d-flex justify-content-center">
    <button type="button" class="btn btn-primary" wire:click="resetForm">
        <i class="fas fa-plus me-2"></i> Process Another Depletion
    </button>
</div>