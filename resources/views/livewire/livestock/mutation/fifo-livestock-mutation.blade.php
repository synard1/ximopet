<div id="fifoMutationContainer">
    @if($showModal)

    @if($isEditing)
    @include('livewire.livestock.mutation.partials.fifo-edit-mode')
    @else
    @include('livewire.livestock.mutation.partials.fifo-create-mode')
    @endif

    <!-- FIFO Preview Modal -->
    @if($showPreviewModal && $fifoPreview)
    @include('livewire.livestock.mutation.partials.fifo-preview-modal')
    @endif

    <!-- Loading Overlay -->
    @if($processingMutation)
    @include('livewire.livestock.mutation.partials.fifo-loading-overlay')
    @endif

    <!-- JavaScript Events -->
    @include('livewire.livestock.mutation.partials.fifo-javascript')

    @endif
</div>