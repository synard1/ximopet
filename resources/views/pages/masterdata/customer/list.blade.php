<x-default-layout>

    @section('title')
    Master Data Customer
    @endsection

    @section('breadcrumbs')
    @endsection

    @if(auth()->user()->can('create customer master data'))
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                {{--
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Cari Customer"
                        id="mySearchInput" />
                </div>
                <!--end::Search-->
                --}}
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                @if(auth()->user()->can('create customer master data'))
                <!--begin::Card toolbar-->
                <div class="card-toolbar" id="cardToolbar">
                    <button id="tambah-ekspedisi-btn" class="btn btn-primary"
                        onclick="Livewire.dispatch('createShowModal')">
                        Tambah Data Customer
                    </button>
                </div>
                <!--end::Card toolbar-->
                @endif
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <div id="datatable-section" class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            @if(auth()->user()->can('create customer master data'))
            <livewire:master-data.customer.create />
            @endif

        </div>
        <!--end::Card body-->
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <div class="text-center">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3 class="text-danger">Unauthorized Access</h3>
                <p class="text-muted">You do not have permission to view coop.</p>
            </div>
        </div>
    </div>
    @endif
    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        document.addEventListener('livewire:init', function () {
            window.addEventListener('hide-datatable', () => {
                $('#datatable-section').hide();
                $('#cardToolbar').hide();
                $('#customer-form-section').show();
                // const cardForm = document.getElementById('expedition-form-section');
                // if (cardForm) {
                //     cardForm.style.display = 'block';
                    
                // }
            });

            window.addEventListener('show-datatable', () => {
                $('#datatable-section').show();
                $('#cardToolbar').show();
                $('#customer-form-section').hide();
            });

        });
        // document.getElementById('mySearchInput').addEventListener('keyup', function () {
        //     window.LaravelDataTables['customers-table'].search(this.value).draw();
        // });
            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    $('#kt_modal_add_user').modal('hide');
                    window.LaravelDataTables['customers-table'].ajax.reload();
                });
            });
            $('#kt_modal_add_user').on('hidden.bs.modal', function () {
                Livewire.dispatch('new_user');
            });
    </script>
    @endpush
    @livewire('admin-monitoring.permission-info')

    @livewire('qa-checklist-monitor', ['url' => request()->path()])
</x-default-layout>