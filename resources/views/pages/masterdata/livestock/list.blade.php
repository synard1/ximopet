<x-default-layout>
    @section('title')
    Data {{ trans('content.ternak',[],'id') }}
    @endsection

    @section('breadcrumbs')
    @endsection
    {{-- <div class="card" id="ternaksTables"> --}}
        <div class="card">
            <!--begin::Card body-->
            <div class="card-body py-4">
                <div id="datatable-container" class="table-responsive">
                    <!--begin::Table-->
                    {{ $dataTable->table(['id' => 'ternaks-table']) }}
                    <!--end::Table-->
                </div>

                <livewire:master-data.worker.assign-worker />
                <livewire:records />
            </div>
            <!--end::Card body-->
        </div>

        {{-- @include('pages.masterdata.ternak._modal_ternak_details') --}}
        @include('pages.masterdata.livestock._detail_modal')
        {{-- @include('pages.masterdata.ternak._detail_reports_modal') --}}

        {{--
        <!-- Livewire Container (Hidden by Default) -->
        <div id="assignWorkerContainer" style="display: none;">
            <button class="btn btn-danger mb-3 closeRecordsBtn">Kembali ke Tabel</button>
            <livewire:master-data.worker.assign-worker />

        </div>

        <!-- Livewire Container (Hidden by Default) -->
        <div id="livewireRecordsContainer" style="display: none;">
            <button class="btn btn-danger mb-3 closeRecordsBtn">Kembali ke Tabel</button>
            <livewire:records />
        </div> --}}

        @push('scripts')
        {{ $dataTable->scripts() }}

        <script>
            document.addEventListener('livewire:init', function () {

                window.addEventListener('show-records', () => {
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                    $('#livewireRecordsContainer').show();
                });

                window.addEventListener('show-worker-assign-form', () => {
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                    $('#assignWorkerContainer').show();
                });

                window.addEventListener('hide-worker-assign-form', () => {
                    $('#assignWorkerContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                });

                window.addEventListener('hide-records', () => {
                    $('#livewireRecordsContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                });
            });

            window.addEventListener('notify', event => {
            alert(event.detail.message); // ganti dengan toastr atau lainnya jika ada
        });

        // on modal close kt_modal_ternak_details
        $('#kt_modal_ternak_details').on('hidden.bs.modal', function() {
            $('#detailTable').DataTable().destroy();
        });
        </script>

        @endpush

        @livewire('admin-monitoring.permission-info')

        @livewire('qa-checklist-monitor', ['url' => request()->path()])

</x-default-layout>