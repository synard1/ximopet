<x-default-layout>
    @section('title')
    Laporan Batch Pekerja
    @endsection

    <div class="card">
        <!--begin::Card body-->
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan</h2>

            <form id="filter-form" class="mb-5">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="farm" class="form-label required">Farm</label>
                        <select class="form-select" id="farm" name="farm" required>
                            <option value="">Pilih Farm</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="coop" class="form-label required">Kandang</label>
                        <select class="form-select" id="coop" name="coop" required disabled>
                            <option value="">Pilih Kandang</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="tahun" class="form-label required">Tahun</label>
                        <select class="form-select" id="tahun" name="tahun" required disabled>
                            <option value="">Pilih Tahun</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="periode" class="form-label required">Periode (Batch)</label>
                        <select class="form-select" id="periode" name="periode" required disabled>
                            <option value="">Pilih Periode</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="report_type" class="form-label required">Jenis Laporan</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="detail">Detail</option>
                            <option value="simple">Simple</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" id="showButton" disabled>
                        <i class="fas fa-search me-2"></i>Tampilkan
                    </button>
                    <button type="button" class="btn btn-success ms-2" id="exportButton" disabled>
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </button>
                    <button type="reset" class="btn btn-secondary" id="resetButton">
                        <i class="fas fa-redo me-2"></i>Reset
                    </button>
                </div>
            </form>

            <div id="report-content">
                <!-- Report content will be loaded here -->
            </div>
        </div>
        <!--end::Card body-->
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            const ternakData = @json($livestock);
            console.table(ternakData);

            // Initialize select2 for dropdowns
            $('#farm, #kandang, #tahun, #periode').select2({
                placeholder: 'Pilih...',
                allowClear: true
            });

            // Disable select elements initially
            const coopSelect = document.getElementById('coop');
            const tahunSelect = document.getElementById('tahun');
            const periodeSelect = document.getElementById('periode');
            const saveChangesButton = document.getElementById('showButton');

            coopSelect.disabled = true;
            tahunSelect.disabled = true;
            periodeSelect.disabled = true;
            saveChangesButton.disabled = true;

            // Handle farm change
            $('#farm').on('change', function() {
                var farmId = $(this).val();
                updateCoopOptions(farmId);
                coopSelect.disabled = false;
            });

            // Handle kandang change
            $('#coop').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $(this).val();
                updateTahunOptions(farmId, coopId);
            });

            // Handle tahun change
            $('#tahun').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $('#coop').val();
                var tahun = $(this).val();
                updatePeriodeOptions(farmId, coopId, tahun);
            });

            // Handle periode change
            $('#periode').on('change', function() {
                saveChangesButton.disabled = false;
            });

            // Handle form submission
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                loadReport();
            });

            // Handle reset button
            $('#resetButton').on('click', function() {
                // Reset all select elements
                $('#farm, #coop, #tahun, #periode').val('').trigger('change');

                // Disable select elements and button
                $('#coop, #tahun, #periode').prop('disabled', true);
                $('#showButton').prop('disabled', true);

                // Clear the report content
                $('#report-content').empty();
            });

            // Handle export button
            $('#exportButton').on('click', function() {
                if (!$('#farm').val()) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Silahkan pilih Farm terlebih dahulu',
                        icon: 'error'
                    });
                    return;
                }
                exportReport();
            });

            function updateCoopOptions(farmId) {
                var coopSelect = $('#coop');
                coopSelect.empty().append(new Option('Pilih Kandang', ''));

                if (farmId) {
                    var farmTernak = ternakData.filter(function(ternak) {
                        return ternak.farm_id == farmId;
                    });

                    var uniqueKandangs = [];
                    farmTernak.forEach(function(ternak) {
                        if (!uniqueKandangs.some(k => k.id === ternak.coop_id)) {
                            uniqueKandangs.push({
                                id: ternak.coop_id,
                                name: ternak.coop_name
                            });
                        }
                    });

                    uniqueKandangs.forEach(function(kandang) {
                        coopSelect.append(new Option(kandang.name, kandang.id));
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

            function loadReport() {
                const farm = $('#farm').val();
                const coop = $('#coop').val();
                const tahun = $('#tahun').val();
                const periode = $('#periode').val();
                const reportType = $('#report_type').val();

                if (!farm || !coop || !tahun || !periode || !reportType) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Semua field harus diisi',
                        icon: 'error'
                    });
                    return;
                }

                // Show loading spinner
                $('#report-content').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                $.ajax({
                    url: '/api/v2/reports/batch-worker',
                    method: 'POST',
                    data: {
                        farm: farm,
                        coop: coop,
                        tahun: tahun,
                        periode: periode,
                        tanggal: tanggal,
                        report_type: reportType
                    },
                    success: function(response) {
                        $('#report-content').html(response);
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

            function exportReport() {
                const farm = $('#farm').val();
                const coop = $('#coop').val();
                const tahun = $('#tahun').val();
                const periode = $('#periode').val();
                const reportType = $('#report_type').val();

                if (!farm || !coop || !tahun || !periode || !reportType) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Semua field harus diisi untuk export',
                        icon: 'error'
                    });
                    return;
                }

                // Create form and submit it
                const form = $('<form>', {
                    method: 'POST',
                    action: '/reports/livestock-cost/export',
                    target: '_blank' // Open in a new tab/window
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
                    name: 'coop',
                    value: coop
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'tahun',
                    value: tahun
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'periode',
                    value: periode
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'report_type',
                    value: reportType
                }));

                // Append form to body and submit
                $('body').append(form);
                form.submit();
                form.remove();
            }
        });
    </script>
    @endpush
</x-default-layout>