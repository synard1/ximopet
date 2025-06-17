<x-default-layout>
    @if(auth()->user()->can('read company mapping'))
    <div class="card" id="companyTableCard">
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

                    @if(auth()->user()->can('create company mapping'))
                    <!--begin::Add feed purchasing-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('createMapping')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Mapping
                    </button>
                    <!--end::Add feed purchasing-->
                    @endif
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->

        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <div id="datatable-container">
                <!--begin::Table-->
                <div class="table-responsive">
                    {{ $dataTable->table() }}
                </div>
                <!--end::Table-->
            </div>
            <livewire:company.company-user-mapping-form />


        </div>
        <!--end::Card body-->
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <div class="text-center">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3 class="text-danger">Unauthorized Access</h3>
                <p class="text-muted">You do not have permission to view feed purchasing data.</p>
            </div>
        </div>
    </div>
    @endif
    {{-- <div class="card" id="companyTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Search Company"
                        id="mySearchInput" />
                </div>
                <!--end::Search-->
            </div>
            <!--end::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    @if(auth()->user()->hasRole('SuperAdmin'))
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('createMapping')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Add New Mapping
                    </button>
                    @endif
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            {{ $dataTable->table() }}
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <livewire:company.company-user-mapping-form /> --}}


    {{--
    <!-- Modal Tambah/Edit Mapping -->
    <div id="mappingModal"
        class="fixed inset-0 z-50 bg-black bg-opacity-40 flex items-center justify-center hidden transition-all">
        <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-lg relative animate-fadeIn">
            <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 text-2xl"
                onclick="closeMappingModal()" aria-label="Close">&times;</button>
            <h3 class="text-xl font-semibold mb-6 text-gray-800" id="mappingModalTitle">Tambah Mapping</h3>
        </div>
    </div> --}}

    @push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
        document.addEventListener('livewire:init', function () {
                window.addEventListener('hide-datatable', () => {
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                });

                window.addEventListener('show-datatable', () => {
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                });
                
            });

    </script>
    <style>
        .animate-fadeIn {
            animation: fadeIn .2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }
    </style>
    <script>
        function openMappingModal() {
            document.getElementById('mappingModal').classList.remove('hidden');
        }
        function closeMappingModal() {
            document.getElementById('mappingModal').classList.add('hidden');
        }
        // Tutup modal otomatis setelah mapping disimpan
        document.addEventListener('livewire:load', function () {
            Livewire.on('mappingSaved', function () {
                closeMappingModal();
            });
        });
    </script>
    @endpush
</x-default-layout>