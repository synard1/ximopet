<x-default-layout>
    @section('title')
    Master Data Strain Standard
    @endsection

    @section('breadcrumbs')
    @endsection
    @if(auth()->user()->can('read livestock strain standard master data'))
    <div class="card" id="standarBobotTables">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
            </div>
            <div class="card-toolbar" id="cardToolbar">
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    @can('create livestock strain standard master data')
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('showCreateForm')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data
                    </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body py-4">
            <div id="datatable-section" class="table-responsive">
                {!! $dataTable->table(['class' => 'table table-striped table-row-bordered gy-5 gs-7'], true) !!}
            </div>

            <livewire:master-data.livestock-standard.create />

        </div>
    </div>

    {{-- <div class="card" id="bobotForm" style="display: none">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="card-title">Form Standar Ayam</h3>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end">

                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('closeForm')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Kembali
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">

            <livewire:master-data.livestock-standard.create />
        </div>
    </div> --}}

    <livewire:standar-bobot-view-modal />

    @else
    <div class="card">
        <div class="card-body">
            <div class="text-center">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3 class="text-danger">Unauthorized Access</h3>
                <p class="text-muted">You do not have permission to view livestock standards.</p>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        document.addEventListener('livewire:init', function () {
            window.addEventListener('hide-datatable', () => {
                $('#datatable-section').hide();
                $('#cardToolbar').hide();
                $('#livestock-standard-form-section').show();
            });

            window.addEventListener('show-datatable', () => {
                $('#datatable-section').show();
                $('#cardToolbar').show();
                $('#livestock-standard-form-section').hide();
            });


        
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

            Livewire.on('confirm', function (data) {
                
                Swal.fire({
                    text: data[0].message,
                    icon: 'warning',
                    buttonsStyling: false,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, keep it',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary',
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch(data[0].callback, [data[0].id]);
                    }
                });
            });

            Livewire.on('removeStandard', function (index) {
                confirmRemove(index);
            });
        });

        function confirmRemove(index) {
            Swal.fire({
                text: 'Are you sure you want to remove this standard?',
                icon: 'warning',
                buttonsStyling: false,
                showCancelButton: true,
                confirmButtonText: 'Yes, remove it!',
                cancelButtonText: 'No, keep it',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('removeStandard', [index]);
                }
            });
        }
    </script>
    @endpush
    @livewire('qa-checklist-monitor', ['url' => request()->path()])
    @livewire('admin-monitoring.permission-info')
</x-default-layout>