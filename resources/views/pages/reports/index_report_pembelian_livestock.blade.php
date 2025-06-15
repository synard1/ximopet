<x-default-layout>

    @section('title', 'Laporan Pembelian Livestock')

    <!--begin::Content wrapper-->
    <div class="d-flex flex-column flex-column-fluid">
        {{--
        <!--begin::Toolbar-->
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <!--begin::Toolbar container-->
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <!--begin::Page title-->
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        Laporan Pembelian Livestock
                    </h1>
                    <!--end::Title-->
                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">Reports</li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">Pembelian Livestock</li>
                    </ul>
                    <!--end::Breadcrumb-->
                </div>
                <!--end::Page title-->
            </div>
            <!--end::Toolbar container-->
        </div>
        <!--end::Toolbar--> --}}

        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">Filter Laporan Pembelian Livestock</h3>
                    </div>
                    <!--end::Search-->
                </div>
                <!--begin::Card title-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body py-4">
                <h2 class="mb-4">Filter Laporan Pembelian Livestock</h2>
                <form id="filter-form" class="mb-5">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="farm" class="form-label required">Farm</label>
                            <select class="form-select" id="farm" name="farm">
                                <option value="">Pilih Farm</option>
                                @foreach($farms as $farm)
                                <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="coop" class="form-label required">Kandang</label>
                            <select class="form-select" id="coop" name="coop" disabled>
                                <option value="">Pilih Kandang</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tahun" class="form-label required">Tahun</label>
                            <select class="form-select" id="tahun" name="tahun" disabled>
                                <option value="">Pilih Tahun</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="periode" class="form-label required">Periode (Batch)</label>
                            <select class="form-select" id="periode" name="periode" disabled>
                                <option value="">Pilih Periode</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="supplier" class="form-label">Supplier</label>
                            <select class="form-select" id="supplier" name="supplier">
                                <option value="">Semua Supplier</option>
                                @foreach($partners as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="arrived">Arrived</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label required">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required
                                value="{{ request('start_date', \Carbon\Carbon::now()->subMonth()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label required">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required
                                value="{{ request('end_date', \Carbon\Carbon::now()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" id="showButton">
                                <i class="fas fa-search me-2"></i>Tampilkan
                            </button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-success w-100 ms-2" id="exportButton">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </button>
                        </div>
                    </div>
                </form>
                <div id="report-content">
                    <!-- Report content will be loaded here -->
                </div>
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->

        <!--begin::Info Card-->
        <div class="card mt-6">
            <div class="card-body">
                <div class="d-flex align-items-center mb-6">
                    <i class="ki-duotone ki-information-5 fs-2x text-primary me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div>
                        <h4 class="mb-1">Informasi Laporan</h4>
                        <p class="text-muted mb-0">Panduan penggunaan laporan pembelian livestock</p>
                    </div>
                </div>

                <div class="row g-6">
                    <div class="col-md-4">
                        <div class="bg-light-primary rounded p-4">
                            <i class="ki-duotone ki-calendar fs-2x text-primary mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h6 class="fw-bold mb-2">Filter Periode</h6>
                            <p class="text-muted fs-7 mb-0">
                                Tentukan rentang tanggal untuk data pembelian yang ingin ditampilkan
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="bg-light-success rounded p-4">
                            <i class="ki-duotone ki-filter fs-2x text-success mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h6 class="fw-bold mb-2">Filter Lanjutan</h6>
                            <p class="text-muted fs-7 mb-0">
                                Gunakan filter farm, supplier, ekspedisi, dan status untuk hasil yang lebih
                                spesifik
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="bg-light-warning rounded p-4">
                            <i class="ki-duotone ki-document fs-2x text-warning mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h6 class="fw-bold mb-2">Format Export</h6>
                            <p class="text-muted fs-7 mb-0">
                                Pilih format sesuai kebutuhan: HTML untuk preview, Excel/PDF untuk dokumen
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Info Card-->
    </div>
    <!--end::Content wrapper-->


    @push('scripts')
    <script>
        $(document).ready(function() {
            var livestockData = @json($livestocks);
            // Dynamic filter logic (same as performa)
            const coopSelect = document.getElementById('coop');
            const tahunSelect = document.getElementById('tahun');
            const periodeSelect = document.getElementById('periode');
            $('#farm').on('change', function() {
                var farmId = $(this).val();
                updateCoopOptions(farmId);
                coopSelect.disabled = false;
            });
            $('#coop').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $(this).val();
                updateTahunOptions(farmId, coopId);
            });
            $('#tahun').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $('#coop').val();
                var tahun = $(this).val();
                updatePeriodeOptions(farmId, coopId, tahun);
            });
            function updateCoopOptions(farmId) {
                var coopSelect = $('#coop');
                coopSelect.empty().append(new Option('Pilih Kandang', ''));
                if (farmId) {
                    var farmLivestock = livestockData.filter(function(l) { return l.farm_id == farmId; });
                    var uniqueCoops = [];
                    farmLivestock.forEach(function(l) {
                        if (!uniqueCoops.some(k => k.id === l.coop_id)) {
                            uniqueCoops.push({ id: l.coop_id, name: l.coop_name });
                        }
                    });
                    uniqueCoops.forEach(function(kandang) {
                        coopSelect.append(new Option(kandang.name, kandang.id));
                    });
                    coopSelect.prop('disabled', false);
                } else {
                    coopSelect.prop('disabled', true);
                }
            }
            function updateTahunOptions(farmId, coopId) {
                var tahunSelect = $('#tahun');
                tahunSelect.empty().append(new Option('Pilih Tahun', ''));
                tahunSelect.prop('disabled', true);
                if (farmId && coopId) {
                    var filtered = livestockData.filter(function(l) { return l.farm_id == farmId && l.coop_id == coopId; });
                    var uniqueYears = [...new Set(filtered.map(l => new Date(l.start_date).getFullYear()))];
                    uniqueYears.sort((a, b) => b - a);
                    uniqueYears.forEach(function(year) {
                        tahunSelect.append(new Option(year, year));
                    });
                    tahunSelect.prop('disabled', false);
                }
            }
            function updatePeriodeOptions(farmId, coopId, tahun) {
                var periodeSelect = $('#periode');
                periodeSelect.empty().append(new Option('Pilih Periode', ''));
                periodeSelect.prop('disabled', true);
                if (farmId && coopId && tahun) {
                    var filtered = livestockData.filter(function(l) {
                        return l.farm_id == farmId && l.coop_id == coopId && new Date(l.start_date).getFullYear() == tahun;
                    });
                    var uniquePeriodes = filtered.map(l => ({ id: l.id, name: l.name }));
                    uniquePeriodes.sort((a, b) => a.name.localeCompare(b.name));
                    uniquePeriodes.forEach(function(periode) {
                        periodeSelect.append(new Option(periode.name, periode.id));
                    });
                    periodeSelect.prop('disabled', false);
                }
            }
            // Handle form submit (AJAX load report)
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();
                $('#report-content').html('<div class="text-center py-5"><span class="spinner-border"></span> Memuat laporan...</div>');
                $.ajax({
                    url: '/report/livestock-purchase/export',
                    method: 'POST',
                    data: data + '&export_format=html',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(data) {
                        // Clear previous content
                        $('#report-content').empty();
                        var iframe = $('<iframe>', {
                            id: 'report-iframe',
                            frameborder: 0,
                            scrolling: 'yes',
                            width: '100%',
                            height: '500px'
                        }).appendTo('#report-content');
                        var iframeDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
                        iframeDoc.open();
                        iframeDoc.write(data);
                        iframeDoc.close();
                        iframe.on('load', function() {
                            var printBtn = $('<button>', {
                                text: 'Print Report',
                                class: 'btn btn-primary mt-3 me-2',
                                click: function() {
                                    iframe[0].contentWindow.print();
                                }
                            }).appendTo('#report-content');
                        });
                    },
                    error: function(xhr) {
                        let msg = 'Gagal memuat laporan.';
                        if(xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                        $('#report-content').html('<div class="alert alert-danger">'+msg+'</div>');
                    }
                });
            });
            // Handle export button
            $('#exportButton').on('click', function() {
                // TODO: Export logic here
            });
        });
    </script>
    @endpush
</x-default-layout>