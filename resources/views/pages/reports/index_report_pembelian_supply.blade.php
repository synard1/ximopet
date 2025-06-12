<x-default-layout>

    @section('title', 'Laporan Pembelian Supply/OVK')


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

        <!-- Filter Form -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">🔍 Filter Laporan Pembelian Supply/OVK</h6>
            </div>
            <div class="card-body">
                <form id="reportForm" method="GET" action="{{ route('purchase-reports.export-supply') }}">
                    @csrf
                    <div class="row">
                        <!-- Date Range -->
                        <div class="col-md-3 mb-3">
                            <label for="start_date" class="form-label">Tanggal Mulai *</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="end_date" class="form-label">Tanggal Selesai *</label>
                            <input type="date" class="form-control" name="end_date" id="end_date" required>
                        </div>

                        <!-- Farm Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="farm_id" class="form-label">Farm</label>
                            <select class="form-control" name="farm_id" id="farm_id">
                                <option value="">-- Semua Farm --</option>
                                @foreach($farms as $farm)
                                <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Livestock Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="livestock_id" class="form-label">Livestock/Batch</label>
                            <select class="form-control" name="livestock_id" id="livestock_id">
                                <option value="">-- Semua Livestock --</option>
                                @foreach($livestocks as $livestock)
                                <option value="{{ $livestock->id }}">
                                    {{ $livestock->farm->name ?? 'N/A' }} - {{ $livestock->coop->name ?? 'N/A' }} ({{
                                    $livestock->batch_number ?? 'N/A' }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Supplier Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="supplier_id" class="form-label">Supplier</label>
                            <select class="form-control" name="supplier_id" id="supplier_id">
                                <option value="">-- Semua Supplier --</option>
                                @foreach($partners as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Supply Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="supply_id" class="form-label">Jenis Supply/OVK</label>
                            <select class="form-control" name="supply_id" id="supply_id">
                                <option value="">-- Semua Supply --</option>
                                @foreach($supplies as $supply)
                                <option value="{{ $supply->id }}">{{ $supply->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Expedition Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="expedition_id" class="form-label">Ekspedisi</label>
                            <select class="form-control" name="expedition_id" id="expedition_id">
                                <option value="">-- Semua Ekspedisi --</option>
                                @foreach($expeditions as $expedition)
                                <option value="{{ $expedition->id }}">{{ $expedition->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="">-- Semua Status --</option>
                                <option value="draft">Draft</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="arrived">Arrived</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Export Format -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Format Export</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="export_format" value="html" checked>
                                <label class="form-check-label">HTML</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="export_format" value="excel">
                                <label class="form-check-label">Excel</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="export_format" value="pdf">
                                <label class="form-check-label">PDF</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="export_format" value="csv">
                                <label class="form-check-label">CSV</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-chart-bar"></i> Generate Report
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg ms-2"
                                onclick="location.reload()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>



    @push('scripts')
    <script>
        $(document).ready(function() {
    // Set default dates (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    $('#end_date').val(today.toISOString().split('T')[0]);
    $('#start_date').val(thirtyDaysAgo.toISOString().split('T')[0]);
});
    </script>
    @endpush
</x-default-layout>