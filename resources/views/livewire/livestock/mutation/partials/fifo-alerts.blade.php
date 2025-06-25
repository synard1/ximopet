<!-- Error Messages -->
@if($errorMessage)
<div class="alert alert-danger d-flex align-items-center mb-3">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <span>{{ $errorMessage }}</span>
</div>
@endif

<!-- Success Messages -->
@if($successMessage)
<div class="alert alert-success d-flex align-items-center mb-3">
    <i class="fas fa-check-circle me-2"></i>
    <span>{{ $successMessage }}</span>
</div>
@endif

<!-- Restriction Messages -->
@if($restrictionMessage || (is_array($restrictionDetails ?? []) && count($restrictionDetails ?? [])))
<div class="alert alert-danger mb-3">
    <div class="d-flex align-items-start">
        <i class="fas fa-ban me-2 mt-1"></i>
        <div class="flex-grow-1">
            <h6 class="mb-1">Pembatasan Mutasi</h6>
            @if(is_array($restrictionTypes ?? []))
            @foreach($restrictionTypes as $type)
            <span class="badge bg-danger me-1">{{ $type }}</span>
            @endforeach
            @endif

            @if(is_array($restrictionDetails ?? []) && count($restrictionDetails))
            <ul class="mb-2 mt-2">
                @foreach($restrictionDetails as $detail)
                <li>{!! $detail !!}</li>
                @endforeach
            </ul>
            @elseif($restrictionMessage)
            <p class="mb-2">{{ $restrictionMessage }}</p>
            @endif

            @if(is_array($restrictionAction ?? []) && $restrictionAction)
            <button type="button" class="btn btn-sm btn-danger" wire:click="loadExistingMutationForEdit">
                <i class="fas fa-edit me-1"></i> {{ $restrictionAction['label'] ?? 'Edit Mutasi' }}
            </button>
            @endif
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="$set('restrictionMessage', '')">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
@endif

<!-- Loading Feedback -->
<div wire:loading
    wire:target="updatedSourceLivestockId,updatedMutationDate,checkMutations,doCheck,processMutationCheck,triggerMutationCheck,checkExistingMutations"
    class="alert alert-info mb-3">
    <i class="fas fa-search fa-spin me-2"></i>
    Memeriksa mutasi yang ada...
</div>