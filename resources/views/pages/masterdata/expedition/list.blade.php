<x-default-layout>

    @section('title')
    Master Data Ekspedisi
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
                <button id="tambah-ekspedisi-btn" class="btn btn-primary"
                    onclick="Livewire.dispatch('createShowModal')">
                    Tambah Ekspedisi
                </button>
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <div id="datatable-section" class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <livewire:master-data.expedition.create />

        </div>
        <!--end::Card body-->
    </div>


    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        document.addEventListener('livewire:init', function () {
            window.addEventListener('hide-datatable', () => {
                $('#datatable-section').hide();
                $('#cardToolbar').hide();
                $('#expedition-form-section').show();
                // const cardForm = document.getElementById('expedition-form-section');
                // if (cardForm) {
                //     cardForm.style.display = 'block';
                    
                // }
            });

            window.addEventListener('show-datatable', () => {
                $('#datatable-section').show();
                $('#cardToolbar').show();
                $('#expedition-form-section').hide();
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