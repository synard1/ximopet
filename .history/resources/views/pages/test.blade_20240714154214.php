<x-default-layout>

    @section('title')
        Dashboard
    @endsection

    @section('breadcrumbs')
        {{ Breadcrumbs::render('dashboard') }}
    @endsection

    <!--begin::Row-->
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @include('livewire.create')
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            @include('partials/widgets/cards/_widget-20')

            @include('partials/widgets/cards/_widget-7')
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
            @include('partials/widgets/cards/_widget-17')

            @include('partials/widgets/lists/_widget-26')
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-xxl-6">
            @include('partials/widgets/engage/_widget-10')
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->
</x-default-layout>

