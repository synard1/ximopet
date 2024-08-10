<x-default-layout>

    @section('title')
        Master Data Farm
    @endsection

    @section('breadcrumbs')
    @endsection
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#kt_tab_farm">Data Farm</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#kt_tab_operator">Data Operator</a>
        </li>
    </ul>
    
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="kt_tab_farm" role="tabpanel">
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                            <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Farm" id="mySearchInput"/>
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--begin::Card title-->
        
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        {{-- <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                            <!--begin::Add user-->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                                {!! getIcon('plus', 'fs-2', '', 'i') !!}
                                Add User
                            </button>
                            <!--end::Add user-->
                        </div>
                        <!--end::Toolbar--> --}}
        
                        <!--begin::Modal-->
                        <livewire:master-data.farm-list />
                        <!--end::Modal-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
        
                <!--begin::Card body-->
                <div class="card-body py-4">
                    
                    <!--begin::Table-->
                    <div class="table-responsive">
                        {{-- {{ $dataTable->table() }} --}}
                        {{-- {!! $dataTable->table(['class' => 'table table-bordered']) !!} --}}
                        {!! $dataTable->table(['class' => 'table table-bordered'], true) !!}


                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
        </div>
        <div class="tab-pane fade" id="kt_tab_operator" role="tabpanel">
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                            <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Operator" id="mySearchInput"/>
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--begin::Card title-->

                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                            <!--begin::Add user-->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_operator_farm">
                            {{-- <button type="button" class="btn btn-primary" wire:click="openModalForm"> --}}
                                {!! getIcon('plus', 'fs-2', '', 'i') !!}
                                Tambah Petugas Operator
                            </button>
                            <!--end::Add user-->
                        </div>
                        <!--end::Toolbar-->
                    </div>
                </div>
                <!--end::Card header-->
        
                <!--begin::Card body-->
                <div class="card-body py-4">                    
                    <!--begin::Table-->
                    <div class="table-responsive">
                        <table id="operatorsTable" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    {{-- <th>ID</th> --}}
                                    <th>Nama Farm</th>
                                    <th>Nama Operator</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
        </div>
    </div>
    
    <livewire:master-data.tambah-operator-farm />

    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            $(document).ready(function() {
                // Attach a click event handler to the tab links
                $('.nav-link').on('shown.bs.tab', function(e) {
                    // Check if the clicked tab has the id kt_incident_general
                    if (e.target.href.includes('#kt_tab_operator')) {
                        var table = new DataTable('#operatorsTable');
                        table.destroy();
                        // window.LaravelDataTables['operatorsTable'].ajax.reload();
                        getOperators();
                        console.log('tab operator');
                    }else{
                        // var table = new DataTable('#farms-table');
                        // table.destroy();
                        window.LaravelDataTables['farms-table'].ajax.reload();
                        
                        console.log('tab farm');
                    }
                });
            });

            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['farms-table'].search(this.value).draw();
            });
            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    $('#kt_modal_add_user').modal('hide');
                    window.LaravelDataTables['farms-table'].ajax.reload();
                    // window.LaravelDataTables['operatorsTable'].ajax.reload();

                     // Reload DataTables
                    $('.table').each(function() {
                        if ($.fn.DataTable.isDataTable(this)) {
                            $(this).DataTable().ajax.reload();
                        }
                    });

                    let a = document.getElementById('form_farm_operator');
                    a.reset();

                });
            });
            $('#kt_modal_add_user').on('hidden.bs.modal', function () {
                Livewire.dispatch('new_user');
            });

            function getOperators() {
                new DataTable('#operatorsTable', {
                    ajax: `/api/v1/farm/operators`,
                    columns: [
                        { data: '#',
                            render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                            } 
                        },
                        // { data: 'farm_id' },
                        { data: 'nama_farm' },
                        { data: 'nama_operator' },
                        { data: 'status' },
                        { 
                            data: null, 
                            orderable: false, 
                            searchable: false,
                            render: function (data, type, row) {
                                return `
                                    <a href="/farm/operators/${row.id}" class="btn btn-sm btn-primary">View</a>
                                    <a href="/farm/operators/${row.id}/edit" class="btn btn-sm btn-warning">Edit</a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteOperator('${row.id}')">Delete</button>
                                `;
                            }
                        }
                    ]
                });
            }

            function deleteOperator(id) {
                if (confirm('Are you sure you want to delete this operator?')) {
                    $.ajax({
                        url: `/api/v1/farm/operators/${id}`,
                        type: 'DELETE',
                        success: function(result) {
                            // Reload the DataTable to reflect the deletion
                            $('#operatorsTable').DataTable().ajax.reload();
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred while trying to delete the operator.');
                        }
                    });
                }
            }
        </script>
    @endpush
</x-default-layout>

