<div class="modal" tabindex="-1" role="dialog" id="kt_modal_master_ekspedisi" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Data Ekspedisi</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="kode">Kode</label>
                        <input type="text" class="form-control" wire:model="kode" id="kode">
                        @error('kode') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" wire:model="nama" id="nama">
                        @error('nama') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <input type="text" class="form-control" wire:model="alamat" id="alamat">
                        @error('alamat') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" wire:model="email" id="email">
                        @error('email') <span class="text-danger error">{{ $message}}</span>@enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="store()">Save changes</button>
            </div>
        </div>
    </div>
</div>
