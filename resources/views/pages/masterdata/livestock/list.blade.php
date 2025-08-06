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

            // Enhanced DataTable reload function with error handling
            function reloadDataTableSafely() {
                log('🔄 Attempting to reload DataTable...');
                
                try {
                    if (LaravelDataTables && LaravelDataTables["ternaks-table"]) {
                        const table = LaravelDataTables["ternaks-table"];
                        
                        // Check if table is still valid
                        if (!table.context || !table.context.length) {
                            log('⚠️  DataTable context is invalid, skipping reload');
                            return;
                        }
                        
                        // Save current column visibility state
                        const columnVisibility = [];
                        const currentPage = table.page.info().page;
                        const pageLength = table.page.len();
                        
                        // Store current settings
                        table.columns().every(function(index) {
                            columnVisibility[index] = this.visible();
                        });
                        
                        // Reload table with proper callback and error handling
                        table.ajax.reload(function(json) {
                            log('✅ DataTable reloaded successfully');
                            
                            // Restore settings after reload
                            setTimeout(() => {
                                try {
                                    // Restore column visibility
                                    table.columns().every(function(index) {
                                        if (columnVisibility[index] !== undefined) {
                                            this.visible(columnVisibility[index]);
                                        }
                                    });
                                    
                                    // Restore page if possible
                                    if (currentPage > 0) {
                                        table.page(currentPage);
                                    }
                                    
                                    // Adjust layout
                                    table.columns.adjust();
                                    if (table.responsive && table.responsive.recalc) {
                                        table.responsive.recalc();
                                    }
                                    
                                    log('✅ DataTable settings restored');
                                } catch (restoreError) {
                                    log('⚠️  Error restoring DataTable settings:', restoreError);
                                }
                            }, 100);
                        }, false); // false = don't reset paging
                        
                    } else {
                        log('⚠️  LaravelDataTables["ternaks-table"] not found');
                    }
                } catch (error) {
                    log('❌ Error reloading DataTable:', error);
                    
                    // Fallback: try to reinitialize the entire page if critical error
                    if (error.message && error.message.includes('Cannot read properties')) {
                        log('🔄 Attempting page refresh as fallback...');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                }
            }

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
                    
                    // Reload table after worker assignment
                    reloadDataTableSafely();
                });

                window.addEventListener('hide-records', () => {
                    $('#livewireRecordsContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                    
                    // Reload table after records update
                    reloadDataTableSafely();
                });
                
                // Event listeners for livestock settings
                window.addEventListener('hide-livestock-setting', () => {
                    log('Hiding livestock settings container');
                    $('#livestockSettingContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                    
                    // Reload table after livestock settings update
                    reloadDataTableSafely();
                });

                window.addEventListener('show-livestock-setting', () => {
                    log('Showing livestock settings container');
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                    $('#livestockSettingContainer').show();
                });

                window.addEventListener('show-fifo-mutation', () => {
                    log('Showing livestock mutation container');
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                    $('#fifoMutationContainer').show();
                });

                window.addEventListener('hide-fifo-mutation', () => {
                    log('Hiding livestock mutation container');
                    $('#fifoMutationContainer').hide();
                    $('#datatable-container').show();
                    $('#cardToolbar').show();

                    // Use enhanced reload function
                    reloadDataTableSafely();
                });

                // Global event listeners for FIFO mutation notifications using Livewire
                Livewire.on('fifo-mutation-completed', (data) => {
                    log('🔥 Global: fifo-mutation-completed event received', data);
                    
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
                    log('🔥 Global: show-success-message event received', data);
                    
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
                    
                    // Reload table after successful operation
                    setTimeout(() => {
                        reloadDataTableSafely();
                    }, 500);
                });

                // Additional Livewire event listeners for table reload
                Livewire.on('refresh-livestock-table', () => {
                    log('🔥 Global: refresh-livestock-table event received');
                    reloadDataTableSafely();
                });

                Livewire.on('livestock-data-updated', () => {
                    log('🔥 Global: livestock-data-updated event received');
                    reloadDataTableSafely();
                });
            });

            window.addEventListener('notify', event => {
                alert(event.detail.message); // ganti dengan toastr atau lainnya jika ada
            });

            // on modal close kt_modal_ternak_details
            $('#kt_modal_ternak_details').on('hidden.bs.modal', function() {
                try {
                    const detailTable = $('#detailTable').DataTable();
                    if (detailTable && typeof detailTable.destroy === 'function') {
                        detailTable.destroy();
                        log('✅ Detail table destroyed successfully');
                    }
                } catch (error) {
                    log('⚠️  Error destroying detail table:', error);
                }
                
                // Optional: reload main table if needed
                // reloadDataTableSafely();
            });

            // // Event handler untuk DataTable actions
            // $(document).on('click', '[data-kt-action]', function(e) {
            //     e.preventDefault();
                
            //     const action = $(this).data('kt-action');
            //     const livestockId = $(this).data('livestock-id');
            //     const ternakId = $(this).data('ternak-id');
                
            //     log('Action triggered:', action, 'Livestock ID:', livestockId);

            //     switch(action) {
            //         case 'manual_depletion':
            //             // Trigger manual depletion modal
            //             log('Opening manual depletion for livestock:', livestockId);
            //             // Dispatch event to manual depletion component
            //             Livewire.dispatchTo('master-data.livestock.manual-batch-depletion', 'show-manual-depletion', { livestock_id: livestockId });
            //             break;
                        
            //         case 'update_setting':
            //             // Existing setting modal logic
            //             log('Opening settings for livestock:', livestockId);
            //             break;
                        
            //         case 'assign_worker':
            //             // Existing worker assignment logic
            //             log('Opening worker assignment for livestock:', livestockId);
            //             break;
                        
            //         case 'update_records':
            //             // Existing records logic
            //             log('Opening records for livestock:', ternakId);
            //             break;
                        
            //         default:
            //             log('Unknown action:', action);
            //     }
            // });
        </script>

        @endpush

        {{-- @livewire('admin-monitoring.permission-info')

        @livewire('qa-checklist-monitor', ['url' => request()->path()]) --}}

</x-default-layout>