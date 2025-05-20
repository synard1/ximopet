<x-default-layout>

    @section('title')
    Routes Manager
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


        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <livewire:route-manager />


        </div>
        <!--end::Card body-->
    </div>

    @push('scripts')

    @endpush
</x-default-layout>