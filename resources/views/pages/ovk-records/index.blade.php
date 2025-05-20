<x-default-layout>

    @section('title')
    Data Penjualan Ayam
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="stokTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-primary" onclick="Livewire.dispatch('showCreateForm')">
                    <i class="ki-duotone ki-plus fs-2"></i>Add New Record
                </button>
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
            <div id="form-container">

            </div>
            <livewire:ovk.create />


        </div>
        <!--end::Card body-->
    </div>

    @push('scripts')
    {{ $dataTable->scripts() }}

    <script>
        document.addEventListener('livewire:init', function () {
                window.addEventListener('hide-datatable', () => {
                    $('#datatable-container').hide();
                    $('#form-container').hide();
                    $('#cardToolbar').hide();
                });

                window.addEventListener('show-datatable', () => {
                    $('#datatable-container').show();
                    $('#form-container').show();
                    $('#cardToolbar').show();
                });

            });

    </script>
    @endpush
</x-default-layout>