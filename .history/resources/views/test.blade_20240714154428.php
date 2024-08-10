<x-default-layout>

    @section('title')
        test
    @endsection

    @section('breadcrumbs')
    @endsection

    <!--begin::Row-->
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <livewire
        @include('livewire.contacts')
    </div>
    <!--end::Row-->
</x-default-layout>

