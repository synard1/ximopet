<x-default-layout>

    @section('title')
        Penjualan Ternak
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
            <div class="card" id="stokTableCard">
			@if(auth()->user()->hasRole(['Operator']))
                            <div class="card-header border-0 pt-6">
                           <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                            <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Data" id="searchTransaksiHarian"/>
                        </div>
                    </div>

                @can('create transaksi')
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                        <!--begin::Add user-->
                        <button type="button" class="btn btn-primary" data-kt-button="new_penjualan">
                            {!! getIcon('plus', 'fs-2', '', 'i') !!}
                            Tambah Data
                        </button>
                        <!--end::Add user-->
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Card toolbar-->     
                    
                @endcan
                 

                </div>     
            @endif
                
                <div class="card-body py-4">
                    <div class="table-responsive" style="width: 100%;">
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

    <livewire:transaksi.penjualan-ternak />


    @push('styles')
    <style>
        #penjualans-table_wrapper {
            width: 100%;
        }
        #penjualans-table {
            width: 100% !important;
        }
        .dataTables_scrollBody {
            overflow-x: auto;
            width: 100%;
        }
        .dataTables_scrollHead {
            overflow: visible !important;
        }
        .dataTables_scrollHeadInner {
            width: 100% !important;
        }
    </style>
    @endpush

    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('open-modal-penjualan', function () {
                    // Code to open your modal
                    $('#penjualanTernak').modal('show');
                    console.log('modal open');
                    
            });
        });

            // Custom print function
        function customPrint() {
            var table = $('#penjualans-table').DataTable();
            var visibleColumns = table.columns().visible().toArray();
            var printColumns = [];
            
            visibleColumns.forEach(function(visible, index) {
                if (visible) {
                    printColumns.push(index);
                }
            });

            table.button('print').action(function(e, dt, button, config) {
                config.exportOptions.columns = printColumns;
                $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
            });

            table.button('print').trigger();
        }

        // Replace the default print button with our custom one
        $(document).ready(function() {
            var table = $('#penjualans-table').DataTable();
            table.button('print').node().off('click');
            table.button('print').node().on('click', customPrint);
        });
            </script>
    @endpush
</x-default-layout>