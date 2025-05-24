<div>
    @if($isOpen)
    <div class="modal fade show" tabindex="-1" style="display: block;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Restore Menu Configuration</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    @if(!$selectedBackup)
                    <div class="mb-5">
                        <label class="form-label">Select Backup File</label>
                        <select class="form-select" wire:model.live="selectedBackup"
                            wire:change="loadBackupData($event.target.value)">
                            <option value="">Select a backup file...</option>
                            @foreach($backupFiles as $file)
                            <option value="{{ $file['name'] }}">{{ $file['name'] }} ({{ $file['date'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if($showDiff)
                    <div class="mb-5">
                        <h6 class="mb-3">Changes Summary for {{ $selectedBackup }}</h6>

                        @if(count($differences['added']) > 0)
                        <div class="alert alert-info">
                            <h6 class="mb-2">New Menus ({{ count($differences['added']) }})</h6>
                            @include('livewire.menu.partials.menu-diff-item', ['items' => $differences['added'], 'type'
                            => 'added', 'level' => 0])
                        </div>
                        @endif

                        @if(count($differences['modified']) > 0)
                        <div class="alert alert-warning">
                            <h6 class="mb-2">Modified Menus ({{ count($differences['modified']) }})</h6>
                            @include('livewire.menu.partials.menu-diff-item', ['items' => $differences['modified'],
                            'type' => 'modified', 'level' => 0])
                        </div>
                        @endif

                        @if(count($differences['deleted']) > 0)
                        <div class="alert alert-danger">
                            <h6 class="mb-2">Deleted Menus ({{ count($differences['deleted']) }})</h6>
                            @include('livewire.menu.partials.menu-diff-item', ['items' => $differences['deleted'],
                            'type' => 'deleted', 'level' => 0])
                        </div>
                        @endif

                        @if(empty($differences['added']) && empty($differences['modified']) &&
                        empty($differences['deleted']))
                        <div class="alert alert-success">
                            No changes detected. The backup is identical to the current configuration.
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Close</button>
                    @if($showDiff)
                    <button type="button" class="btn btn-secondary" wire:click="resetSelection">Back to Files</button>
                    <button type="button" class="btn btn-primary" wire:click="restore">
                        Restore Configuration
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>