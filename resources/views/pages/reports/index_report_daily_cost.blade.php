<x-default-layout>
    @section('title')
    Laporan Biaya Harian
    @endsection

    <div class="card">
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan Biaya Harian</h2>

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
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Tampilkan
                    </button>
                    <button type="button" class="btn btn-success ms-2" id="exportButton">
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

            function loadReport() {
                const farm = $('#farm').val();
                const tanggal = $('#tanggal').val();

                if (!farm || !tanggal) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Farm dan Tanggal harus diisi',
                        icon: 'error'
                    });
                    return;
                }

                // Show loading spinner
                $('#report-content').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                $.ajax({
                    url: '/api/v2/reports/daily-cost',
                    method: 'POST',
                    data: {
                        farm: farm,
                        tanggal: tanggal
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
                const tanggal = $('#tanggal').val();

                // Create form and submit it
                const form = $('<form>', {
                    method: 'POST',
                    action: '/reports/harian/export',
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

                // Append form to body and submit
                $('body').append(form);
                form.submit();
                form.remove();
            }
        });
    </script>
    @endpush
</x-default-layout>