<x-default-layout>
    @section('title')
    Data Standar Ayam
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="standarBobotTables">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                {{-- <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Cari Operator"
                        id="mySearchInput2" />
                </div> --}}
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <button type="button" class="btn btn-primary" id="btnTambahData">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table table-striped table-row-bordered gy-5 gs-7'], true) !!}
            </div>
        </div>
    </div>

    <div class="card" id="bobotForm" style="display: none">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="card-title">Form Standar Ayam</h3>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary" id="btnBackToList">
                        {!! getIcon('arrow-left', 'fs-2', '', 'i') !!}
                        Kembali
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <livewire:master-data.livestock-strain.standard />
        </div>
    </div>

    <livewire:standar-bobot-view-modal />

    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        // Handle switching between table and form views
        document.addEventListener('DOMContentLoaded', function() {
            const tableCard = document.getElementById('standarBobotTables');
            const formCard = document.getElementById('bobotForm');
            const btnTambahData = document.getElementById('btnTambahData');
            const btnBackToList = document.getElementById('btnBackToList');

            // Show form and hide table when clicking Tambah Data
            btnTambahData.addEventListener('click', function() {
                tableCard.style.display = 'none';
                formCard.style.display = 'block';
            });

            // Show table and hide form when clicking Back
            btnBackToList.addEventListener('click', function() {
                formCard.style.display = 'none';
                tableCard.style.display = 'block';
                // Reload the farms-table datatable
                LaravelDataTables['standarBobots-table'].ajax.reload();
                Livewire.dispatch('resetInputBobot');
            });


            
        });
       

        document.addEventListener('livewire:init', function () {
            Livewire.on('strainStandardEdit', function () {
                const tableCard = document.getElementById('standarBobotTables');
                const formCard = document.getElementById('bobotForm');
                
                tableCard.style.display = 'none';
                formCard.style.display = 'block';
            });

            Livewire.on('showDetailModal', () => {
                console.log('Showing detail modal'); // Debugging line
                $('#standarBobotDetailModal').modal('show'); // Show the modal
            });

            Livewire.on('closeModal', function () {
                $('#standarBobotDetailModal').modal('hide'); // Hide the modal
            });

            // Livewire.on('confirmDelete', function (data) {
            //     console.log(data);
            // });
        });
    </script>
    @endpush
</x-default-layout>