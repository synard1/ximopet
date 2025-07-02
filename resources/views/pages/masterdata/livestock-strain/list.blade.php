<x-default-layout>

    @section('title')
    Master Data Strain
    @endsection

    @section('breadcrumbs')
    @endsection
    @if(auth()->user()->can('read livestock strain master data'))
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar" id="cardToolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    @if (auth()->user()->can('create livestock strain master data'))
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('showCreateForm')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data
                    </button>
                    <!--end::Add user-->
                    @endif
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <div id="datatable-section" class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <!--end::Table-->
            <!-- Include the Livestock Strain Form Component -->
            <livewire:master-data.livestock-strain.create />
        </div>
        <!--end::Card body-->
    </div>


    @else
    <div class="card">
        <div class="card-body">
            <div class="text-center">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3 class="text-danger">Unauthorized Access</h3>
                <p class="text-muted">You do not have permission to view livestock strains.</p>
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
                $('#livestock-strain-form-section').show();
            });

            window.addEventListener('show-datatable', () => {
                $('#datatable-section').show();
                $('#cardToolbar').show();
                $('#livestock-strain-form-section').hide();
            });

        });

        // Listen for Livewire events to reinitialize DataTables
        document.addEventListener("livewire:load", function () {
            Livewire.hook('message.processed', (message, component) => {
                if ($.fn.DataTable.isDataTable('.dataTable')) {
                    $('.dataTable').DataTable().ajax.reload(null, false);
                }
            });
        });
    </script>
    @endpush
    @livewire('qa-checklist-monitor', ['url' => request()->path()])
    @livewire('admin-monitoring.permission-info')
</x-default-layout>