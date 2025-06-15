<x-default-layout>

    @section('title', 'Laporan Pembelian Pakan')

    <div class="card">
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan Pembelian Pakan</h2>
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
                        <label for="feed" class="form-label">Jenis Pakan</label>
                        <select class="form-select" id="feed" name="feed">
                            <option value="">Semua Pakan</option>
                            @foreach($feeds as $feed)
                            <option value="{{ $feed->id }}">{{ $feed->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
    </div>

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
                    url: '/report/feed-purchase/export',
                    method: 'POST',
                    data: data + '&export_format=html',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(data) {
                        // Clear previous content
                        $('#report-content').empty();
                        
                        // Create an iframe to display the report
                        var iframe = $('<iframe>', {
                            id: 'report-iframe',
                            frameborder: 0,
                            scrolling: 'yes',
                            width: '100%',
                            height: '500px'
                        }).appendTo('#report-content');

                        // Set iframe content
                        var iframeDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
                        iframeDoc.open();
                        iframeDoc.write(data);
                        iframeDoc.close();

                        // Ensure the iframe content is fully loaded before adding buttons
                        iframe.on('load', function() {
                            // Add print button
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