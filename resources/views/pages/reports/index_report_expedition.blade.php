<x-default-layout>
    @section('title')
    Laporan Ekspedisi
    @endsection

    <div class="card">
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan Ekspedisi</h2>

            <form id="filter-form" class="mb-5">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label required">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date"
                            value="{{ date('Y-m-d', strtotime('-1 month')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label required">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="end_date" name="end_date"
                            value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="expedition_id" class="form-label">Ekspedisi</label>
                        <select class="form-select" id="expedition_id" name="expedition_id">
                            <option value="">Semua Ekspedisi</option>
                        </select>
                    </div>
                    {{-- <div class="col-md-3">
                        <label for="zone" class="form-label">Zona Tujuan</label>
                        <input type="text" class="form-control" id="zone" name="zone" placeholder="Misal: Zona A">
                    </div> --}}
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label for="transaction_type" class="form-label">Jenis Transaksi</label>
                        <select class="form-select" id="transaction_type" name="transaction_type">
                            <option value="">Semua</option>
                            <option value="livestock_purchase">Pembelian Livestock</option>
                            <option value="feed_purchase">Pembelian Pakan</option>
                            <option value="supply_purchase">Pembelian Supply</option>
                            <option value="sales">Penjualan</option>
                            <option value="livestock_sales">Penjualan Livestock</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua</option>
                            <option value="pending">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_header" checked>
                            <label class="form-check-label" for="include_header">Sertakan header saat print</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" id="showButton">
                        <i class="fas fa-search me-2"></i>Tampilkan
                    </button>
                    <button type="button" class="btn btn-success ms-2 d-none" id="printFullButton">
                        <i class="fas fa-print me-2"></i>Print (Full)
                    </button>
                    <button type="button" class="btn btn-outline-success ms-2 d-none" id="printTableButton">
                        <i class="fas fa-print me-2"></i>Print (Hanya Tabel)
                    </button>
                    <button type="reset" class="btn btn-secondary ms-2" id="resetButton">
                        <i class="fas fa-redo me-2"></i>Reset
                    </button>
                </div>
            </form>

            <div id="report-content"></div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
			// Load expeditions for select
			loadExpeditions();

			// Submit filter
			$('#filter-form').on('submit', function(e) {
				e.preventDefault();
				loadReport();
			});

			// Reset
			$('#resetButton').on('click', function() {
				$('#filter-form')[0].reset();
				$('#report-content').empty();
				$('#printFullButton, #printTableButton').addClass('d-none');
			});

			$('#printFullButton').on('click', function(){
				printReport(true);
			});
			$('#printTableButton').on('click', function(){
				printReport(false);
			});
		});

		function getFilterParams() {
			return {
				start_date: $('#start_date').val(),
				end_date: $('#end_date').val(),
				expedition_id: $('#expedition_id').val(),
				transaction_type: $('#transaction_type').val(),
				zone: $('#zone').val(),
				status: $('#status').val()
			};
		}

		function loadExpeditions() {
			$.get('/api/expedition/list', function(resp){
				if (resp && resp.success && resp.data) {
					const select = $('#expedition_id');
					resp.data.forEach(function(item){ select.append(new Option(item.name, item.id)); });
				}
			});
		}

		function loadReport(includeHeader) {
			const params = Object.assign({}, getFilterParams(), { include_header: $('#include_header').is(':checked') });
			$('#report-content').html('<div class="text-center py-5"><span class="spinner-border"></span> Memuat laporan...</div>');
			$.ajax({
				url: '{{ route('report.expedition.export') }}',
				method: 'POST',
				data: Object.assign(params, { _token: '{{ csrf_token() }}' }),
				success: function(html) {
					$('#report-content').empty();
					var iframe = $('<iframe>', { id: 'report-iframe', frameborder: 0, scrolling: 'yes', width: '100%', height: '600px' }).appendTo('#report-content');
					var doc = iframe[0].contentDocument || iframe[0].contentWindow.document;
					doc.open(); doc.write(html); doc.close();
					$('#printFullButton, #printTableButton').removeClass('d-none');
				},
				error: function(xhr){
					let msg = 'Gagal memuat laporan.';
					if (xhr.responseText) msg = xhr.responseText;
					$('#report-content').html('<div class="alert alert-danger">'+msg+'</div>');
					$('#printFullButton, #printTableButton').addClass('d-none');
				}
			});
		}

		function printReport(includeHeader) {
			// Jika opsi berbeda dari checkbox saat ini, muat ulang lalu print
			const desired = !!includeHeader;
			const current = $('#include_header').is(':checked');
			if (desired !== current) {
				$('#include_header').prop('checked', desired);
				loadReport();
				setTimeout(function(){
					var iframe = document.getElementById('report-iframe');
					if (iframe && iframe.contentWindow) iframe.contentWindow.print();
				}, 800);
				return;
			}
			var iframe = document.getElementById('report-iframe');
			if (iframe && iframe.contentWindow) iframe.contentWindow.print();
		}
    </script>
    @endpush
</x-default-layout>