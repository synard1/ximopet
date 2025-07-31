<x-default-layout>

    @section('title')
    Laporan Penjualan
    @endsection

    <div class="card">
        <!--begin::Card body-->
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan Penjualan</h2>

            <form id="filter-form" class="mb-5">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="farm" class="form-label">Farm</label>
                        <select class="form-select" id="farm" name="farm">
                            <option value="">Pilih Farm</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                            @endforeach
                            <!-- Add farm options dynamically -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="coop" class="form-label">Kandang</label>
                        <select class="form-select" id="coop" name="coop">
                            <option value="">Pilih Coop</option>
                            {{-- @foreach($kandangs as $kandang)
                            <option value="{{ $kandang->id }}">{{ $kandang->nama }}</option>
                            @endforeach --}}
                            <!-- Add kandang options dynamically -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="tahun" class="form-label">Tahun</label>
                        <select class="form-select" id="tahun" name="tahun">
                            <option value="">Pilih Tahun</option>
                            <!-- Add year options dynamically -->
                            @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                            <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="periode" class="form-label">Periode</label>
                        <select class="form-select" id="periode" name="periode">
                            <option value="">Pilih Periode</option>
                            <!-- Add periode options dynamically -->
                            @for ($i = 1; $i <= 12; $i++) <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                        </select>
                    </div>
                </div>

                <!-- Opsi Tambahan untuk Data Penjualan -->
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_temp_sales"
                                name="include_temp_sales" value="1">
                            <label class="form-check-label" for="include_temp_sales">
                                <i class="fas fa-clock text-warning me-2"></i>
                                Include Penjualan Temporer (RecordingSale)
                            </label>
                            <div class="form-text text-muted">
                                Menyertakan data penjualan temporer dari sistem recording untuk analisis yang lebih
                                lengkap
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_draft_sales"
                                name="include_draft_sales" value="1">
                            <label class="form-check-label" for="include_draft_sales">
                                <i class="fas fa-edit text-info me-2"></i>
                                Include Draft Sales
                            </label>
                            <div class="form-text text-muted">
                                Menyertakan data penjualan dengan status draft/pending
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Data yang Akan Ditampilkan -->
                <div class="alert alert-info mt-3" id="data-info" style="display: none;">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        Data yang akan ditampilkan:
                    </h6>
                    <ul class="mb-0" id="data-info-list">
                        <!-- Will be populated by JavaScript -->
                    </ul>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" id="saveChangesButton">
                        <i class="fas fa-filter me-2"></i>
                        Filter
                    </button>
                    <button type="reset" class="btn btn-secondary" id="resetButton">
                        <i class="fas fa-undo me-2"></i>
                        Reset
                    </button>
                    <button type="button" class="btn btn-outline-info" id="helpButton" data-bs-toggle="modal"
                        data-bs-target="#helpModal">
                        <i class="fas fa-question-circle me-2"></i>
                        Bantuan
                    </button>
                </div>
            </form>

            <div id="report-content">
                <!-- Report content will be loaded here -->
            </div>

        </div>
        <!--end::Card body-->
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">
                        <i class="fas fa-question-circle text-primary me-2"></i>
                        Panduan Laporan Penjualan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Filter Dasar:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Farm:</strong> Pilih farm yang akan dianalisis</li>
                                <li><strong>Kandang:</strong> Pilih kandang spesifik (opsional)</li>
                                <li><strong>Tahun:</strong> Periode tahun yang akan dianalisis</li>
                                <li><strong>Periode:</strong> Batch ternak spesifik</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-warning">Opsi Tambahan:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Penjualan Temporer:</strong> Data dari sistem recording yang belum final
                                </li>
                                <li><strong>Draft Sales:</strong> Penjualan dengan status draft/pending</li>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Catatan Penting:
                        </h6>
                        <ul class="mb-0">
                            <li>Data penjualan temporer mungkin belum final dan dapat berubah</li>
                            <li>Draft sales belum dikonfirmasi dan mungkin belum valid</li>
                            <li>Gunakan opsi tambahan dengan hati-hati untuk analisis yang akurat</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
        integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        $(document).ready(function() {
            var ternakData = @json($livestockForView);
            console.table(ternakData);

            // Show loading spinner
            const coopSelect = document.getElementById('coop');
            coopSelect.disabled = true;

            const tahunSelect = document.getElementById('tahun');
            tahunSelect.disabled = true;

            const periodeSelect = document.getElementById('periode');
            periodeSelect.disabled = true;

            const saveChangesButton = document.getElementById('saveChangesButton');

            // Disable the button
            saveChangesButton.disabled = true;

            // Initialize select2 for dropdowns if needed
            $('#farm, #coop, #tahun, #periode').select2();

            // Handle reset button click
            $('#resetButton').on('click', function() {
                // Reset all select elements
                $('#farm, #coop, #tahun, #periode').val('').trigger('change');

                // Reset checkboxes
                $('#include_temp_sales, #include_draft_sales').prop('checked', false);

                // Disable select elements and button
                $('#coop, #tahun, #periode').prop('disabled', true);
                $('#saveChangesButton').prop('disabled', true);

                // Clear the report content
                $('#report-content').empty();

                // Hide data info
                $('#data-info').hide();
            });

            // Handle checkbox changes to update data info
            $('#include_temp_sales, #include_draft_sales').on('change', function() {
                updateDataInfo();
            });

            // Handle farm change
            $('#farm').on('change', function() {
                var farmId = $(this).val();
                updateCoopOptions(farmId);
                coopSelect.disabled = false;
                updateDataInfo();
            });

            // Handle coop change
            $('#coop').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $(this).val();
                updateTahunOptions(farmId, coopId);
                updateDataInfo();
            });

            // Handle tahun change
            $('#tahun').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $('#coop').val();
                var tahun = $(this).val();
                updatePeriodeOptions(farmId, coopId, tahun);
                updateDataInfo();
            });

            // Handle periode change
            $('#periode').on('change', function() {
                var periodeId = $(this).val();
                saveChangesButton.disabled = false;
                updateDataInfo();
            });

            // Function to update data info display
            function updateDataInfo() {
                var farmId = $('#farm').val();
                var coopId = $('#coop').val();
                var tahun = $('#tahun').val();
                var periodeId = $('#periode').val();
                var includeTempSales = $('#include_temp_sales').is(':checked');
                var includeDraftSales = $('#include_draft_sales').is(':checked');

                var dataTypes = [];

                // Base data
                if (periodeId) {
                    dataTypes.push('<li><i class="fas fa-check text-success me-2"></i>Data penjualan final (LivestockSales)</li>');
                }

                // Additional data based on checkboxes
                if (includeTempSales) {
                    dataTypes.push('<li><i class="fas fa-clock text-warning me-2"></i>Data penjualan temporer (RecordingSale)</li>');
                }

                if (includeDraftSales) {
                    dataTypes.push('<li><i class="fas fa-edit text-info me-2"></i>Data penjualan draft/pending</li>');
                }

                if (dataTypes.length > 0) {
                    $('#data-info-list').html(dataTypes.join(''));
                    $('#data-info').show();
                } else {
                    $('#data-info').hide();
                }
            }

            // Handle form submission
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                saveChangesButton.disabled = true;
                saveChangesButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                
                var formData = $(this).serialize();
                
                // Add loading indicator to report content
                $('#report-content').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Memuat laporan penjualan...</p>
                    </div>
                `);

                $.ajax({
                    url: '/api/v2/reports/penjualan',
                    method: 'POST',
                    data: formData,
                    success: function(data) {
                        // Clear previous content
                        $('#report-content').empty();
                        
                        // Check if the response contains an error message
                        if (data.error) {
                            // Display the error message
                            $('#report-content').html(`
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    ${data.error}
                                </div>
                            `);
                        } else {
                            // Create an iframe to display the report
                            var iframe = $('<iframe>', {
                                id: 'report-iframe',
                                frameborder: 0,
                                scrolling: 'yes',
                                width: '100%',
                                height: '600px'
                            }).appendTo('#report-content');

                            // Set iframe content
                            var iframeDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
                            iframeDoc.open();
                            iframeDoc.write(data);
                            iframeDoc.close();

                            // Ensure the iframe content is fully loaded before adding buttons
                            iframe.on('load', function() {
                                // Add action buttons
                                var actionButtons = $('<div class="mt-3">').appendTo('#report-content');
                                
                                // Print button
                                var printBtn = $('<button>', {
                                    text: 'Print Report',
                                    class: 'btn btn-primary me-2',
                                    click: function() {
                                        iframe[0].contentWindow.print();
                                    }
                                }).appendTo(actionButtons);

                                // Download PDF button
                                var downloadBtn = $('<button>', {
                                    text: 'Download PDF',
                                    class: 'btn btn-success me-2',
                                    click: function() {
                                        downloadPDF();
                                    }
                                }).appendTo(actionButtons);

                                // Export Excel button
                                var exportBtn = $('<button>', {
                                    text: 'Export Excel',
                                    class: 'btn btn-info',
                                    click: function() {
                                        exportExcel();
                                    }
                                }).appendTo(actionButtons);
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading report:", error);
                        var errorMessage = "Error loading report. Please try again.";
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        $('#report-content').html(`
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${errorMessage}
                            </div>
                        `);
                    },
                    complete: function() {
                        // Reset button state
                        saveChangesButton.disabled = false;
                        saveChangesButton.innerHTML = '<i class="fas fa-filter me-2"></i>Filter';
                    }
                });
            });

            // Function to download PDF
            function downloadPDF() {
                var element = document.getElementById('report-iframe');
                var opt = {
                    margin: 1,
                    filename: 'laporan_penjualan.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(element).save();
            }

            // Function to export Excel (placeholder)
            function exportExcel() {
                alert('Export Excel functionality will be implemented soon.');
            }

            // Function to update kandang options based on selected farm
            function updateCoopOptions(farmId) {
                var coopSelect = $('#coop');
                coopSelect.empty().append(new Option('Pilih Coop', ''));
                
                if (farmId) {
                    var farmTernak = ternakData.filter(function(ternak) {
                        return ternak.farm_id == farmId;
                    });

                    var uniqueCoops = [];
                    farmTernak.forEach(function(ternak) {
                        if (!uniqueCoops.some(k => k.id === ternak.coop_id)) {
                            uniqueCoops.push({
                                id: ternak.coop_id,
                                name: ternak.coop_name
                            });
                        }
                    });

                    uniqueCoops.forEach(function(coop) {
                        coopSelect.append(new Option(coop.name, coop.id));
                    });
                }
            }

            function updateTahunOptions(farmId, coopId) {
                var tahunSelect = $('#tahun');
                tahunSelect.empty().append(new Option('Pilih Tahun', ''));
                tahunSelect.prop('disabled', true);

                if (farmId && coopId) {
                    var filteredTernak = ternakData.filter(function(ternak) {
                        return ternak.farm_id == farmId && ternak.coop_id == coopId;
                    });

                    var uniqueYears = [...new Set(filteredTernak.map(ternak => new Date(ternak.start_date).getFullYear()))];
                    uniqueYears.sort((a, b) => b - a); // Sort years in descending order

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
                    var filteredTernak = ternakData.filter(function(ternak) {
                        return ternak.farm_id == farmId && 
                               ternak.coop_id == coopId && 
                               new Date(ternak.start_date).getFullYear() == tahun;
                    });

                    var uniquePeriodes = filteredTernak.map(ternak => ({
                        id: ternak.id,
                        name: ternak.name
                    }));

                    uniquePeriodes.sort((a, b) => a.name.localeCompare(b.name));
                    uniquePeriodes.forEach(function(periode) {
                        periodeSelect.append(new Option(periode.name, periode.id));
                    });

                    periodeSelect.prop('disabled', false);
                }
            }

            // Initialize data info on page load
            updateDataInfo();
        });
    </script>
    @endpush
</x-default-layout>