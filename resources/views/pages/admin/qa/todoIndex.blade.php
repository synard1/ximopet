<x-default-layout>

    @section('title')
    QA Todo
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


        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <livewire:qa-todo-list />


        </div>
        <!--end::Card body-->
    </div>

    @push('scripts')
    {{--

    {{-- {{ $dataTable->scripts() }} --}}
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

            // Livewire.on('closeForm', function () {
            //     showLoadingSpinner();
            //     const cardList = document.getElementById(`stokTableCard`);
            //     cardList.style.display = 'block';

            //     const cardForm = document.getElementById(`cardForm`);
            //     cardForm.style.display = 'none';

            //     // Reload DataTables
            //     $('.table').each(function() {
            //         if ($.fn.DataTable.isDataTable(this)) {
            //             $(this).DataTable().ajax.reload();
            //         }
            //     });
            // });

            Livewire.on('showForm', function () {
                // Show the form card
                const cardForm = document.getElementById('cardForm');
                if (cardForm) {
                    cardForm.style.display = 'block';
                    console.log('form ada');
                    
                }
            });
        });
        
        
            // document.getElementById('mySearchInput').addEventListener('keyup', function () {
            //     window.LaravelDataTables['pembelianStoks-table'].search(this.value).draw();
            // });
            // document.addEventListener('livewire:init', function () {
            //     Livewire.on('success', function () {
            //         $('#kt_modal_add_user').modal('hide');
            //         window.LaravelDataTables['kandangs-table'].ajax.reload();
            //     });
            // });

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


    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('todoUpdated', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('todoModal'));
                if (modal) {
                    modal.hide();
                }
            });
        });
    </script>
    @endpush
</x-default-layout>