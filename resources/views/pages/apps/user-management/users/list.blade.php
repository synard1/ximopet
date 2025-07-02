<x-default-layout>

    @section('title')
    Users
    @endsection

    @section('breadcrumbs')
    {{ Breadcrumbs::render('user-management') ?? ''}}
    @endsection

    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Search user"
                        id="mySearchInput" />
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" data-kt-action="new_user">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Add User
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->

                <!--begin::Modal-->
                {{-- <livewire:user.add-user-modal></livewire:user.add-user-modal> --}}
                <!--end::Modal-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    <livewire:user.add-user-modal />

    <!-- Stylish Error/Warning Modal -->
    <div class="modal fade" id="kt_modal_user_blockers" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-10 px-5">
                    <div id="user-blockers-icon" class="mb-4">
                        <!-- Icon will be injected by JS -->
                    </div>
                    <h4 id="user-blockers-title" class="mb-2 fw-bold"></h4>
                    <div id="user-blockers-text" class="mb-2"></div>
                    <ul id="user-blockers-list" class="text-start mx-auto mb-4" style="max-width: 400px;"></ul>
                    <div id="user-blockers-actions" class="d-flex justify-content-center gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['users-table'].search(this.value).draw();
            });

    // Listen for Livewire events for error-modal and confirm
    document.addEventListener('livewire:init', function () {
        Livewire.on('error-modal', function (data) {
            // Unwrap if data is delivered inside an extra array index
            if (Array.isArray(data) && data.length === 1 && typeof data[0] === 'object') {
                data = data[0];
            }
            console.log('Blockers:', data.blockers);
            showUserBlockersModal(data, false);
        });
        // Livewire.on('confirm', function (data) {
        //     console.log('Blockers:', data.blockers);
        //     console.log('confirm');
        //     showUserBlockersModal(data, false);
        // });

        Livewire.on('confirm', (params = {}) => {
            console.log('params:', params[0]);
            // Pastikan params adalah object
            let p = {};
            if (Array.isArray(params[0])) {
                [
                    'title',
                    'text', 
                    'confirmButtonText',
                    'cancelButtonText',
                    'onConfirmed',
                    'onCancelled',
                    'params',
                    'blockers'
                ].forEach((key, i) => {
                    if (typeof params[0][i] !== 'undefined') p[key] = params[0][i];
                });
            } else if (typeof params[0] === 'object' && params[0] !== null) {
                p = params[0];
            }

            // Fallbacks for missing values
            p.title = p.title || 'Konfirmasi';
            p.text = p.text || '';
            p.confirmButtonText = p.confirmButtonText || 'Ya';
            p.cancelButtonText = p.cancelButtonText || 'Batal';

            // Tampilkan blockers jika ada
            let htmlBlockers = '';
            if (Array.isArray(p.blockers) && p.blockers.length > 0) {
                htmlBlockers = '<ul style="text-align:left;max-width:400px;margin:10px auto;">' +
                    p.blockers.map(reason => `<li>${reason}</li>`).join('') +
                    '</ul>';
            }

            Swal.fire({
                title: p.title,
                html: `<div>${p.text}</div>${htmlBlockers}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: p.confirmButtonText,
                cancelButtonText: p.cancelButtonText,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch(p.onConfirmed, p.params);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Livewire.dispatch(p.onCancelled);
                }
            });
        });
    });

    

    function showUserBlockersModal(data, isConfirm = false, confirmData = null) {
        // Set icon
        let iconHtml = '';
        if (data.icon === 'warning') {
            iconHtml = '<i class="fa fa-exclamation-triangle text-warning fa-3x"></i>';
        } else if (data.icon === 'error') {
            iconHtml = '<i class="fa fa-times-circle text-danger fa-3x"></i>';
        } else {
            iconHtml = '<i class="fa fa-info-circle text-primary fa-3x"></i>';
        }
        document.getElementById('user-blockers-icon').innerHTML = iconHtml;
        // Set title
        document.getElementById('user-blockers-title').innerText = data.title || '';
        // Set text
        document.getElementById('user-blockers-text').innerText = data.text || '';
        // Set blockers list
        let listHtml = '';
        if (Array.isArray(data.blockers)) {
            data.blockers.forEach(function (reason) {
                listHtml += '<li>' + reason + '</li>';
            });
        }
        document.getElementById('user-blockers-list').innerHTML = listHtml;
        // Set actions
        let actionsHtml = '';
        if (isConfirm && confirmData) {
            actionsHtml += `<button type="button" class="btn btn-warning" id="user-blockers-confirm-btn">${confirmData.confirmButtonText || 'Ya'}</button>`;
            actionsHtml += `<button type="button" class="btn btn-light" data-bs-dismiss="modal">${confirmData.cancelButtonText || 'Batal'}</button>`;
        } else {
            actionsHtml += `<button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>`;
        }
        document.getElementById('user-blockers-actions').innerHTML = actionsHtml;
        // Show modal
        let modal = new bootstrap.Modal(document.getElementById('kt_modal_user_blockers'));
        modal.show();
        // Confirm button event
        if (isConfirm && confirmData) {
            setTimeout(function () {
                let btn = document.getElementById('user-blockers-confirm-btn');
                if (btn) {
                    btn.onclick = function () {
                        modal.hide();
                        window.livewire.emit(confirmData.onConfirmed, confirmData.params.id);
                    };
                }
            }, 100);
        }
    }
            // document.addEventListener('livewire:init', function () {
            //     Livewire.on('success', function () {
            //         $('#kt_modal_add_user').modal('hide');
            //         window.LaravelDataTables['users-table'].ajax.reload();
            //     });
            // });
            // $('#kt_modal_add_user').on('hidden.bs.modal', function () {
            //     Livewire.dispatch('new_user');
            // });
    </script>
    @endpush

</x-default-layout>