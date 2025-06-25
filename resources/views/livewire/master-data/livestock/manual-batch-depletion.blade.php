<div>
    <!-- Manual Batch Depletion Modal -->
    <div wire:ignore.self class="modal fade" id="manualBatchDepletionModal" tabindex="-1" aria-hidden="true"
        data-bs-keyboard="false" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bolder">
                        @if($isEditing)
                        <i class="fas fa-edit text-warning me-2"></i> Edit Manual Depletion
                        @else
                        <i class="fas fa-plus-circle text-primary me-2"></i> Manual Batch Depletion
                        @endif
                    </h2>
                    <button type="button" wire:click="closeModal" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Edit Mode Alert -->
                    @if($isEditing)
                    <div class="alert alert-warning d-flex align-items-center p-5 mb-6">
                        <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="d-flex flex-column flex-grow-1">
                            <h4 class="mb-1 text-warning">Edit Mode Active</h4>
                            <span>You are editing existing manual depletion data for {{ $depletionDate }}. All changes
                                will update the existing records.</span>
                    </div>
                        <button type="button" class="btn btn-sm btn-light-warning" wire:click="cancelEditMode">
                            <i class="ki-duotone ki-cross fs-6 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            Cancel Edit
                        </button>
                    </div>
                    @endif

                    @if ($errorMessage)
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ $errorMessage }}
                    </div>
                    @endif
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    @if($successMessage)
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> {{ $successMessage }}
                    </div>
                    @endif
                                @if($isLoading)
                    <div class="d-flex justify-content-center align-items-center py-10">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    @else
                    @if($step === 1)
                    @include('livewire.master-data.livestock.partials.depletion-selection-step')
                    @elseif($step === 2)
                    @include('livewire.master-data.livestock.partials.depletion-preview-step')
                    @elseif($step === 3)
                    @include('livewire.master-data.livestock.partials.depletion-result-step')
                    @endif
                    @endif
                    </div>

                <div class="modal-footer">
                    @if($step === 1)
                    <button type="button" class="btn btn-light" wire:click="closeModal"
                        data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="previewDepletion"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="fas fa-eye me-2"></i>
                            {{ $isEditing ? 'Preview Update' : 'Preview Depletion' }}
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Loading...
                        </span>
                    </button>
                    @elseif($step === 2)
                    <button type="button" class="btn btn-light" wire:click="backToSelection">
                        <i class="fas fa-arrow-left me-2"></i> Back
                    </button>
                    <button type="button" class="btn btn-{{ $canProcess ? 'success' : 'secondary' }}"
                        wire:click="processDepletion" @if(!$canProcess) disabled @endif wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="fas fa-check-circle me-2"></i>
                            {{ $isEditing ? 'Update Data' : 'Process Depletion' }}
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Processing...
                        </span>
                        </button>
                    @elseif($step === 3)
                    <button type="button" class="btn btn-primary" wire:click="closeModal" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i> Close
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('manualBatchDepletionModal');
            const modalInstance = new bootstrap.Modal(modal);

            // Handle Livewire events to show modal
            Livewire.on('show-manual-depletion', (data) => {
                console.log('Manual batch depletion event received:', data);
                modalInstance.show();
            });

            // Handle edit mode enabled event
            Livewire.on('depletion-edit-mode-enabled', (data) => {
                console.log('🔄 Edit mode enabled:', data);
                
                // Show SweetAlert notification
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Edit Mode Enabled',
                        text: `Existing depletion data loaded for ${data.date}. You can now edit the existing records.`,
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.info(`Edit mode enabled for ${data.date}. Existing data loaded.`);
                }
            });

            // Handle edit mode cancelled event
            Livewire.on('depletion-edit-mode-cancelled', () => {
                console.log('🚫 Edit mode cancelled');
                
                // Show SweetAlert notification
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Edit Mode Cancelled',
                        text: 'Switched back to create new depletion mode.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.success('Edit mode cancelled. Ready to create new depletion.');
                }
            });

            // Handle depletion processed event
            Livewire.on('depletion-processed', (data) => {
                console.log('Depletion processed:', data);
                
                // Show success notification
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Manual depletion processed successfully!',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });
                } else if (typeof toastr !== 'undefined') {
                    toastr.success('Manual depletion processed successfully!');
                }
                
                // Dispatch custom event for parent components
                window.dispatchEvent(new CustomEvent('depletion-processed', {
                    detail: data
                }));
            });

            // Handle modal close event
            modal.addEventListener('hidden.bs.modal', function() {
                @this.call('closeModalSilent');
            });

            // Handle close modal event from Livewire
            Livewire.on('close-manual-depletion-modal', () => {
                console.log('🔥 close-manual-depletion-modal event received');
                var modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    // Fallback: hide modal using jQuery if bootstrap instance not found
                    $('#manualBatchDepletionModal').modal('hide');
                }
            });

            // Watch for showModal property changes
            Livewire.hook('message.processed', (message, component) => {
                if (component.fingerprint.name === 'master-data.livestock.manual-batch-depletion') {
                    if (@this.showModal) {
                        modalInstance.show();
                    } else {
                        modalInstance.hide();
                    }
                }
            });
        });

        // Global function to show manual depletion modal with debugging
        function showManualDepletionModal(livestockId) {
            console.log('🔥 showManualDepletionModal called', { livestockId });
            
            // Try multiple methods
            try {
                Livewire.dispatch('show-manual-depletion', {
                    livestock_id: livestockId
                });
            } catch (error) {
                console.error('Error with show-manual-depletion:', error);
            }
        }
    </script>
    @endpush
</div>