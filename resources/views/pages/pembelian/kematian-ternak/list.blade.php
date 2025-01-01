<x-default-layout>

    @section('title')
        Data Kematian Ternak
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card">

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    <livewire:transaksi.kematian-ternak />

    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            // Function to fetch staff data and populate the dropdown
            var fetchFarmData = function (e) {
                // Replace this URL with your actual API endpoint
                const apiUrl = '/api/v1/farms';

                // Show loading spinner
                const farmSelect = document.getElementById('farmSelect');
                farmSelect.innerHTML = '<option>Loading...</option>';
                farmSelect.disabled = true;

                $.ajax({
                    url: apiUrl,
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        roles: 'Operator',
                        type: 'list'
                    },
                    success: function(data) {                       
                        if (data.farms && data.farms.length > 0) {
                            // Clear loading spinner
                            farmSelect.innerHTML = '';
                            farmSelect.disabled = false;

                            const defaultOption = new Option("=== Pilih Farm ===", "", true, true);
                            farmSelect.append(defaultOption);

                            data.farms.forEach(farm => {
                                const option = new Option(farm.nama, farm.id);
                                option.setAttribute('data-farm-id', farm.id);
                                farmSelect.append(option);
                            });

                            $(farmSelect).trigger('change');
                            $('#kt_modal_kternak').modal('show');
                        } else {
                            $('#noFarm_modal').modal('show');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        // Clear loading spinner on error
                        farmSelect.innerHTML = '<option>Error loading farms</option>';
                        farmSelect.disabled = false;
                    }
                });
            }

            var fetchKandangData = function (e) {
                // Replace this URL with your actual API endpoint
                const apiUrl = '/api/v1/kandangs';

                $.ajax({
                    url: apiUrl,
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        farm_id: farmId,
                        roles: 'Operator',
                        type: 'list'
                    },
                    success: function(data) {    
                        if (data.kandangs && data.kandangs.length > 0) {
                            // Select the farm dropdown
                            const kandangSelect = document.getElementById('kandangSelect');
                            kandangSelect.innerHTML = ''; // Clear existing options
                            kandangSelect.disabled = false; // Remove disabled attribute

                            let minDat = data.oldestDate;
                            flatpickr("#tanggal", {
                                    minDate: minDat,
                                });

                            document.getElementById('tanggal').disabled = false; // Enable tanggal input 

                            const defaultOption = new Option("=== Pilih Kandang ===", "", true, true);
                            kandangSelect.append(defaultOption);

                            data.kandangs.forEach(kandang => {
                                const option = new Option(kandang.nama, kandang.id);
                                option.setAttribute('data-kandang-id', kandang.id);
                                kandangSelect.append(option);
                            });

                            $(kandangSelect).trigger('change');
                            // $('#kt_modal_kternak').modal('show');
                        } else {
                            $('#noFarm_modal').modal('show');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                    }
                });
            }
        </script>
    @endpush
</x-default-layout>

