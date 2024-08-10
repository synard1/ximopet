<div>

    <!--begin::sidebar menu-->
<div class="menu-sidebar menu menu-fit menu-column menu-rounded menu-title-gray-700 menu-icon-gray-700 menu-arrow-gray-700 fw-semibold fs-6 align-items-stretch flex-grow-1" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="true">
	<!--begin:Menu item-->
	<div class="menu-item py-1">
		<!--begin:Menu content-->
		<div class="menu-content">
			<div class="separator separator-dashed"></div>
		</div>
		<!--end:Menu content-->
	</div>
	<!--end:Menu item-->
	<!--begin:Menu item-->
	<div data-kt-menu-trigger="click" class="menu-item menu-accordion show">
		<!--begin:Menu link-->
		<span class="menu-link">
			<span class="menu-title">{{ trans('menu.create_header',[],'id') }}</span>
			<span class="menu-arrow"></span>
		</span>
		<!--end:Menu link-->
		<!--begin:Menu sub-->
		<div class="menu-sub menu-sub-accordion menu-state-gray-900 menu-fit open">
			<!--begin:Menu item-->
			<div class="menu-item menu-accordion menu-fit">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-delivery-3 fs-4 text-success"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_supplier',[],'id') }}</span>
					<span class="menu-badge">
						{{-- <livewire:master-data._create_supplier /> --}}
						{{-- <button class="btn btn-sm btn-icon btn-action" wire:click="openModal">
							<i class="ki-outline ki-plus fs-4"></i>
						</button> --}}
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_supplier">
						{{-- <button class="btn btn-sm btn-icon btn-action"> --}}
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
						{{-- <a href="{{ route('supplier.create') }}"  class="btn btn-light-primary"><i class="ki-outline ki-plus fs-4"></i></a> --}}

					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-courier fs-4 text-danger"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_customer',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_customer">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-home fs-4 text-success"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_farm',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_farm">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-home-1 fs-4 text-info"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_cage',[],'id') }}</span>
					<span class="menu-badge">
						{{-- <button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_kandang" data-kt-action="new_kandang"> --}}
						<button class="btn btn-sm btn-icon btn-action" data-kt-action="new_kandang">
						{{-- <button class="btn btn-sm btn-icon btn-action" data-kt-action="new_kandang" wire:click="showModal"> --}}
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-cube-3 fs-4 text-warning"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_stock',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_top_up_wallet">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
		</div>
		<!--end:Menu sub-->
	</div>
	<!--end:Menu item-->
	<!--begin:Menu item-->
	<div class="menu-item py-1">
		<!--begin:Menu content-->
		<div class="menu-content">
			<div class="separator separator-dashed"></div>
		</div>
		<!--end:Menu content-->
	</div>
	<!--end:Menu item-->
	<!--begin:Menu item-->
	<div data-kt-menu-trigger="click" class="menu-item menu-accordion show">
		<!--begin:Menu link-->
		<span class="menu-link">
			<span class="menu-title">{{ trans('menu.create_transaction_header',[],'id') }}</span>
			<span class="menu-arrow"></span>
		</span>
		<!--end:Menu link-->
		<!--begin:Menu sub-->
		<div class="menu-sub menu-sub-accordion menu-state-gray-900 menu-fit open">
			<!--begin:Menu item-->
			<div class="menu-item menu-accordion menu-fit">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-basket-ok fs-4 text-gray-700"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_purchasing',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item menu-accordion menu-fit">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-delivery-2 fs-4 text-gray-700"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_mutation_stock',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_top_up_wallet">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item menu-accordion menu-fit">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-notepad-edit fs-4 text-gray-700"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_mutation_livestock',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_bidding">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
		</div>
		<!--end:Menu sub-->
	</div>
	<!--end:Menu item-->
	@if($isOpen)
	{{-- <livewire:master-data.kandang-modal/> --}}
	{{-- @include('livewire.master-data.kandang-modal') --}}
	{{-- @include('modal') --}}
@endif

@include('modal')
</div>
<!--end::sidebar menu-->


@push('styles')
{{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet"> --}}

@endpush

@push('scripts')
{{-- <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script> --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.min.js"></script> --}}

	<script>
		document.addEventListener('livewire:init', function () {
                Livewire.on('openModal', function () {
                    $('#exampleModal').modal('show');
                });
            });

		// Function to auto-select unit in dropdown
		function selectClassification(selectedClassification) {
			$('#selectedFarm').val(selectedClassification).trigger('change'); // Assuming you are using a library like Select2 for the dropdown
		}

		// Function to fetch staff data and populate the dropdown
		var fetchStaffData = function (e) {
			// function fetchStaffData() {
				// Replace this URL with your actual API endpoint
				const apiUrl = '/apps/helpdesk/api/workorder/staff';
		
				fetch(apiUrl)
					.then(response => {
						if (!response.ok) {
							throw new Error(`HTTP error! Status: ${response.status}`);
						}
						return response.json();
					})
					.then(data => {
						// Get the Select2 instance
						const staffSelect = $('#staffSelect');
		
						// Clear existing options
						staffSelect.empty();
		
						// Add new options based on the fetched data
						data.forEach(staff => {
							const option = new Option(staff, staff);
							staffSelect.append(option);
						});
		
						// Trigger Select2 to update the UI
						staffSelect.trigger('change');
					})
					.catch(error => {
						console.error('Fetch error:', error.message);
					});
		}

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

                $.ajax({
                    url: '/api/v1/farms-list',
                    type: 'GET',
                    data: {
                        // id: incidentId,
                    },
                    success: function(response) {
						$('#kt_modal_new_kandang').modal('show'); // Assuming your modal has the ID 'kt_modal_master_kandang'

                        // $('#incident').val(response.data.title);
                        // $('#description').val(response.data.description);

                        // // Format date and set values for report_time and respond_date
                        // $('#report_time').val(formatDateTime(response.data.report_time));
                        // $('#response_time').val(formatDateTime(response.data.response_time));

                        // // Auto-select the unit-dropdown based on response.data.origin_unit
                        selectClassification(response.nama);
                        // selectLocation(response.data.location);
                        // selectSource(response.data.source);
                        // selectReporter(response.data.reportedBy);
                        // selectSeverity(response.data.severity);

                        // response.data.category.forEach(function(category) {
                        //     $('input[name="category[]"][value="' + category + '"]').prop('checked', true);
                        // });

                        // // Find the input element by its id
                        // var incidentInput = document.getElementById('incident');
                        // incidentInput.setAttribute('readonly', true);

                        // // Create a new hidden input element
                        // var hiddenInput = document.createElement("input");
                        // hiddenInput.type = "hidden";
                        // hiddenInput.id = "incident_id";
                        // hiddenInput.name = "incident_id";
                        // hiddenInput.className = "form-control form-control-solid";
                        // hiddenInput.value = response.data.id;
                        // hiddenInput.readOnly = true;

                        // // Find the form by its id and append the hidden input to it
                        // document.getElementById("kt_new_incident_form").appendChild(hiddenInput);
                    },
                    error: function (error) {
                        let errorMessage = "Sorry, looks like there are some errors detected, please try again.";

                        if (error.responseJSON && error.responseJSON.message) {
                            errorMessage = error.responseJSON.message;
                        }

                        Swal.fire({
                            text: errorMessage,
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });

                        console.error('Error deleting incident:', error);
                    }
                });
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
	</script>
@endpush
</div>
