<x-default-layout>

    @section('title')
        Master Data OVK
    @endsection

    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#kt_tab_pane_4">Data OVK</a>
        </li>
        @if(auth()->user()->hasRole(['Supervisor']))
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#kt_tab_pane_5">Pemetaan Lokasi</a>
            </li>
        @endif
    </ul>
    
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="kt_tab_pane_4" role="tabpanel">
            @include('pages.masterdata.stok._table')
        </div>

        @if(auth()->user()->hasRole(['Supervisor']))
            <div class="tab-pane fade" id="kt_tab_pane_5" role="tabpanel">
                @include('pages.masterdata.stok._pemetaan_lokasi_table')
            </div>
        @endif
    </div>

    @include('pages.masterdata.stok._modal_stok_details')


    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            let farmsData;
            $(document).ready(function () {
                $('.nav-link').on('shown.bs.tab', function(e) {
                    if (e.target.href.includes('#kt_tab_pane_4')) {
                        var table = new DataTable('#operatorsTable');
                        table.destroy();
                        getOperators();
                        document.getElementById('mySearchInput2').addEventListener('keyup', function () {
                            $('#operatorsTable').DataTable().search(this.value).draw();
                        });
                    } else if (e.target.href.includes('#kt_tab_pane_5')) {
                        var table = new DataTable('#itemLocationMappingTable');
                        table.destroy();

                        // Initialize DataTable
                        getItemsLocation();

                        // Edit button click handler
                        $('#itemLocationMappingTable').on('click', '.edit-btn', function() {
                            var id = $(this).data('id');
                            editItemLocation(id);
                        });

                        // Delete button click handler
                        $('#itemLocationMappingTable').on('click', '.delete-btn', function() {
                            var id = $(this).data('id');
                            deleteItemLocation(id);
                        });
                    const farmSelect = document.getElementById('farm_select');

                    const defaultOption = new Option("=== Pilih Farm ===", "", true, true);
                    farmSelect.append(defaultOption);

                    farmsData.forEach(farm => {
                        const option = document.createElement('option');
                        option.value = farm.farm_id;
                        option.textContent = farm.farm_name;
                        farmSelect.appendChild(option);
                    });

                    } else {
                        window.LaravelDataTables['farms-table'].ajax.reload();
                        document.getElementById('mySearchInput').addEventListener('keyup', function () {
                            window.LaravelDataTables['farms-table'].search(this.value).draw();
                        });
                    }
                });

                // Example usage on a specific page
                fetchItemsData({ task: 'GET', mode: 'LIST' }, function(items) {
                    // Process the itemsData here, e.g., display it in a table
                    // console.log(items);
                    // console.table(items);
                    // Additional logic for this particular page/component
                });
                fetchFarmsData({ task: 'GET', mode: 'LIST' }, function(farms) {
                    // Process the itemsData here, e.g., display it in a table
                    // console.log(farms);
                    // console.table(farms);
                    farmsData = farms;
                    // Additional logic for this particular page/component
                });
                fetchFarmsData({ task: 'GET', mode: 'LIST', submodul:'kandangs' }, function(farms) {
                    // Process the itemsData here, e.g., display it in a table
                    // console.log(farms);
                    // console.table(farms);
                    // Additional logic for this particular page/component
                });
            });
            // document.getElementById('mySearchInput').addEventListener('keyup', function () {
            //     window.LaravelDataTables['stoks-table'].search(this.value).draw();
            // });
            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    $('#kt_modal_add_user').modal('hide');
                    window.LaravelDataTables['stoks-table'].ajax.reload();
                });
            });

            function editItemLocation(id) {
                $.ajax({
                    url: '/api/v2/data/farms/items_mapping',
                    type: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        task: 'GET',
                        id: id
                    },
                    success: function(response) {
                        // Populate the form with the fetched data
                        $('#farm_select').val(response.farm_id).trigger('change');
                        $('#item_select').val(response.item_id).prop('disabled', false);
                        $('#location_select').val(response.location_id).prop('disabled', false);

                        // Change the form submission URL and method
                        $('#addDataForm').attr('action', '/api/v2/data/farms/items_mapping');
                        $('#addDataForm').attr('method', 'POST');
                        // Add a hidden input for the ID and task
                        $('#addDataForm').append('<input type="hidden" name="id" value="' + id + '">');
                        $('#addDataForm').append('<input type="hidden" name="task" value="UPDATE">');

                        // Show the modal
                        $('#addDataModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching item location data:', error);
                        toastr.error('Failed to fetch item location data');
                    }
                });
            }

            function deleteItemLocation(id) {
                if (confirm('Are you sure you want to delete this item location mapping?')) {
                    $.ajax({
                        url: '/api/v2/data/farms/items_mapping',
                        type: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            task: 'DELETE',
                            id: id
                        },
                        success: function(response) {
                            toastr.success('Item location mapping deleted successfully');
                            $('#itemLocationMappingTable').DataTable().ajax.reload();
                        },
                        error: function(xhr, status, error) {
                            let errorMessage = 'Failed to delete item location mapping';
                            
                            // Check if there's a response from the server
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            } else if (xhr.responseText) {
                                try {
                                    const responseObj = JSON.parse(xhr.responseText);
                                    if (responseObj.error) {
                                        errorMessage = responseObj.error;
                                    }
                                } catch (e) {
                                    console.error('Error parsing error response:', e);
                                }
                            }
                            
                            toastr.error(errorMessage);
                        }
                    });
                }
            }
        </script>
    @endpush
</x-default-layout>

