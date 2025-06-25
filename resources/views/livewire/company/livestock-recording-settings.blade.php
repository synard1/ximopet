<div class="card">
    <div class="card-header">
        <h3 class="card-title">Livestock Recording Settings</h3>
        <div class="card-toolbar">
            @if(!$isEditing)
            <button type="button" class="btn btn-sm btn-light-primary" wire:click="toggleEdit">
                <i class="ki-duotone ki-pencil fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                Edit Settings
            </button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form wire:submit="save">
            <!-- Recording Method Selection -->
            <div class="mb-5">
                <label class="form-label required">Recording Method</label>
                <div class="d-flex gap-5">
                    <div class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="radio" wire:model="recordingType" value="batch" {{
                            !$isEditing ? 'disabled' : '' }} />
                        <label class="form-check-label">
                            Batch Recording
                            <span class="form-text text-muted">Track individual batches with detailed attributes</span>
                        </label>
                    </div>
                    <div class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="radio" wire:model="recordingType" value="total" {{
                            !$isEditing ? 'disabled' : '' }} />
                        <label class="form-check-label">
                            Total Recording
                            <span class="form-text text-muted">Track total counts and averages only</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Multiple Batches Option -->
            <div class="mb-5">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" wire:model="allowMultipleBatches" {{ !$isEditing
                        ? 'disabled' : '' }} />
                    <label class="form-check-label">
                        Allow Multiple Batches
                        <span class="form-text text-muted">Enable recording of multiple batches per entry</span>
                    </label>
                </div>
            </div>

            <!-- Batch Settings -->
            @if($recordingType === 'batch')
            <div class="mb-5">
                <div class="row align-items-start">
                    <!-- Batch Settings Column -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Batch Settings</h4>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="batchSettings.enabled" {{
                                !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Enable Batch Recording</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="batchSettings.auto_generate_batch" {{ !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Auto-generate Batch Numbers</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="batchSettings.require_batch_number" {{ !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Require Batch Numbers</label>
                        </div>
                    </div>
                    <!-- Track Batch Details Column -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Track Batch Details</h4>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="batchSettings.batch_details.weight" {{ !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Weight</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="batchSettings.batch_details.age"
                                {{ !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Age</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="batchSettings.batch_details.breed" {{ !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Breed</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="batchSettings.batch_details.health_status" {{ !$isEditing ? 'disabled' : ''
                                }} />
                            <label class="form-check-label">Health Status</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="batchSettings.batch_details.notes" {{ !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Notes</label>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Total Settings -->
            @if($recordingType === 'total')
            <div class="mb-5">
                <h4 class="mb-3">Total Settings</h4>
                <div class="row g-5">
                    <!-- Basic Settings -->
                    <div class="col-md-6">
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="totalSettings.enabled" {{
                                !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Enable Total Recording</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox" wire:model="totalSettings.track_total_only"
                                {{ !$isEditing ? 'disabled' : '' }} />
                            <label class="form-check-label">Track Total Only</label>
                        </div>
                    </div>

                    <!-- Total Details -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Track Total Details</h5>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="totalSettings.total_details.total_count" {{ !$isEditing ? 'disabled' : ''
                                }} />
                            <label class="form-check-label">Total Count</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="totalSettings.total_details.average_weight" {{ !$isEditing ? 'disabled' : ''
                                }} />
                            <label class="form-check-label">Average Weight</label>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox"
                                wire:model="totalSettings.total_details.total_weight" {{ !$isEditing ? 'disabled' : ''
                                }} />
                            <label class="form-check-label">Total Weight</label>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            @if($isEditing)
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light" wire:click="resetToDefaults">
                    Reset to Defaults
                </button>
                <button type="button" class="btn btn-light" wire:click="toggleEdit">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Save Changes
                </button>
            </div>
            @endif
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('success', (message) => {
            Swal.fire({
                text: message,
                icon: "success",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });
        });

        Livewire.on('error', (message) => {
            Swal.fire({
                text: message,
                icon: "error",
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                    confirmButton: "btn btn-primary"
                }
            });
        });
    });
</script>
@endpush