<x-default-layout>
    @section('title')
    Livestock Tracking & Origin Chain
    @endsection

    @section('breadcrumbs')
    <div class="d-flex align-items-center flex-wrap me-1">
        <div class="d-flex align-items-center my-2">
            <a href="#" class="text-gray-400 text-hover-primary me-2">
                <i class="ki-outline ki-home fs-2"></i>
            </a>
            <i class="ki-outline ki-right fs-3 text-gray-400 me-2"></i>
            <a href="#" class="text-gray-400 text-hover-primary me-2 fw-semibold">Livestock</a>
            <i class="ki-outline ki-right fs-3 text-gray-400 me-2"></i>
            <span class="text-gray-600 fw-semibold">Tracking</span>
        </div>
    </div>
    @endsection

    <!-- Livestock Tracking Component -->
    <div class="container-fluid px-0">
        @if(isset($livestockId))
        <livewire:livestock.tracking.livestock-tracking-view :livestock-id="$livestockId" />
        @else
        <livewire:livestock.tracking.livestock-tracking-view />
        @endif
    </div>

</x-default-layout>