<x-default-layout>

    @section('title')
        Data Pembelian OVK
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card">

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

    @push('scripts')
        {{ $dataTable->scripts() }}
    @endpush
</x-default-layout>