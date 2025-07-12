<x-default-layout>
    @section('title')
    Laporan Pemakaian Supply/OVK
    @endsection

    <div class="card">
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan Pemakaian Supply/OVK</h2>

            <form id="filter-form" class="mb-5">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="farm" class="form-label required">Farm</label>
                        <select class="form-select" id="farm" name="farm_id" required>
                            <option value="">Pilih Farm</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="coop" class="form-label">Kandang</label>
                        <select class="form-select" id="coop" name="coop_id" disabled>
                            <option value="">Pilih Kandang</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="livestock" class="form-label">Batch</label>
                        <select class="form-select" id="livestock" name="livestock_id" disabled>
                            <option value="">Pilih Batch</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="supply" class="form-label">Jenis Supply</label>
                        <select class="form-select" id="supply" name="supply_id">
                            <option value="">Semua Supply</option>
                            @foreach($supplies ?? [] as $supply)
                            <option value="{{ $supply->id }}">{{ $supply->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label required">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required
                            value="{{ request('start_date', \Carbon\Carbon::now()->subWeek()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label required">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required
                            value="{{ request('end_date', \Carbon\Carbon::now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="report_type" class="form-label required">Tipe Laporan</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="detail">Detail (Per Record)</option>
                            <option value="simple">Simple (Per Batch/Hari)</option>
                        </select>
                        <div class="form-text">
                            <small class="text-muted">
                                <strong>Detail:</strong> Menampilkan setiap record pemakaian supply<br>
                                <strong>Simple:</strong> Data diagregasi per batch per hari
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" id="showButton">
                            <i class="fas fa-search me-2"></i>Tampilkan
                        </button>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <button type="button" class="btn btn-success w-100" id="exportExcelButton" disabled>
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-danger w-100" id="exportPdfButton" disabled>
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-info w-100" id="exportCsvButton" disabled>
                            <i class="fas fa-file-csv me-2"></i>Export CSV
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="reset" class="btn btn-secondary w-100" id="resetButton">
                            <i class="fas fa-redo me-2"></i>Reset
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
            const livestockData = @json($ternak ?? []);
            console.table(livestockData);

            // Initialize select2 for dropdowns
            $('#farm, #coop, #livestock, #supply').select2({
                placeholder: 'Pilih...',
                allowClear: true
            });

            // Disable select elements initially
            const coopSelect = document.getElementById('coop');
            const livestockSelect = document.getElementById('livestock');
            const showButton = document.getElementById('showButton');

            coopSelect.disabled = true;
            livestockSelect.disabled = true;

            // Handle farm change
            $('#farm').on('change', function() {
                var farmId = $(this).val();
                updateCoopOptions(farmId);
                coopSelect.disabled = false;
            });

            // Handle coop change
            $('#coop').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $(this).val();
                updateLivestockOptions(farmId, coopId);
            });

            // Handle livestock change
            $('#livestock').on('change', function() {
                enableButtons();
            });

            // Handle form submission
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                loadReportIframe();
            });

            // Handle reset button
            $('#resetButton').on('click', function() {
                // Reset all select elements
                $('#farm, #coop, #livestock, #supply').val('').trigger('change');

                // Disable select elements and buttons
                $('#coop, #livestock').prop('disabled', true);
                disableButtons();

                // Clear the report content
                $('#report-content').empty();
            });

            // Handle export buttons
            $('#exportExcelButton').on('click', function() {
                exportReport('excel');
            });

            $('#exportPdfButton').on('click', function() {
                exportReport('pdf');
            });

            $('#exportCsvButton').on('click', function() {
                exportReport('csv');
            });

            function updateCoopOptions(farmId) {
                var coopSelect = $('#coop');
                coopSelect.empty().append(new Option('Pilih Kandang', ''));
                console.log(farmId);
                
                if (farmId) {
                    var farmLivestock = livestockData.filter(function(l) {
                        return l.farm_id == farmId;
                    });

                    var uniqueCoops = [];
                    farmLivestock.forEach(function(l) {
                        if (!uniqueCoops.some(k => k.id === l.coop_id)) {
                            uniqueCoops.push({
                                id: l.coop_id,
                                name: l.coop_name
                            });
                        }
                    });

                    uniqueCoops.forEach(function(coop) {
                        coopSelect.append(new Option(coop.name, coop.id));
                    });
                    coopSelect.prop('disabled', false);
                } else {
                    coopSelect.prop('disabled', true);
                }
            }

            function updateLivestockOptions(farmId, coopId) {
                var livestockSelect = $('#livestock');
                livestockSelect.empty().append(new Option('Pilih Batch', ''));
                livestockSelect.prop('disabled', true);

                if (farmId && coopId) {
                    var filteredLivestock = livestockData.filter(function(l) {
                        return l.farm_id == farmId && l.coop_id == coopId;
                    });

                    var uniqueLivestocks = filteredLivestock.map(l => ({
                        id: l.id,
                        name: l.name
                    }));

                    uniqueLivestocks.sort((a, b) => a.name.localeCompare(b.name));
                    uniqueLivestocks.forEach(function(livestock) {
                        livestockSelect.append(new Option(livestock.name, livestock.id));
                    });

                    livestockSelect.prop('disabled', false);
                }
            }

            function enableButtons() {
                $('#showButton, #exportExcelButton, #exportPdfButton, #exportCsvButton').prop('disabled', false);
            }

            function disableButtons() {
                $('#showButton, #exportExcelButton, #exportPdfButton, #exportCsvButton').prop('disabled', true);
            }

            function loadReportIframe() {
                const farmId = $('#farm').val();
                const coopId = $('#coop').val();
                const livestockId = $('#livestock').val();
                const supplyId = $('#supply').val();
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                const reportType = $('#report_type').val();

                if (!farmId || !startDate || !endDate || !reportType) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Farm, Tanggal Mulai, Tanggal Selesai, dan Tipe Laporan harus diisi',
                        icon: 'error'
                    });
                    return;
                }

                // Show loading spinner
                $('#report-content').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                // Prepare form data for POST
                const formData = {
                    farm_id: farmId,
                    coop_id: coopId,
                    livestock_id: livestockId,
                    supply_id: supplyId,
                    start_date: startDate,
                    end_date: endDate,
                    report_type: reportType,
                    export_format: 'html',
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                // Use AJAX to get the HTML, then inject into iframe
                $.ajax({
                    url: '/reports/supply-usage/export',
                    method: 'POST',
                    data: formData,
                    success: function(data) {
                        // Clear previous content
                        $('#report-content').empty();

                        // Replace the iframe creation and height logic with dynamic height adjustment
                        var iframe = $('<iframe>', {
                            id: 'report-iframe',
                            frameborder: 0,
                            scrolling: 'yes',
                            width: '100%',
                            style: 'display:block; width:100vw; min-width:100%; max-width:100%; border:0; background:#fff;'
                        }).appendTo('#report-content');

                        function resizeIframeToWindow() {
                            var $iframe = $('#report-iframe');
                            if ($iframe.length) {
                                var offset = $iframe.offset();
                                var margin = 32; // px, for spacing and print button
                                var availableHeight = window.innerHeight - offset.top - margin;
                                if (availableHeight < 300) availableHeight = 300;
                                $iframe.height(availableHeight);
                            }
                        }
                        resizeIframeToWindow();
                        $(window).off('resize.supplyReportIframe').on('resize.supplyReportIframe', resizeIframeToWindow);

                        // Set iframe content
                        var iframeDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
                        iframeDoc.open();
                        iframeDoc.write(data);
                        iframeDoc.close();

                        // Add print button below iframe
                        var printBtn = $('<button>', {
                            text: 'Print Report',
                            class: 'btn btn-primary mt-3',
                            style: 'display:block; margin: 16px 0 0 0;',
                            click: function() {
                                iframe[0].contentWindow.print();
                            }
                        }).appendTo('#report-content');
                    },
                    error: function(xhr) {
                        let errorMessage = 'Terjadi kesalahan saat memuat laporan';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        $('#report-content').html('<p class="text-danger">' + errorMessage + '</p>');
                    }
                });
            }

            function exportReport(format) {
                const farmId = $('#farm').val();
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                const reportType = $('#report_type').val();

                if (!farmId || !startDate || !endDate || !reportType) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Farm, Tanggal Mulai, Tanggal Selesai, dan Tipe Laporan harus diisi untuk export',
                        icon: 'error'
                    });
                    return;
                }

                // Show loading toast
                const loadingToast = Swal.fire({
                    title: 'Processing Export...',
                    text: `Generating ${format.toUpperCase()} file`,
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Create form and submit it
                const form = $('<form>', {
                    method: 'POST',
                    action: '/reports/supply-usage/export',
                    target: '_blank'
                });

                // Add CSRF token
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: $('meta[name="csrf-token"]').attr('content')
                }));

                // Add parameters
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'farm_id',
                    value: farmId
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'coop_id',
                    value: $('#coop').val()
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'livestock_id',
                    value: $('#livestock').val()
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'supply_id',
                    value: $('#supply').val()
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'start_date',
                    value: startDate
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'end_date',
                    value: endDate
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'report_type',
                    value: reportType
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'export_format',
                    value: format
                }));

                // Append form to body and submit
                $('body').append(form);
                form.submit();
                form.remove();

                // Close loading after a short delay
                setTimeout(() => {
                    Swal.close();
                    
                    // Show success message
                    Swal.fire({
                        title: 'Export Started!',
                        text: `${format.toUpperCase()} file is being generated and downloaded`,
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }, 1000);
            }
        });
    </script>
    @endpush
</x-default-layout>