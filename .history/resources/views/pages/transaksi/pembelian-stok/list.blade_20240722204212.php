<x-default-layout>

    @section('title')
        Data Pembelian Stok
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="stokTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Data DOC" id="mySearchInput"/>
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pembelian
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->

                <!--begin::Modal-->
                <livewire:master-data.kandang-list />
                <!--end::Modal-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    <div class="card" id="stokFormCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Data DOC" id="mySearchInput"/>
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            document.querySelectorAll('[data-kt-action="new_kandang"]').forEach(function (element) {
			element.addEventListener('click', function () {
				// Simulate delete request -- for demo purpose only
				Swal.fire({
					html: `Load Data Form`,
					icon: "info",
					buttonsStyling: false,
					showConfirmButton: false,
					timer: 2000
				}).then(function () {
					fetchFarm();
					// $('#kt_modal_new_kandang').modal('show'); // Assuming your modal has the ID 'kt_modal_master_kandang'


					// $.ajax({
					//     url: '/api/v1/farms-list',
					//     type: 'GET',
					//     data: {
					//         // id: incidentId,
					//     },
					//     success: function(response) {
					// 		$('#kt_modal_new_kandang').modal('show'); // Assuming your modal has the ID 'kt_modal_master_kandang'

					//         // $('#incident').val(response.data.title);
					//         // $('#description').val(response.data.description);

					//         // // Format date and set values for report_time and respond_date
					//         // $('#report_time').val(formatDateTime(response.data.report_time));
					//         // $('#response_time').val(formatDateTime(response.data.response_time));

					//         // // Auto-select the unit-dropdown based on response.data.origin_unit
					//         selectClassification(response.nama);
					//         // selectLocation(response.data.location);
					//         // selectSource(response.data.source);
					//         // selectReporter(response.data.reportedBy);
					//         // selectSeverity(response.data.severity);

					//         // response.data.category.forEach(function(category) {
					//         //     $('input[name="category[]"][value="' + category + '"]').prop('checked', true);
					//         // });

					//         // // Find the input element by its id
					//         // var incidentInput = document.getElementById('incident');
					//         // incidentInput.setAttribute('readonly', true);

					//         // // Create a new hidden input element
					//         // var hiddenInput = document.createElement("input");
					//         // hiddenInput.type = "hidden";
					//         // hiddenInput.id = "incident_id";
					//         // hiddenInput.name = "incident_id";
					//         // hiddenInput.className = "form-control form-control-solid";
					//         // hiddenInput.value = response.data.id;
					//         // hiddenInput.readOnly = true;

					//         // // Find the form by its id and append the hidden input to it
					//         // document.getElementById("kt_new_incident_form").appendChild(hiddenInput);
					//     },
					//     error: function (error) {
					//         let errorMessage = "Sorry, looks like there are some errors detected, please try again.";

					//         if (error.responseJSON && error.responseJSON.message) {
					//             errorMessage = error.responseJSON.message;
					//         }

					//         Swal.fire({
					//             text: errorMessage,
					//             icon: "error",
					//             buttonsStyling: false,
					//             confirmButtonText: "Ok, got it!",
					//             customClass: {
					//                 confirmButton: "btn btn-primary"
					//             }
					//         });

					//         console.error('Error deleting incident:', error);
					//     }
					// });
				});
				// console.log('create supplier click!');

				// Livewire.dispatch('openModal');

				// Livewire.on('openModal', () => {
				// 	var myModal = new bootstrap.Modal(document.getElementById('exampleModal'), {
				// 		keyboard: false
				// 	});
				// 	myModal.show();
				// });
				
				


				// Livewire.on('show-modal', () => {
				// 	$('#kt_modal_new_kandang').modal('show'); // Assuming your modal has the ID 'kt_modal_master_kandang'
				// });
				
			});

		});
            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['kandangs-table'].search(this.value).draw();
            });
            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    $('#kt_modal_add_user').modal('hide');
                    window.LaravelDataTables['kandangs-table'].ajax.reload();
                });
            });
            $('#kt_modal_add_user').on('hidden.bs.modal', function () {
                Livewire.dispatch('new_user');
            });
        </script>
    @endpush
</x-default-layout>

