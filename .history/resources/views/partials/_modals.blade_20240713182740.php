<!--begin::Modals-->
@include('partials/modals/_upgrade-plan')

@include('partials/modals/create-app/_main')

@include('partials/modals/create-campaign/_main')

@include('partials/modals/create-project/_main')

@include('partials/modals/_new-target')

@include('partials/modals/_view-users')

@include('partials/modals/users-search/_main')

@include('partials/modals/_invite-friends')

{{-- @include('partials/master/_supplier') --}}



<div class="modal fade" tabindex="-1" id="kt_modal_1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Modal title</h3>

                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <!--end::Close-->
            </div>

            <div class="modal-body">
                <p>Modal body text goes here.</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!--end::Modals-->
