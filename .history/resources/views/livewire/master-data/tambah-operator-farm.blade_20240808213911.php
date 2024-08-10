<div class="modal fade" tabindex="-1" role="dialog" id="kt_modal_tambah_operator_farm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data</h5>
            </div>
            <div class="modal-body">
                <form>
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Farm</label>
                        <!--end::Label-->
                        <!--begin::Select2-->
                        <select id="farms" name="farms" wire:model="farms" class="js-select2 form-control">
                            <option value="">=== Pilih Farm ===</option>
                            @foreach($availableFarms as $farm)
                                <option value="{{ $farm->id }}">{{ $farm->nama }}</option>
                            @endforeach
                        </select>
                        <!--end::Select2-->
                        @error('jenis')
                        <span class="text-danger">{{ $message }}</span> @enderror
                        @error('jenis')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group-->
                    <div class="fv-row mb-7">
                        <label for="name" class="required fw-semibold fs-6 mb-2">Nama</label>
                        <input type="text" class="form-control" wire:model="name" id="name">
                        @error('name') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    <div class="fv-row mb-7">
                        <label for="email" class="required fw-semibold fs-6 mb-2">Email</label>
                        <input type="email" class="form-control" wire:model="email" id="email">
                        @error('email') <span class="text-danger error">{{ $message}}</span>@enderror
                    </div>
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label for="password" class="required fw-semibold fs-6 mb-2">Password</label>
                        <input type="password" class="form-control" wire:model="password" id="password">
                        @error('password') <span class="text-danger error">{{ $message}}</span>@enderror
                    </div>
                    <!--end::Input group--->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="storeFarmOperator()">Save changes</button>
            </div>
        </div>
    </div>
</div>

