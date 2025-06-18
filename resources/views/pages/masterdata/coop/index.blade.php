<x-default-layout>

    @section('title')
    Master Data {{ trans('content.coop',[],'id') }}
    @endsection

    @section('breadcrumbs')
    @endsection

    @if(auth()->user()->can('read coop master data'))
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
                    @if (auth()->user()->can('create coop master data'))
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('createCoop')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Kandang
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
            <!--begin::Table-->
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    @if(auth()->user()->can('create coop master data'))
    <!-- Include the Kandang Form Component -->
    <livewire:master-data.kandang-form />
    @endif
    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('success', function () {
                window.LaravelDataTables['kandangs-table'].ajax.reload();
            });
        });
    </script>
    @endpush
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

    @livewire('admin-monitoring.permission-info')

    @livewire('qa-checklist-monitor', ['url' => request()->path()])
</x-default-layout>