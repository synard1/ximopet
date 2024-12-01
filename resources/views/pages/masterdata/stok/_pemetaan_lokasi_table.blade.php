    <div class="card card-flush">
        <!--begin::Card header-->
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                    <input type="text" data-kt-ecommerce-product-filter="search" class="form-control form-control-solid w-250px ps-12" placeholder="Search Product">
                </div>
                <!--end::Search-->
            </div>
            <!--end::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <!--begin::Add product-->
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDataModal">
                    Tambah Data
                </a>
                <!--end::Add product-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <div class="table-responsive">
                <table id="itemLocationMappingTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Farm Name</th>
                            <th>Location Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    <!--begin::Modal-->
    <div class="modal fade" id="addDataModal" tabindex="-1" aria-labelledby="addDataModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDataModalLabel">Tambah Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addDataForm">
                        <div class="mb-3">
                            <label for="farm_select" class="form-label">Farm</label>
                            <select class="form-control" id="farm_select" name="farm_select" required>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="item_select" class="form-label">Item</label>
                            <select class="form-control" id="item_select" name="item_select" required disabled>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="location_select" class="form-label">Location</label>
                            <select class="form-control" id="location_select" name="location_select" required disabled>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal-->

    @push('scripts')
        <script>
            $(document).ready(function() {
                document.getElementById('mySearchInput').addEventListener('keyup', function () {
                    $('#itemLocationMappingTable').DataTable().search(this.value).draw();
                });

                document.addEventListener('livewire:init', function () {
                    Livewire.on('success', function () {
                        $('#addDataModal').modal('hide');
                        $('#itemLocationMappingTable').DataTable().ajax.reload();
                    });
                });

                $('#addDataForm').on('submit', function(e) {
                    e.preventDefault();
                    var url = $(this).attr('action') || '/api/v2/data/farms/items_mapping';
                    var method = $(this).attr('method') || 'POST';
                    
                    // Add your AJAX call here to submit the form data
                    // Example:
                    $.ajax({
                        url: url,
                        type: method,
                        data: $(this).serialize() + (method === 'POST' ? '&task=ADD' : ''),
                        headers: {
                            'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            $('#addDataModal').modal('hide');
                            toastr.success(response.message);
                            $('#itemLocationMappingTable').DataTable().ajax.reload();

                            // Reset form fields
                            $('#addDataForm')[0].reset();
                            
                            // Reset select fields to their initial state
                            $('#farm_select').val('').trigger('change');
                            $('#item_select').val('').prop('disabled', true);
                            $('#location_select').val('').prop('disabled', true);

                            // Reset form action and method
                            $('#addDataForm').attr('action', '');
                            $('#addDataForm').attr('method', 'POST');
                        },
                            error: function(xhr, status, error) {
                                console.error('Error submitting form:', error);
                                toastr.error('Failed to submit form');
                            }
                    });
                });
            });

            document.getElementById('farm_select').addEventListener('change', function() {
                const selectedFarmId = this.value;
                if (selectedFarmId) {
                    // Fetch items not in the selected location
                    axios.post('/api/v2/data/farms/items_mapping', {
                        farm_id: selectedFarmId
                    }, {
                        headers: {
                            'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    })
                    .then(function(response) {
                        const items = response.data;
                        // Update the item select options
                        const itemSelect = document.getElementById('item_select');
                        itemSelect.innerHTML = ''; // Clear existing options

                        itemSelect.disabled = false;

                        const defaultOption = new Option("=== Pilih Item ===", "", true, true);
                        itemSelect.append(defaultOption);

                        items.forEach(function(item) {
                            const option = document.createElement('option');
                            option.value = item.item_id;
                            option.textContent = item.item_name;
                            itemSelect.appendChild(option);
                        });
                    })
                    .catch(function(error) {
                        console.error('Error fetching items not in location:', error);
                    });

                    fetchFarmsData({ task: 'GET', mode: 'LIST', submodul:'location', farm_id: selectedFarmId }, function(location) {
                    // Process the itemsData here, e.g., display it in a table
                    // console.table(location);
                    // Additional logic for this particular page/component
                    const items = location;
                    // Update the item select options
                    const locationSelect = document.getElementById('location_select');
                    locationSelect.innerHTML = ''; // Clear existing options

                    locationSelect.disabled = false;

                    const defaultOption = new Option("=== Pilih Lokasi ===", "", true, true);
                    locationSelect.append(defaultOption);

                    items.forEach(function(item) {
                        const option = document.createElement('option');
                        option.value = item.storage_id;
                        option.textContent = item.nama;
                        locationSelect.appendChild(option);
                    });
                });
                }
            });

            

            function getItemsLocation() {
                const task = 'GET';
                const mode = 'TABLE';

                new DataTable('#itemLocationMappingTable', {
                    ajax: {
                        url: '/api/v2/data/items/location',
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            task: task,
                            mode: mode,
                        }
                    },
                    columns: [
                        { data: '#', render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }},
                        { data: 'item_name' },
                        { data: 'farm_name' },
                        { data: 'location_name' },
                        {
                            data: null,
                            render: function(data, type, row) {
                                return `
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Delete</button>
                                `;
                            }
                        },
                    ],
                    error: function (xhr, error, thrown) {
                        if (xhr.status === 401) {
                            window.location.href = '/login';
                        }
                    }
                });
            }
        </script>
    @endpush
