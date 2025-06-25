<x-default-layout>
    @section('title')
    Data {{ trans('content.ternak',[],'id') }}
    @endsection

    @section('breadcrumbs')
    @endsection
    {{-- <div class="card" id="ternaksTables"> --}}
        <div class="card">
            <!--begin::Card body-->
            <div class="card-body py-4">
                <div id="datatable-container" class="table-responsive">
                    <!--begin::Table-->
                    {{ $dataTable->table(['id' => 'ternaks-table']) }}
                    <!--end::Table-->
                </div>

                <livewire:master-data.worker.assign-worker />
                <livewire:records />
                <livewire:master-data.livestock.settings />
                <livewire:master-data.livestock.manual-batch-depletion />
                <livewire:master-data.livestock.fifo-depletion />
                <livewire:feed-usages.manual-feed-usage />
                <livewire:livestock.mutation.manual-livestock-mutation />
                <livewire:livestock.mutation.fifo-livestock-mutation-configurable />
            </div>
            <!--end::Card body-->
        </div>

        @include('pages.masterdata.livestock._detail_modal')

        @push('scripts')
        {{ $dataTable->scripts() }}

        <script>
            $('#livestockSettingContainer').hide();

            document.addEventListener('livewire:init', function () {
                window.addEventListener('show-records', () => {
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                    $('#livewireRecordsContainer').show();
                });

                window.addEventListener('show-worker-assign-form', () => {
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                    $('#assignWorkerContainer').show();
                });

                window.addEventListener('hide-worker-assign-form', () => {
                    $('#assignWorkerContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                });

                window.addEventListener('hide-records', () => {
                    $('#livewireRecordsContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                });
                
                // Event listeners for livestock settings
                window.addEventListener('hide-livestock-setting', () => {
                    console.log('Hiding livestock settings container');
                    $('#livestockSettingContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();

                });

                window.addEventListener('show-livestock-setting', () => {
                    console.log('Showing livestock settings container');
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                    $('#livestockSettingContainer').show();
                });

                window.addEventListener('show-fifo-mutation', () => {
                    console.log('Showing livestock mutation container');
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                    $('#fifoMutationContainer').show();
                });

                window.addEventListener('hide-fifo-mutation', () => {
                    console.log('Hiding livestock mutation container');
                    $('#fifoMutationContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();

                    if (LaravelDataTables && LaravelDataTables["ternaks-table"]) {
                        LaravelDataTables["ternaks-table"].ajax.reload();
                    }
                });

                // Global event listeners for FIFO mutation notifications using Livewire
                Livewire.on('fifo-mutation-completed', (data) => {
                    console.log('🔥 Global: fifo-mutation-completed event received', data);
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Mutasi FIFO Berhasil!',
                            text: `Berhasil memutasi ${data.total_quantity} ekor menggunakan metode FIFO`,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert(`Mutasi FIFO Berhasil! Berhasil memutasi ${data.total_quantity} ekor menggunakan metode FIFO`);
                    }
                });

                Livewire.on('show-success-message', (data) => {
                    console.log('🔥 Global: show-success-message event received', data);
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: data.title || 'Berhasil',
                            text: data.message || 'Operasi berhasil diselesaikan',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert(`${data.title || 'Berhasil'}: ${data.message || 'Operasi berhasil diselesaikan'}`);
                    }
                });
            });

            window.addEventListener('notify', event => {
                alert(event.detail.message); // ganti dengan toastr atau lainnya jika ada
            });

            // on modal close kt_modal_ternak_details
            $('#kt_modal_ternak_details').on('hidden.bs.modal', function() {
                $('#detailTable').DataTable().destroy();
            });

            // // Event handler untuk DataTable actions
            // $(document).on('click', '[data-kt-action]', function(e) {
            //     e.preventDefault();
                
            //     const action = $(this).data('kt-action');
            //     const livestockId = $(this).data('livestock-id');
            //     const ternakId = $(this).data('ternak-id');
                
            //     console.log('Action triggered:', action, 'Livestock ID:', livestockId);

            //     switch(action) {
            //         case 'manual_depletion':
            //             // Trigger manual depletion modal
            //             console.log('Opening manual depletion for livestock:', livestockId);
            //             // Dispatch event to manual depletion component
            //             Livewire.dispatchTo('master-data.livestock.manual-batch-depletion', 'show-manual-depletion', { livestock_id: livestockId });
            //             break;
                        
            //         case 'update_setting':
            //             // Existing setting modal logic
            //             console.log('Opening settings for livestock:', livestockId);
            //             break;
                        
            //         case 'assign_worker':
            //             // Existing worker assignment logic
            //             console.log('Opening worker assignment for livestock:', livestockId);
            //             break;
                        
            //         case 'update_records':
            //             // Existing records logic
            //             console.log('Opening records for livestock:', ternakId);
            //             break;
                        
            //         default:
            //             console.log('Unknown action:', action);
            //     }
            // });
        </script>

        @endpush

        {{-- @livewire('admin-monitoring.permission-info')

        @livewire('qa-checklist-monitor', ['url' => request()->path()]) --}}

</x-default-layout>