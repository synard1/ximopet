<div class="modal d-block" tabindex="-1" role="dialog" id="kt_modal_user" tabindex="-1" aria-hidden="true"
    wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $user_id ? 'Ubah Data User' : 'Buat Data User' }}</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-dismiss="modal" aria-label="Close"
                    wire:click="closeModal()">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <form>
                    {{-- @if($user_id)
                    <div class="fv-row mb-7">
                        <label for="user_id" class="fw-semibold fs-6 mb-2">User ID</label>
                        <input type="text" class="form-control" wire:model="user_id" id="user_id" {{ $user_id
                            ? 'disabled' : '' }}>
                        @error('user_id') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                    @endif --}}
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
                    <div class="fv-row mb-7"
                        x-data="{ show: false, copied: false, copy() { navigator.clipboard.writeText($refs.pass.value); this.copied = true; setTimeout(() => this.copied = false, 2000); } }">
                        <label class="required fw-semibold fs-6 mb-5">Password</label>
                        <div class="input-group">
                            <input :type="show ? 'text' : 'password'" class="form-control form-control-solid"
                                wire:model.defer="password" placeholder="Password" id="passwordField" x-ref="pass" />
                            <button class="btn btn-light" type="button" @click="show = !show" tabindex="-1">
                                <span x-show="!show"><i class="fa fa-eye"></i></span>
                                <span x-show="show"><i class="fa fa-eye-slash"></i></span>
                            </button>
                            <button class="btn btn-light" type="button" @click="copy" tabindex="-1">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                        <template x-if="copied">
                            <div class="text-success small mt-2">Password berhasil disalin!</div>
                        </template>
                    </div>
                    <!--end::Input group--->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7" x-data="{ show: false }">
                        <label class="required fw-semibold fs-6 mb-5">Password Confirmation</label>
                        <div class="input-group">
                            <input :type="show ? 'text' : 'password'" class="form-control form-control-solid"
                                wire:model.defer="passwordConfirmation" placeholder="Password Confirmation"
                                id="passwordConfirmationField" />
                            <button class="btn btn-light" type="button" @click="show = !show" tabindex="-1">
                                <span x-show="!show"><i class="fa fa-eye"></i></span>
                                <span x-show="show"><i class="fa fa-eye-slash"></i></span>
                            </button>
                        </div>
                    </div>
                    <!--end::Input group--->
                    <!--begin::Accordion for Auto Generate Password-->
                    <div class="mb-7">
                        <button type="button" class="btn btn-light btn-sm mb-2" data-bs-toggle="collapse"
                            data-bs-target="#generatePasswordOptions" aria-expanded="false"
                            aria-controls="generatePasswordOptions">
                            Opsi Auto Generate Password
                        </button>
                        <div class="collapse" id="generatePasswordOptions">
                            <div class="card card-body border shadow-sm">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Karakter</label>
                                    <input type="number" min="6" max="64" class="form-control form-control-sm w-25"
                                        wire:model.defer="generate_length" />
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox"
                                            wire:model.defer="generate_uppercase" id="genUppercase">
                                        <label class="form-check-label" for="genUppercase">Huruf Besar (A-Z)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox"
                                            wire:model.defer="generate_lowercase" id="genLowercase">
                                        <label class="form-check-label" for="genLowercase">Huruf Kecil (a-z)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox"
                                            wire:model.defer="generate_numbers" id="genNumbers">
                                        <label class="form-check-label" for="genNumbers">Angka (0-9)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox"
                                            wire:model.defer="generate_symbols" id="genSymbols">
                                        <label class="form-check-label" for="genSymbols">Simbol (!@#$...)</label>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm"
                                    wire:click="generatePassword">Generate Password</button>
                                <div class="form-text mt-2">Password hasil generate akan otomatis mengisi field password
                                    dan konfirmasi password.</div>
                            </div>
                        </div>
                    </div>
                    <!--end::Accordion for Auto Generate Password-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-5">Role</label>
                        <!--end::Label-->
                        @error('role')
                        <span class="text-danger">{{ $message }}</span> @enderror

                        <!--begin::Roles-->
                        @foreach($roles as $role)
                        <!--begin::Input row-->
                        <div class="d-flex fv-row">
                            <!--begin::Radio-->
                            <div class="form-check form-check-custom form-check-solid">
                                <!--begin::Input-->
                                <input class="form-check-input me-3" id="kt_modal_update_role_option_{{ $role->id }}"
                                    wire:model="role" name="role" type="radio" value="{{ $role->name }}" @if($role->name
                                === $role) checked="checked" @endif/>
                                <!--end::Input-->
                                <!--begin::Label-->
                                <label class="form-check-label" for="kt_modal_update_role_option_{{ $role->id }}">
                                    <div class="fw-bold text-gray-800">
                                        {{ ucwords($role->name) }}
                                    </div>
                                    <div class="text-gray-600">
                                        {{ $role->description }}
                                    </div>
                                </label>
                                <!--end::Label-->
                            </div>
                            <!--end::Radio-->
                        </div>
                        <!--end::Input row-->
                        @if(!$loop->last)
                        <div class='separator separator-dashed my-5'></div>
                        @endif
                        @endforeach
                        <!--end::Roles-->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closeModal()">Close</button>
                <button type="button" class="btn btn-primary" wire:click="store()">Save changes</button>
            </div>
        </div>
    </div>
</div>