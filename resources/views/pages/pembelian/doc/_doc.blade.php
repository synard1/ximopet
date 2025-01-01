<x-default-layout>

    @section('title')
        Data Pembelian DOC
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

    @include('pages.pembelian.doc._modal_pembelian_doc_details')

    @push('scripts')
        {{ $dataTable->scripts() }}
    @endpush
</x-default-layout>