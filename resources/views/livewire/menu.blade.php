@php
$currentUrl = request()->url();
@endphp

<div>
    <!--begin::sidebar menu-->
    <div class="menu-sidebar menu menu-fit menu-column menu-rounded menu-title-gray-700 menu-icon-gray-700 menu-arrow-gray-700 fw-semibold fs-6 align-items-stretch flex-grow-1"
        id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="true">
        @include('layouts.style60.partials.sidebar-layout.menu._menu')
    </div>

    @push('scripts')
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
		var fetchFarm = function (e) {
			// Replace this URL with your actual API endpoint
			const apiUrl = '/api/v1/farms-list';

			fetch(apiUrl, {
				headers: {
                                'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
			})
				.then(response => {
					if (!response.ok) {
						throw new Error(`HTTP error! Status: ${response.status}`);
					}
					return response.json();
				})
				.then(data => {
					if (data.length > 0) { // Check if data array is not empty
						const farmSelect = $('#farmSelect');
						farmSelect.empty();

						// Add static "Select Farm" option
						const defaultOption = new Option("=== Pilih Farm ===", "", true, true);
						farmSelect.append(defaultOption);

						data.forEach(farm => {
							const option = new Option(farm.nama, farm.id);
							option.setAttribute('data-farm-id', farm.id);
							farmSelect.append(option);
						});

						farmSelect.trigger('change');
						$('#kt_modal_new_kandang').modal('show'); // Show the main modal
					} else {
						$('#noFarm_modal').modal('show'); // Show the "no farm" modal
					}
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
					fetchFarm();
				});
			});

			// Optionally, listen for changes in the Select2 dropdown
			$('#farmSelect').on('select2:select', function (e) {
				const selectedData = e.params.data;
				const farmId = selectedData.element.getAttribute('data-farm-id');
				console.log('Selected Farm ID:', farmId);
				// Now you have the farmId, which you can use in your application
			});
		});

		document.querySelectorAll('[data-kt-action="new_user"]').forEach(function (element) {
			element.addEventListener('click', function () {
				// Simulate delete request -- for demo purpose only
				Swal.fire({
					html: `Load Data Form`,
					icon: "info",
					buttonsStyling: false,
					showConfirmButton: false,
					timer: 2000
				}).then(function () {
					$('#kt_modal_user').modal('show');
				});
			});



		});
    </script>

    @endpush
</div>