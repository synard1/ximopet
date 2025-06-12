<x-default-layout>
    @section('title')
    Laporan Harian
    @endsection

    <div class="card">
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan Harian</h2>

            <form id="filter-form" class="mb-5">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="farm" class="form-label required">Farm</label>
                        <select class="form-select" id="farm" name="farm" required>
                            <option value="">Pilih Farm</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="tanggal" class="form-label required">Tanggal</label>
                        <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ date('Y-m-d') }}"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label for="report_type" class="form-label required">Tipe Laporan</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="simple">Simple (Per Kandang)</option>
                            <option value="detail">Detail (Per Batch)</option>
                        </select>
                        <div class="form-text">
                            <small class="text-muted">
                                <strong>Simple:</strong> Data diagregasi per kandang<br>
                                <strong>Detail:</strong> Data ditampilkan per batch dalam setiap kandang
                            </small>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Tampilkan
                    </button>

                    <!-- Export Dropdown -->
                    <div class="btn-group ms-2" role="group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">
                                    <i class="fas fa-file-excel me-2 text-success"></i>Excel (.xlsx)
                                </a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">
                                    <i class="fas fa-file-pdf me-2 text-danger"></i>PDF
                                </a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportReport('csv')">
                                    <i class="fas fa-file-csv me-2 text-info"></i>CSV
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#" onclick="exportReport('html')">
                                    <i class="fas fa-globe me-2 text-primary"></i>HTML (View)
                                </a></li>
                        </ul>
                    </div>

                    <button type="reset" class="btn btn-secondary ms-2" id="resetButton">
                        <i class="fas fa-redo me-2"></i>Reset
                    </button>

                    <button type="button" class="btn btn-info ms-2" id="previewButton">
                        <i class="fas fa-eye me-2"></i>Preview
                    </button>

                    <button type="button" class="btn btn-warning ms-2" onclick="bulkExport()">
                        <i class="fas fa-download me-2"></i>Bulk Export
                    </button>

                    <button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="collapse"
                        data-bs-target="#advancedOptions">
                        <i class="fas fa-cog me-2"></i>Advanced
                    </button>
                </div>
            </form>

            <!-- Advanced Options -->
            <div class="collapse mt-3" id="advancedOptions">
                <div class="card card-body">
                    <h6 class="mb-3">Advanced Export Options</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Include Charts</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeCharts" checked>
                                <label class="form-check-label" for="includeCharts">
                                    Include performance charts in export
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date Range Export</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="dateRangeExport">
                                <label class="form-check-label" for="dateRangeExport">
                                    Export multiple dates (coming soon)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Auto Schedule</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="autoSchedule">
                                <label class="form-check-label" for="autoSchedule">
                                    Schedule daily auto-export (coming soon)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Advanced features are being developed. Current export supports single date with multiple
                                formats.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Statistics Summary -->
            <div class="row mt-4" id="reportStats" style="display: none;">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">Total Kandang</h5>
                            <h3 id="totalCoops">-</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">Total Stock</h5>
                            <h3 id="totalStock">-</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">Total Deplesi</h5>
                            <h3 id="totalDeplesi">-</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5 class="card-title">Survival Rate</h5>
                            <h3 id="survivalRate">-</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div id="report-content">
                <!-- Report content will be loaded here -->
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize select2
            $('#farm').select2({
                placeholder: 'Pilih Farm',
                allowClear: true
            });

            // Handle form submission
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                loadReport();
            });

            // Handle reset button
            $('#resetButton').on('click', function() {
                $('#farm').val('').trigger('change');
                $('#tanggal').val('{{ date('Y-m-d') }}');
                $('#report_type').val('simple');
                $('#report-content').empty();
            });

            // Handle preview button
            $('#previewButton').on('click', function() {
                if (!validateForm()) return;
                loadReport();
            });

            // Add validation function
            function validateForm() {
                const farm = $('#farm').val();
                const tanggal = $('#tanggal').val();
                const reportType = $('#report_type').val();

                if (!farm || !tanggal || !reportType) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Silahkan lengkapi semua field terlebih dahulu',
                        icon: 'error'
                    });
                    return false;
                }
                return true;
            }

            function loadReport() {
                const farm = $('#farm').val();
                const tanggal = $('#tanggal').val();
                const reportType = $('#report_type').val();

                if (!farm || !tanggal || !reportType) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Farm, Tanggal, dan Tipe Laporan harus diisi',
                        icon: 'error'
                    });
                    return;
                }

                // Show loading spinner
                $('#report-content').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                $.ajax({
                    url: '/api/v2/reports/harian',
                    method: 'POST',
                    data: {
                        farm: farm,
                        tanggal: tanggal,
                        report_type: reportType
                    },
                    success: function(response) {
                        $('#report-content').html(response);
                        $('#reportStats').show();
                        updateReportStats();
                    },
                    error: function(xhr) {
                        let errorMessage = 'Terjadi kesalahan saat memuat laporan';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error'
                        });
                        
                        $('#report-content').empty();
                    }
                });
            }

            // Global export function
            window.exportReport = function(format) {
                if (!validateForm()) return;

                const farm = $('#farm').val();
                const tanggal = $('#tanggal').val();
                const reportType = $('#report_type').val();

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

                if (format === 'html') {
                    // For HTML, open in new tab
                    const url = `/report/daily/export?farm=${farm}&tanggal=${tanggal}&report_type=${reportType}&export_format=html`;
                    window.open(url, '_blank');
                    Swal.close();
                    return;
                }

                // Create form and submit it for file downloads
                const form = $('<form>', {
                    method: 'POST',
                    action: '/report/daily/export',
                    target: format === 'pdf' ? '_blank' : '_self' // PDF opens in new tab
                });

                // Add CSRF token
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: '{{ csrf_token() }}'
                }));

                // Add parameters
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'farm',
                    value: farm
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'tanggal',
                    value: tanggal
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

            // Bulk export function
            window.bulkExport = function() {
                if (!validateForm()) return;

                Swal.fire({
                    title: 'Bulk Export',
                    text: 'Export laporan dalam semua format sekaligus?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Export Semua',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formats = ['excel', 'pdf', 'csv'];
                        let completed = 0;
                        
                        const progressSwal = Swal.fire({
                            title: 'Bulk Export Progress',
                            html: `<div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <p>Preparing exports...</p>`,
                            allowOutsideClick: false,
                            showConfirmButton: false
                        });

                        formats.forEach((format, index) => {
                            setTimeout(() => {
                                exportReport(format);
                                completed++;
                                const progress = (completed / formats.length) * 100;
                                
                                Swal.update({
                                    html: `<div class="progress mb-3">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: ${progress}%"></div>
                                    </div>
                                    <p>Exported: ${format.toUpperCase()} (${completed}/${formats.length})</p>`
                                });

                                if (completed === formats.length) {
                                    setTimeout(() => {
                                        Swal.fire({
                                            title: 'Bulk Export Complete!',
                                            text: 'Semua format telah diexport',
                                            icon: 'success',
                                            timer: 3000
                                        });
                                    }, 1000);
                                }
                            }, index * 1500); // Stagger exports
                        });
                                         }
                 });
             }

             // Update report statistics from the loaded content
             function updateReportStats() {
                 try {
                     // Count coops from table rows
                     const coopRows = $('#report-content table tbody tr').length;
                     $('#totalCoops').text(coopRows || 0);

                     // Extract totals from the total row
                     const totalRow = $('#report-content table tfoot tr');
                     if (totalRow.length > 0) {
                         const cells = totalRow.find('td');
                         if (cells.length >= 10) {
                             const stockAwal = parseInt(cells.eq(2).text().replace(/,/g, '')) || 0;
                             const totalDeplesi = parseInt(cells.eq(5).text().replace(/,/g, '')) || 0;
                             const stockAkhir = parseInt(cells.eq(9).text().replace(/,/g, '')) || 0;
                             
                             $('#totalStock').text(stockAwal.toLocaleString());
                             $('#totalDeplesi').text(totalDeplesi.toLocaleString());
                             
                             const survivalRate = stockAwal > 0 ? ((stockAkhir / stockAwal) * 100).toFixed(1) : 0;
                             $('#survivalRate').text(survivalRate + '%');
                         }
                     }
                 } catch (e) {
                     console.log('Error updating stats:', e);
                     // Set default values on error
                     $('#totalCoops').text('-');
                     $('#totalStock').text('-');
                     $('#totalDeplesi').text('-');
                     $('#survivalRate').text('-');
                 }
             }
         });
    </script>
    @endpush
</x-default-layout>