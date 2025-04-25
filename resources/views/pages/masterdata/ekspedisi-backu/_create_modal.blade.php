<div class="modal" role="dialog" id="kt_modal_tambah_company" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Data Perusahaan</h5>
                <!--begin::Close-->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <button type="button" class="btn btn-primary" wire:click="store()">Save changes</button>
            </div>
        </div>
    </div>
</div>
