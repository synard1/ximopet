<div class="modal" tabindex="-1" role="dialog" id="kt_modal_1" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">test</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="closeModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" wire:model="nama" id="nama">
                        @error('nama') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeModalSupplier()">Close</button>
                <button type="button" class="btn btn-primary" wire:click="storeSupplier()">Save changes</button>
            </div>
        </div>
    </div>
</div>
