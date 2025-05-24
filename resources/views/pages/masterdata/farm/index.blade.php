<x-default-layout>

    @section('title')
    Master Data Farm
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar" id="cardToolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    @if (auth()->user()->can('create farm management'))
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('create')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data
                    </button>
                    <!--end::Add user-->
                    @endif
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->

        </div>
        <!--end::Card header-->

        <div class="card-body py-4">
            <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                @if(auth()->user()->can('read farm management'))
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#kt_tab_farm">Data Farm</a>
                </li>
                @endif
                @if(auth()->user()->hasAllPermissions(['access farm management', 'read farm operator']))
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#kt_tab_operator">Data Operator</a>
                </li>
                @endif
                @if(auth()->user()->hasAllPermissions(['access farm storage', 'read farm storage']))
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#kt_tab_storage">Data Storage</a>
                </li>
                @endif
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="kt_tab_farm" role="tabpanel">
                    <div class="card-body py-4">
                        <div class="table-responsive">
                            {!! $dataTable->table(['class' => 'table table-striped table-row-bordered gy-5 gs-7'], true)
                            !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="kt_tab_operator" role="tabpanel">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <div class="d-flex align-items-center position-relative my-1">
                                {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                                <input type="text" data-kt-user-table-filter="search"
                                    class="form-control form-control-solid w-250px ps-13" placeholder="Cari Operator"
                                    id="mySearchInput2" />
                            </div>
                        </div>
                        <div class="card-toolbar">
                            <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#kt_modal_tambah_operator_farm">
                                    {!! getIcon('plus', 'fs-2', '', 'i') !!}
                                    Tambah Petugas Operator
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body py-4">
                        <div class="table-responsive">
                            <table id="operatorsTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Farm</th>
                                        <th>Nama Operator</th>
                                        <th>Email Login</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="kt_tab_storage" role="tabpanel">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <div class="d-flex align-items-center position-relative my-1">
                                {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                                <input type="text" data-kt-user-table-filter="search"
                                    class="form-control form-control-solid w-250px ps-13" placeholder="Cari Storage"
                                    id="mySearchInput3" />
                            </div>
                        </div>
                        <div class="card-toolbar">
                            <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#kt_modal_tambah_storage">
                                    {!! getIcon('plus', 'fs-2', '', 'i') !!}
                                    Tambah Data Storage
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body py-4">
                        <div class="table-responsive">
                            <table id="storageTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Farm</th>
                                        <th>Tipe Storage</th>
                                        <th>Nama Storage</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(auth()->user()->hasRole(['Manager']))
    @else
    <livewire:master-data.tambah-operator-farm />
    <livewire:master-data.tambah-storage-farm />
    @include('pages.masterdata.farm._related_data_modal')
    @endif

    <livewire:master-data.farm-modal />

    <!-- Farm Details Modal -->
    <div class="modal fade" id="farmDetailsModal" tabindex="-1" aria-labelledby="farmDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="farmDetailsModalLabel">Farm Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table" id="kandangsTable">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Kapasitas</th>
                                <th>Status</th>
                                <th>Tanggal Masuk DOC</th>
                                <th>Jumlah Awal DOC</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">

        @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            $(document).ready(function() {
                $('.nav-link').on('shown.bs.tab', function(e) {
                    if (e.target.href.includes('#kt_tab_operator')) {
                        var table = new DataTable('#operatorsTable');
                        table.destroy();
                        getOperators();
                        document.getElementById('mySearchInput2').addEventListener('keyup', function () {
                            $('#operatorsTable').DataTable().search(this.value).draw();
                        });
                    } else if (e.target.href.includes('#kt_tab_storage')) {
                        var table = new DataTable('#storageTable');
                        table.destroy();
                        getStorage();
                        document.getElementById('mySearchInput3').addEventListener('keyup', function () {
                            $('#storageTable').DataTable().search(this.value).draw();
                        });
                    } else {
                        window.LaravelDataTables['farms-table'].ajax.reload();
                        document.getElementById('mySearchInput').addEventListener('keyup', function () {
                            window.LaravelDataTables['farms-table'].search(this.value).draw();
                        });
                    }
                });

                // Listen for the refreshDatatable event
                Livewire.on('refreshDatatable', () => {
                    window.LaravelDataTables['farms-table'].ajax.reload();
                });
            });

            function getOperators() {
                const task = 'GET';
                const mode = 'TABLE';

                new DataTable('#operatorsTable', {
                    ajax: {
                        url: '/api/v2/data/farms/operators',
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
                        { data: 'nama_farm' },
                        { data: 'nama_operator' },
                        { data: 'email' },
                        { data: null, orderable: false, searchable: false, render: function (data, type, row) {
                            return `<button class="btn btn-sm btn-danger" onclick="deleteOperator('${row.user_id}','${row.farm_id}')">Delete</button>`;
                        }}
                    ],
                    error: function (xhr, error, thrown) {
                        if (xhr.status === 401) {
                            window.location.href = '/login';
                        }
                    }
                });
            }

            function getStorage() {
                const task = 'GET';
                const mode = 'TABLE';

                new DataTable('#storageTable', {
                    ajax: {
                        url: '/api/v2/data/farms/storage',
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
                        { data: 'nama_farm' },
                        { data: 'type', render: function (data, type, row) {
                            return data.charAt(0).toUpperCase() + data.slice(1);
                        }},
                        { data: 'nama' },
                        { data: null, orderable: false, searchable: false, render: function (data, type, row) {
                            return `<button class="btn btn-sm btn-danger" onclick="deleteStorage('${row.storage_id}')">Delete</button>`;
                        }}
                    ],
                    error: function (xhr, error, thrown) {
                        if (xhr.status === 401) {
                            window.location.href = '/login';
                        }
                    }
                });
            }

            function deleteOperator(user_id, farm_id) {
                const finalData = {
                    farm_id: farm_id,
                    user_id: user_id,
                    task: 'DELETE',
                };

                if (confirm('Are you sure you want to delete this operator?')) {
                    $.ajaxSetup({
                        headers: {
                            'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                    });

                    $.ajax({
                        url: `/api/v2/data/farms/operators`,
                        type: 'POST',
                        data: JSON.stringify(finalData),
                        contentType: 'application/json', 
                        success: function(result) {
                            toastr.success(result.message);
                            $('#operatorsTable').DataTable().ajax.reload();
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred while trying to delete the operator.');
                        }
                    });
                }
            }

            function deleteStorage(storage_id) {
                const finalData = {
                    storage_id: storage_id,
                    task: 'DELETE',
                };

                if (confirm('Are you sure you want to delete this storage?')) {
                    $.ajaxSetup({
                        headers: {
                            'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                    });

                    $.ajax({
                        url: `/api/v2/data/farms/storage`,
                        type: 'POST',
                        data: JSON.stringify(finalData),
                        contentType: 'application/json', 
                        success: function(result) {
                            toastr.success(result.message);
                            $('#storageTable').DataTable().ajax.reload();
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred while trying to delete the storage.');
                        }
                    });
                }
            }

            function deleteFarm(farmId) {
                const finalData = {
                    farm_id: farmId,
                    task: 'DELETE',
                };
                $.ajaxSetup({
                        headers: {
                            'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                    });
                $.ajax({
                    url: `/api/v2/data/farms`,
                    type: 'POST',
                    data: JSON.stringify(finalData),
                    contentType: 'application/json', 
                    success: function(response) {
                        if (response.relatedData) {
                            // Populate the table body with related data
                            var tableBody = $('#relatedDataTableBody');
                            tableBody.empty(); // Clear existing rows

                            response.relatedData.forEach(function(item) {
                                var row = '<tr><td>' + item.type + '</td><td>' + item.name + '</td></tr>';
                                tableBody.append(row);
                            });

                            toastr.error('Data Farm Tidak Bisa Dihapus');

                            // Show the modal
                            $('#relatedDataModal').modal('show');
                        } else {
                            // Handle successful deletion
                            alert(response.success);
                            // Reload the page or update the UI accordingly
                        }
                    },
                    error: function(xhr) {
                        // Handle errors
                        alert('An error occurred: ' + xhr.responseText);
                    }
                });
            }

            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    Livewire.dispatch('closeModalFarm');
                    
                    window.LaravelDataTables['farms-table'].ajax.reload();
                    $('.table').each(function() {
                        if ($.fn.DataTable.isDataTable(this)) {
                            $(this).DataTable().ajax.reload();
                        }
                    });
                    let a = document.getElementById('form_farm_operator');
                    a.reset();
                });
            });
        </script>
        @endpush
</x-default-layout>