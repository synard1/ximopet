<x-default-layout>

    @section('title')
    Laporan Performa
    @endsection

    <div class="card">
        <!--begin::Card body-->
        <div class="card-body py-4">
            <h2 class="mb-4">Filter Laporan Performa </h2>

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
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="coop" class="form-label">Kandang</label>
                        <select class="form-select" id="coop" name="coop">
                            <option value="">Pilih Kandang</option>
                            @foreach($coops as $coop)
                            <option value="{{ $coop->id }}">{{ $coop->name }}</option>
                            @endforeach
                            <!-- Add coop options dynamically -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="periode" class="form-label">Periode</label>
                        <select class="form-select" id="periode" name="periode">
                            <option value="">Pilih Periode</option>
                            <!-- Add periode options dynamically -->
                            {{-- @for ($i = 1; $i <= 12; $i++) <option value="{{ $i }}">{{ $i }}</option>
                                @endfor --}}
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
        integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        $(document).ready(function() {
            var ternakData = @json($ternak);
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
                $('#farm, #coop, #tahun, #periode', '#tanggal_surat').val('').trigger('change');

                // Disable select elements and button
                $('#coop, #tahun, #periode').prop('disabled', true);
                $('#saveChangesButton').prop('disabled', true);

                // Clear the report content
                $('#report-content').empty();
            });

            // Load farms dynamically
            // $.ajax({
            //     url: '/api/farms', // Replace with your API endpoint
            //     method: 'GET',
            //     success: function(data) {
            //         var farmSelect = $('#farm');
            //         $.each(data, function(index, farm) {
            //             farmSelect.append(new Option(farm.name, farm.id));
            //         });
            //     }
            // });

            // Handle farm change
            $('#farm').on('change', function() {
                var farmId = $(this).val();
                updateCoopOptions(farmId);
                coopSelect.disabled = false;

            });

            // Handle farm change
            $('#coop').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $(this).val();
                updateTahunOptions(farmId, coopId);
                // tahunsele.disabled = false;

            });

            // Handle tahun change
            $('#tahun').on('change', function() {
                var farmId = $('#farm').val();
                var coopId = $('#coop').val();
                var tahun = $(this).val();

                updatePeriodeOptions(farmId, coopId, tahun);


                // console.log('farm ' + farmId + ', coop'+ coopId + ', tahun'+ tahun);
                

                // You can add logic here to handle the year change
                // For example, update the periode options based on the selected year, farm, and coop
            });

            // Handle tahun change
            $('#periode').on('change', function() {
                var periodeId = $(this).val();
                saveChangesButton.disabled = false;


                // updatePeriodeOptions(farmId, kandangId, tahun);
            });

            // Handle tahun change
            // $('#tahun').on('change', function() {
            //     var farmId = $('#farm').val();
            //     var coopId = $('#coop').val();
            //     var tahun = $(this).val();

            //     console.log('farm ' + farmId + ', coop'+ coopId + ', tahun'+ tahun);
                

            //     // You can add logic here to handle the year change
            //     // For example, update the periode options based on the selected year, farm, and coop
            // });

            

            // Handle form submission
            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    url: '/api/v2/reports/performa', // Replace with your API endpoint
                    method: 'POST',
                    data: formData,
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

                            // Add download PDF button
                            // var downloadBtn = $('<button>', {
                            //     text: 'Download PDF',
                            //     class: 'btn btn-secondary mt-3',
                            //     click: function() {
                            //         var element = iframe[0].contentDocument.body;
                            //         var opt = {
                            //             margin:       1,
                            //             filename:     'report.pdf',
                            //             image:        { type: 'jpeg', quality: 0.98 },
                            //             html2canvas:  { scale: 2 },
                            //             jsPDF:        { unit: 'in', format: 'A4', orientation: 'portrait' }
                            //         };
                            //         html2pdf().set(opt).from(element).save();
                            //     }
                            // }).appendTo('#report-content');
                        });
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



            // Function to update coop options based on selected farm
            function updateCoopOptions(farmId) {
                var coopSelect = $('#coop');
                coopSelect.empty().append(new Option('Pilih Coop', ''));
                
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

                    uniqueKandangs.forEach(function(coop) {
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

            // New function to handle periode selection
            $('#periode').on('change', function() {
                var selectedPeriodeId = $(this).val();
                var farmId = $('#farm').val();
                var coopId = $('#coop').val();
                var tahun = $('#tahun').val();

                var selectedTernak = ternakData.find(function(ternak) {
                    return ternak.farm_id == farmId && 
                        ternak.coop_id == coopId && 
                        new Date(ternak.start_date).getFullYear() == tahun &&
                        ternak.id == selectedPeriodeId;
                });

                if (selectedTernak) {
                    var tanggalSurat = selectedTernak.tanggal_surat;
                    $('#tanggal_surat').val(tanggalSurat);
                    console.log('Tanggal Surat:', tanggalSurat);
                } else {
                    $('#tanggal_surat').val('');
                    console.log('No matching ternak found');
                }
            });

        });

        

        // Load kandangs based on selected farm
        // $('#farm').on('change', function() {
        //         var farmId = $(this).val();
        //         console.log(farmId);
                
        //         if (farmId) {
        //             $.ajax({
        //                 url: '/api/kandangs/' + farmId, // Replace with your API endpoint
        //                 method: 'GET',
        //                 success: function(data) {
        //                     var kandangSelect = $('#kandang');
        //                     kandangSelect.empty().append(new Option('Pilih Kandang', ''));
        //                     $.each(data, function(index, kandang) {
        //                         kandangSelect.append(new Option(kandang.name, kandang.id));
        //                     });
        //                 }
        //             });
        //         }
        //     });
    </script>
    @endpush
</x-default-layout>