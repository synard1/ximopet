<x-default-layout>

    @section('title')
        Master Data Pekerja Kandang
    @endsection

    @section('breadcrumbs')
    @endsection
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
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('showCreateForm')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pekerja
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->

        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <div id="datatable-container">
                <!--begin::Table-->
                <div class="table-responsive">
                    {{ $dataTable->table() }}
                </div>
                <!--end::Table-->
            </div>
            <livewire:master-data.worker.create />
        </div>
        <!--end::Card body-->

    </div>

    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            document.addEventListener('livewire:init', function () {
                window.addEventListener('hide-datatable', () => {
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                });

                window.addEventListener('show-datatable', () => {
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                });
                
            });

        </script>
    @endpush
</x-default-layout>

