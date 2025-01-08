<x-default-layout>

    @section('title')
        Laporan Inventory
    @endsection

    <div class="card">
        <!--begin::Card body-->
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan Iventory</h2>
            
            <form id="filter-form" class="mb-5">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="farm" class="form-label">Farm</label>
                        <select class="form-select" id="farm" name="farm">
                            <option value="">Pilih Farm</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->nama }}</option>
                            @endforeach
                            <!-- Add farm options dynamically -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="tahun" class="form-label">Jenis</label>
                        <select class="form-select" id="jenis" name="jenis">
                            <option value="">Pilih Jenis</option>
                            <option value="Masuk">Masuk</option>
                            <option value="Keluar">Keluar</option>
                            <option value="Mutasi">Mutasi</option>
                            <option value="Semua">Semua</option>
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
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" id="saveChangesButton">Filter</button>
                    <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                </div>
            </form>

            <div id="report-content">
                <!-- Report content will be loaded here -->
            </div>

        </div>
        <!--end::Card body-->
    </div>

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        $(document).ready(function() {
            var ternakData = @json($ternak);
            console.table(ternakData);

            const tahunSelect = document.getElementById('tahun');
            tahunSelect.disabled = true;

            const jenisSelect = document.getElementById('jenis');
            jenisSelect.disabled = true;

            const saveChangesButton = document.getElementById('saveChangesButton');

            // Disable the button
            saveChangesButton.disabled = true;

            // Initialize select2 for dropdowns if needed
            $('#farm, #tahun').select2();

            // Handle reset button click
            $('#resetButton').on('click', function() {
                // Reset all select elements
                $('#farm, #tahun').val('').trigger('change');

                // Disable select elements and button
                $('#tahun').prop('disabled', true);
                $('#saveChangesButton').prop('disabled', true);

                // Clear the report content
                $('#report-content').empty();
            });

            // Handle farm change
            $('#farm').on('change', function() {
                var farmId = $(this).val();                
                jenisSelect.disabled = false;

            });

            // Handle jenis change
            $('#jenis').on('change', function() {
                var farmId = $('#farm').val();
                updateTahunOptions(farmId);
                tahunSelect.disabled = false;

            });

            // Handle tahun change
            $('#tahun').on('change', function() {
                var farmId = $('#farm').val();
                var tahun = $(this).val();
            });


            // Handle form submission
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: '/api/v2/reports/penjualan', // Replace with your API endpoint
                    method: 'POST',
                    data: formData,
                    success: function(data) {
                        // Clear previous content
                        $('#report-content').empty();
                        
                        // Check if the response contains an error message
                        if (data.error) {
                            // Display the error message
                            $('#report-content').html('<div class="alert alert-danger" role="alert">' + data.error + '</div>');
                        } else {
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

                                // Add download PDF button if needed
                                // ...
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading report:", error);
                        var errorMessage = "Error loading report. Please try again.";
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        $('#report-content').html('<p class="text-danger">' + errorMessage + '</p>');
                    }

                });
            });

            function updateTahunOptions(farmId) {
                var tahunSelect = $('#tahun');
                tahunSelect.empty().append(new Option('Pilih Tahun', ''));
                tahunSelect.prop('disabled', true);

                if (farmId) {
                    var filteredTernak = ternakData.filter(function(ternak) {
                        return ternak.farm_id == farmId;
                    });

                    var uniqueYears = [...new Set(filteredTernak.map(ternak => new Date(ternak.start_date).getFullYear()))];
                    uniqueYears.sort((a, b) => b - a); // Sort years in descending order

                    uniqueYears.forEach(function(year) {
                        tahunSelect.append(new Option(year, year));
                    });

                    tahunSelect.prop('disabled', false);
                }
            }

        });
    </script>
    @endpush
</x-default-layout>