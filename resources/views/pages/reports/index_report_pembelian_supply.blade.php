<x-default-layout>


    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-2 text-gray-800">📊 Laporan Pembelian Supply/OVK</h2>
                <p class="text-muted">Analisis detail pembelian supply/OVK berdasarkan batch, supplier, dan periode</p>
            </div>
            <div class="text-right">
                <small class="text-muted">
                    <i class="fas fa-clock"></i> {{ now()->format('d M Y, H:i') }} WIB
                </small>
            </div>
        </div>
        <livewire:reports.advanced-supply-purchase-report />

    </div>

</x-default-layout>