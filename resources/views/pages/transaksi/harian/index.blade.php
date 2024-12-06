<x-default-layout>

    @section('title')
        Transaksi Harian
    @endsection

    @section('breadcrumbs')
    @endsection
    <ul class="nav nav-tabs flex-nowrap text-nowrap">
        <li class="nav-item">
            <a class="nav-link active btn btn-flex btn-active-light-success" data-bs-toggle="tab" href="#kt_tab_overview">Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link btn btn-flex btn-active-light-info" data-bs-toggle="tab" href="#kt_tab_details">Details Data</a>
        </li>
    </ul>
    
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="kt_tab_overview" role="tabpanel">
            <div class="card">
                <div class="card-body py-4">
                    <div class="table-responsive">
                        {{ $dataTable->table() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="kt_tab_details" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <!--begin::Form group-->
                    <div class="form-group">
                        <div data-repeater-list="data">
                            <div data-repeater-item>
                                <div class="fv-row form-group row mb-5">
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal:</label>
                                        <input class="form-control form-control-solid" placeholder="Pick date rage" id="kt_daterangepicker_1" required/>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Jenis:</label>
                                        <select class="form-select form-select-solid" id="filter_jenis" data-control="select2" data-close-on-select="false" data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="javascript:;" class="btn btn-sm btn-flex flex-center btn-light-primary mt-3 mt-md-9" id="apply_filter">
                                            <i class="ki-duotone ki-filter">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        </i> Apply Filter
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Form group-->

                <div class="mb-5">
                    <input type="text" id="searchInputFilter" class="form-control" placeholder="Search in filtered results...">
                </div>

                <!-- Add a container for filtered results -->
                <div id="filtered_results" class="mt-5">
                    <!-- Filtered data will be displayed here -->
                </div>
            </div>
        </div>
        </div>
    </div>


    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            $(document).ready(function() {
                $('.nav-link').on('shown.bs.tab', function(e) {
                    if (e.target.href.includes('#kt_tab_overview')) {
                        // var table = new DataTable('#operatorsTable');
                        // table.destroy();
                        // getOperators();
                        // document.getElementById('mySearchInput2').addEventListener('keyup', function () {
                        //     $('#operatorsTable').DataTable().search(this.value).draw();
                        // });
                    } else if (e.target.href.includes('#kt_tab_details')) {
                        getJenis();
                    }
                });


                $("#kt_daterangepicker_1").daterangepicker();
    
                // Function to handle filtering
                function applyFilter() {
                    var dateRange = $('#kt_daterangepicker_1').val();
                    var selectedJenis = $('#filter_jenis').val();

                    // AJAX call to fetch filtered data
                    $.ajax({
                        url: '{{ route("transaksi.harian.filter") }}',
                        method: 'POST',
                        data: {
                            date_range: dateRange,
                            jenis: selectedJenis,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            // Update the filtered_results container with the response
                            $('#filtered_results').html(response);
                            // Clear the search input
                            $('#searchInputFilter').val('');
                            // Trigger search to update visibility
                            searchFilteredResults();
                        },
                        error: function(xhr) {
                            if (xhr.status === 404) {
                                // Handle the "No data found" error
                                var errorMessage = JSON.parse(xhr.responseText).error;
                                $('#filtered_results').html('<div class="alert alert-warning">' + errorMessage + '</div>');
                            } else {
                                console.error('Error fetching filtered data:', xhr);
                                $('#filtered_results').html('<div class="alert alert-danger">An error occurred while fetching data. Please try again.</div>');
                            }
                            // Clear the search input
                            $('#searchInputFilter').val('');
                        }
                    });
                }

                function getJenis() {
                    $.ajax({
                        url: '{{ route("master-data.item-categories.list") }}',
                        method: 'GET',
                        success: function(response) {
                            var select = $('#filter_jenis');
                            select.empty();
                            select.append('<option></option>');
                            $.each(response, function(index, category) {
                                select.append('<option value="' + category.name + '">' + category.name + '</option>');
                            });
                            select.trigger('change');
                        },
                        error: function(xhr) {
                            console.error('Error fetching item categories:', xhr);
                        }
                    });
                }

                function searchFilteredResults() {
                    var input, filter, container, dateGroups, i, j, visible;
                    input = document.getElementById("searchInputFilter");
                    filter = input.value.toUpperCase();
                    container = document.getElementById("filteredDataContainer");
                    if (!container) return; // Exit if container is not found

                    dateGroups = container.getElementsByClassName("date-group");

                    for (i = 0; i < dateGroups.length; i++) {
                        var table = dateGroups[i].getElementsByTagName("table")[0];
                        var tr = table.getElementsByTagName("tr");
                        visible = false;

                        // Start from 1 to skip the header row
                        for (j = 1; j < tr.length; j++) {
                            var td = tr[j].getElementsByTagName("td");
                            var found = false;

                            for (var k = 0; k < td.length; k++) {
                                if (td[k]) {
                                    var txtValue = td[k].textContent || td[k].innerText;
                                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                        found = true;
                                        visible = true;
                                        break;
                                    }
                                }
                            }

                            if (found) {
                                tr[j].style.display = "";
                            } else {
                                tr[j].style.display = "none";
                            }
                        }

                        // Show/hide the entire date group based on whether any rows are visible
                        dateGroups[i].style.display = visible ? "" : "none";
                    }
                }

                // Add event listener for the search input
                $(document).on('keyup', '#searchInputFilter', searchFilteredResults);
    
                // Attach click event to the Apply Filter button
                $('#apply_filter').on('click', function() {
                    applyFilter();
                });

                // Call getJenis on page load if the details tab is active
                if (window.location.hash === '#kt_tab_details') {
                    getJenis();
                }
            });
        </script>
    @endpush
</x-default-layout>
