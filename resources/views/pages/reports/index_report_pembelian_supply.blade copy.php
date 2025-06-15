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
                        <div class="col-md-2">
                            <label for="tahun" class="form-label required">Tahun</label>
                            <select class="form-select" id="tahun" name="tahun">
                                <option value="">Pilih Tahun</option>
                                @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
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
                            <label for="supply" class="form-label">Jenis Supply</label>
                            <select class="form-select" id="supply" name="supply">
                                <option value="">Semua Supply</option>
                                @foreach($supplies as $supply)
                                <option value="{{ $supply->id }}">{{ $supply->name }}</option>
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
    </div>



    @push('scripts')
    <script>
        $(document).ready(function() {
            // No dynamic livestock filter for supply, just static dropdowns
            // Handle form submit (AJAX load report)
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();
                $('#report-content').html('<div class="text-center py-5"><span class="spinner-border"></span> Memuat laporan...</div>');
                $.ajax({
                    url: '/report/supply-purchase/export',
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