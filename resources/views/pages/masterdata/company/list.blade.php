<x-default-layout>

    @section('title')
        Master Data Company
    @endsection

    <div class="card">
        <!--begin::Card body-->
        <div class="card-body py-4">
            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#kt_tab_overview">Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#kt_tab_mapping">Mapping Admin</a>
                </li>
            </ul>
            
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="kt_tab_overview" role="tabpanel">
                    <!--begin::Card header-->
                    <div class="card-header border-0 pt-6">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <!--begin::Search-->
                            <div class="d-flex align-items-center position-relative my-1">
                                {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                                <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Data" id="searchCompany"/>
                            </div>
                            <!--end::Search-->
                        </div>
                        <!--begin::Card title-->

                        <div class="card-toolbar">
                            <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_tambah_company">
                                    {!! getIcon('plus', 'fs-2', '', 'i') !!}
                                    Tambah Data
                                </button>
                            </div>
                        </div>

                    </div>
                    <!--end::Card header-->
                    
                    <!--begin::Table-->
                    <div class="table-responsive">
                        {{ $dataTable->table() }}
                    </div>
                    <!--end::Table-->
                </div>
                <div class="tab-pane fade" id="kt_tab_mapping" role="tabpanel">
                    <div class="card-body py-4">                    
                        <div class="table-responsive">
                            <table id="companyUsers-table" class="table table-striped" style="width:100%">
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

        </div>
        <!--end::Card body-->
    </div>

    @include('pages.masterdata.company._create_modal')


    @push('scripts')
        {{ $dataTable->scripts() }}
    <script>
        $(document).ready(function() {
            $('.nav-link').on('shown.bs.tab', function(e) {
                if (e.target.href.includes('#kt_tab_mapping')) {
                    var table = new DataTable('#companyUsers-table');
                    table.destroy();
                    getCompanyAdmins();

                } else {
                    window.LaravelDataTables['companies-table'].ajax.reload();
                    document.getElementById('mySearchInput').addEventListener('keyup', function () {
                        window.LaravelDataTables['companies-table'].search(this.value).draw();
                    });
                }
            });
        });

            document.getElementById('searchCompany').addEventListener('keyup', function () {
                window.LaravelDataTables['companies-table'].search(this.value).draw();
            });
            document.addEventListener('livewire:init', function () {

            });

            function getCompanyAdmins() {
                const task = 'GET';
                const mode = 'TABLE';

                new DataTable('#companyUsers-table', {
                    ajax: {
                        url: '/api/v2/data/company/admins',
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
                        { data: 'nama_perusahaan' },
                        { data: 'nama_user' },
                        { data: 'email' },
                        { data: null, orderable: false, searchable: false, render: function (data, type, row) {
                            return `<button class="btn btn-sm btn-danger" onclick="deleteAdmin('${row.user_id}','${row.farm_id}')">Delete</button>`;
                        }}
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
</x-default-layout>

