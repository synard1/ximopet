{{-- Halaman Smart Analytics --}}
<x-default-layout>
    @section('title')
    Smart Analytics - Analisis Cerdas Peternakan
    @endsection

    @section('breadcrumbs')
    {{ Breadcrumbs::render('smart-analytics') }}
    @endsection

    {{-- Main content --}}
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-fluid">

                {{-- Header --}}
                <div class="card mb-5">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row align-items-center">
                            <div class="flex-grow-1">
                                <h1 class="text-gray-900 fw-bold fs-2 mb-2">
                                    <i class="ki-duotone ki-chart-line-up text-primary fs-1 me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Smart Analytics
                                </h1>
                                <p class="text-muted fs-6 mb-0">
                                    Analisis cerdas untuk mengidentifikasi kandang dengan mortalitas tinggi,
                                    performa penjualan, dan metrik produksi berdasarkan bobot ayam dan faktor lainnya.
                                </p>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="badge badge-light-primary fs-7">
                                    <i class="ki-duotone ki-pulse text-primary fs-6 me-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Real-time Analytics
                                </div>
                                <div class="badge badge-light-success fs-7">
                                    <i class="ki-duotone ki-verify text-success fs-6 me-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    AI-Powered Insights
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Smart Analytics Component --}}

                <livewire:smart-analytics />

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Initialize any additional JavaScript functionality if needed
        document.addEventListener('DOMContentLoaded', function() {
            log('Smart Analytics page loaded');
        });
    </script>
    @endpush

</x-default-layout>