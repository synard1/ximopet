<x-default-layout>

    @section('title')
    Data Pembelian Supply
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="stokTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
            </div>
            <!--begin::Card title-->

            @can('create transaction')
            <!--begin::Card toolbar-->
            <div class="card-toolbar" id="cardToolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('showCreateForm')">
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
            <livewire:supply-purchases.create />


        </div>
        <!--end::Card body-->
    </div>

    {{--
    <livewire:transaksi.pembelian-list /> --}}
    @include('pages.transaction.supply-purchases._modal_pembelian_details')

    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        document.querySelectorAll('[data-kt-button="create_new"]').forEach(function (element) {
			element.addEventListener('click', function () {
				// Simulate delete request -- for demo purpose only
				Swal.fire({
					html: `Preparing Form`,
					icon: "info",
					buttonsStyling: false,
					showConfirmButton: false,
					timer: 2000
				}).then(function () {

                    // $('#supplierDropdown').select2();

                    // Livewire.on('reinitialize-select2', function () {
                    //     $('.select2').select2();
                    // });

                    // console.log('form loaded');
                    // Livewire.dispatch('createPembelian');

                    const cardList = document.getElementById(`stokTableCard`);
                    cardList.style.display = 'none';
                    // cardList.classList.toggle('d-none');

                    const cardForm = document.getElementById(`cardForm`);
                    cardForm.style.display = 'block';
                    // cardList.classList.toggle('d-none');
					// fetchFarm();

				});
				
			});

		});

        document.addEventListener('livewire:init', function () {
            window.addEventListener('hide-datatable', () => {
                $('#datatable-container').hide();
                $('#cardToolbar').hide();
            });

            window.addEventListener('show-datatable', () => {
                $('#datatable-container').show();
                $('#cardToolbar').show();
            });

            Livewire.on('closeForm', function () {
                showLoadingSpinner();
                const cardList = document.getElementById(`stokTableCard`);
                cardList.style.display = 'block';

                const cardForm = document.getElementById(`cardForm`);
                cardForm.style.display = 'none';

                // Reload DataTables
                $('.table').each(function() {
                    if ($.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable().ajax.reload();
                    }
                });
            });
        });

        function getSupplyPurchaseDetails(param) {
            // console.log(param);
            // Get the Sanctum token from the session
            const token = '{{ Session::get('auth_token') }}';

            // Set up headers with the token
            const headers = {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            };

            const table = new DataTable('#detailsTable', {
                ajax: {
                    url: `/api/v2/supply/purchase/details/${param}`,
                    headers: headers
                },
                columns: [{
                        data: 'id',
                        title: '#',
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'qty',
                        className: 'editable text-center', // Add text-end class for right alignment
                        // render: $.fn.dataTable.render.number(',', '.', 2)
                    },
                    {
                        data: 'terpakai',
                        className: 'text-center', // Add text-end class for right alignment
                        // render: $.fn.dataTable.render.number(',', '.', 0)
                    },
                    {
                        data: 'sisa',
                        className: 'text-center', // Add text-end class for right alignment
                        // render: $.fn.dataTable.render.number(',', '.', 0)
                    },
                    {
                        data: 'unit'
                    },
                    {
                        data: 'price_per_unit',
                        className: 'editable text-end',
                        render: $.fn.dataTable.render.number(',', '.', 0, 'Rp')
                    },
                    {
                        data: 'total',
                        className: 'text-end', // Add text-end class for right alignment
                        render: $.fn.dataTable.render.number(',', '.', 0, 'Rp')
                    }
                ]
            });

            // Enable inline editing
            // Make cells editable (using a simple approach for now)
            table.on('click', 'tbody td.editable', function() {
                var cell = $(this);
                var originalValueText = cell.text();
                var originalValue = parseFloat(originalValueText.replace(/[^0-9.-]+/g, ''));

                // Get the row data safely
                var row = table.row(cell.closest('tr'));
                if (!row || !row.data()) {
                    console.error('Unable to get row data');
                    return;
                }
                var rowData = row.data();
                

                // Safely check 'terpakai'
                var terpakai = rowData && rowData.terpakai !== undefined ? rowData.terpakai : 0;

                // Create an input field for editing
                var input = $('<input type="text" value="' + originalValue + '">');
                cell.html(input);
                input.focus();

                // Handle saving the edit
                input.blur(function() {
                    var newValue = parseFloat(input.val());
                    var columnIndex = table.cell(cell).index().column;
                    var columnData = table.settings().init().columns[columnIndex];
                    console.log('newValue ' + newValue);
                    console.log('columnIndex ' + columnIndex);
                    console.log('columnData ' + columnData);
                    


                    if (!isNaN(newValue) && newValue !== originalValue) {
                        // Check if we're editing the 'qty' column
                        if (columnData.data === 'qty') {
                            // Get the 'terpakai' value from the row data
                            var terpakai = parseFloat(rowData.terpakai);

                            // Check if new quantity is less than 'terpakai'
                            if (newValue < terpakai) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Jumlah baru tidak boleh kurang dari jumlah terpakai.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                                table.ajax.reload();
                                return;
                            }
                        }

                        // Send AJAX request to update the data
                        $.ajax({
                            url: '/api/v2/supply/purchase/edit',
                            method: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                type: 'edit',
                                id: rowData.id,
                                column: columnData.data,
                                value: newValue
                            },
                            success: function(response) {
                                cell.text(newValue);
                                toastr.success(response.message);
                                table.ajax.reload();
                            },
                            error: function(xhr, status, error) {
                                table.ajax.reload();
                                if (xhr.status === 401) {
                                    toastr.error('Unauthorized. Please log in again.');
                                } else {
                                    toastr.error('Error updating value: ' + xhr.responseJSON.message);
                                }
                            }
                        });
                    } else if (isNaN(newValue) || newValue === '') {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Nilai tidak boleh kosong.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        table.ajax.reload();
                    } else {
                        table.ajax.reload();
                    }
                });

            });
        }


            // Fix for printable false not working
            // $(document).ready(function() {
            //     window.LaravelDataTables['pembelianStoks-table'].on('preXhr.dt', function(e, settings, data) {
            //         data.columns = settings.aoColumns.map(function(col, index) {
            //             return {
            //                 data: col.data,
            //                 name: col.name,
            //                 searchable: col.searchable,
            //                 orderable: col.orderable,
            //                 search: {value: "", regex: false}
            //             };
            //         }).filter(function(col) {
            //             return col.data !== 'action';
            //         });
            //     });
            // });
    </script>
    @endpush
</x-default-layout>