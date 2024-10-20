<div class="modal d-block" tabindex="-1" role="dialog" id="kt_modal_user" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $user_id ? 'Ubah Data User' : 'Buat Data User' }}</h5>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-dismiss="modal" aria-label="Close" wire:click="closeModal()">
                    {!! getIcon('cross','fs-1') !!}
                </div>
                <!--end::Close-->
            </div>
            <div class="modal-body">
                <form>
                {{-- @if($user_id)
                    <div class="fv-row mb-7">
                        <label for="user_id" class="fw-semibold fs-6 mb-2">User ID</label>
                        <input type="text" class="form-control" wire:model="user_id" id="user_id" {{ $user_id ? 'disabled' : '' }}>
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
                    <div class="fv-row mb-7">
                        <label for="password" class="required fw-semibold fs-6 mb-2">Password</label>
                        <input type="password" class="form-control" wire:model="password" id="password">
                        @error('password') <span class="text-danger error">{{ $message}}</span>@enderror
                    </div>
                    <!--end::Input group--->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label for="passwordConfirmation" class="required fw-semibold fs-6 mb-2">Password Confirmation</label>
                        <input type="password" class="form-control" wire:model="passwordConfirmation" id="passwordConfirmation">
                        @error('passwordConfirmation') <span class="text-danger error">{{ $message}}</span>@enderror
                    </div>
                    <!--end::Input group--->
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
                                    <input class="form-check-input me-3" id="kt_modal_update_role_option_{{ $role->id }}" wire:model="role" name="role" type="radio" value="{{ $role->name }}" 
                                    @if($role->name === $role) checked="checked" @endif/>
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
