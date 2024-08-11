<div>

	<!--begin::sidebar menu-->
	<div class="menu-sidebar menu menu-fit menu-column menu-rounded menu-title-gray-700 menu-icon-gray-700 menu-arrow-gray-700 fw-semibold fs-6 align-items-stretch flex-grow-1"
		id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="true">
		@if(request()->is('admin*'))
			@if(auth()->user()->hasRole(['SuperAdmin','Administrator']))
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
									{{--
									<livewire:master-data._create_supplier /> --}}
									{{-- <button class="btn btn-sm btn-icon btn-action" wire:click="openModal">
										<i class="ki-outline ki-plus fs-4"></i>
									</button> --}}
									<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
										data-bs-target="#kt_modal_new_supplier">
										{{-- <button class="btn btn-sm btn-icon btn-action"> --}}
											<i class="ki-outline ki-plus fs-4"></i>
										</button>
										{{-- <a href="{{ route('supplier.create') }}" class="btn btn-light-primary"><i
												class="ki-outline ki-plus fs-4"></i></a> --}}

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
									<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
										data-bs-target="#kt_modal_new_customer">
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
									<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
										data-bs-target="#kt_modal_new_farm">
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
									{{-- <button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
										data-bs-target="#kt_modal_new_kandang" data-kt-action="new_kandang"> --}}
										<button class="btn btn-sm btn-icon btn-action" data-kt-action="new_kandang">
											{{-- <button class="btn btn-sm btn-icon btn-action" data-kt-action="new_kandang"
												wire:click="showModal"> --}}
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
									<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
										data-bs-target="#kt_modal_new_stok">
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
			@endif
		@else
		@if(auth()->user()->hasRole(['SuperAdmin','Administrator']))
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
							<i class="ki-outline ki-user fs-4 text-success"></i>
						</span>
						<span class="menu-title">{{ trans('menu.create_users',[],'id') }}</span>
						<span class="menu-badge">
							{{--
							<livewire:master-data._create_supplier /> --}}
							{{-- <button class="btn btn-sm btn-icon btn-action" wire:click="openModal">
								<i class="ki-outline ki-plus fs-4"></i>
							</button> --}}
							{{-- <button class="btn btn-sm btn-icon btn-action" data-kt-action="new_user"> --}}
								<button class="btn btn-sm btn-icon btn-action">
									<a href="/master-data/users"><i class="ki-outline ki-plus fs-4"></i></a>
								</button>
								{{-- <a href="{{ route('supplier.create') }}" class="btn btn-light-primary"><i
										class="ki-outline ki-plus fs-4"></i></a> --}}

						</span>
					</span>
					<!--end:Menu link-->
				</div>
				<!--end:Menu item-->
			</div>
			<!--end:Menu sub-->
		</div>
		<!--end:Menu item-->

		@endif
		@if(auth()->user()->hasRole(['Supervisor']))
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
							{{--
							<livewire:master-data._create_supplier /> --}}
							{{-- <button class="btn btn-sm btn-icon btn-action" wire:click="openModal">
								<i class="ki-outline ki-plus fs-4"></i>
							</button> --}}
							<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_new_supplier">
								{{-- <button class="btn btn-sm btn-icon btn-action"> --}}
									<i class="ki-outline ki-plus fs-4"></i>
								</button>
								{{-- <a href="{{ route('supplier.create') }}" class="btn btn-light-primary"><i
										class="ki-outline ki-plus fs-4"></i></a> --}}

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
							<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_new_customer">
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
							<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_new_farm">
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
							{{-- <button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_new_kandang" data-kt-action="new_kandang"> --}}
								<button class="btn btn-sm btn-icon btn-action" data-kt-action="new_kandang">
									{{-- <button class="btn btn-sm btn-icon btn-action" data-kt-action="new_kandang"
										wire:click="showModal"> --}}
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
							<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_new_stok">
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
		@endif
		<!--begin:Menu item-->
		<div class="menu-item py-1">
			<!--begin:Menu content-->
			<div class="menu-content">
				<div class="separator separator-dashed"></div>
			</div>
			<!--end:Menu content-->
		</div>
		<!--end:Menu item-->
		@can('create transaksi')
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
				@if(auth()->user()->hasRole(['Supervisor']))
				<!--begin:Menu item-->
				<div class="menu-item menu-accordion menu-fit">
					<!--begin:Menu link-->
					<span class="menu-link">
						<span class="menu-icon">
							<i class="ki-outline ki-basket-ok fs-4 text-gray-700"></i>
						</span>
						<span class="menu-title">{{ trans('menu.create_purchasing_doc',[],'id') }}</span>
						<span class="menu-badge">
							<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_new_doc">
								<i class="ki-outline ki-plus fs-4"></i>
							</button>
						</span>
					</span>
					<!--end:Menu link-->
				</div>
				<!--end:Menu item-->
				@endif
				@if(auth()->user()->hasRole(['Operator']))
				<!--begin:Menu item-->
				<div class="menu-item menu-accordion menu-fit">
					<!--begin:Menu link-->
					<span class="menu-link">
						<span class="menu-icon">
							<i class="ki-outline ki-basket-ok fs-4 text-gray-700"></i>
						</span>
						<span class="menu-title">{{ trans('menu.create_purchasing',[],'id') }}</span>
						<span class="menu-badge">
							<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_new_target">
								<a href="/transaksi/stoks"><i class="ki-outline ki-plus fs-4"></i></a>
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
							<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_top_up_wallet">
								<a href="/transaksi/pakai"><i class="ki-outline ki-plus fs-4"></i></a>
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
							<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal"
								data-bs-target="#kt_modal_bidding">
								<i class="ki-outline ki-plus fs-4"></i>
							</button>
						</span>
					</span>
					<!--end:Menu link-->
				</div>
				<!--end:Menu item-->
				@endif
			</div>
			<!--end:Menu sub-->
		</div>
		<!--end:Menu item-->
		@endcan
		
		@endif

		@if($isOpen)
		{{--
		<livewire:master-data.kandang-modal /> --}}
		{{-- @include('livewire.master-data.kandang-modal') --}}
		{{-- @include('modal') --}}
		@endif

		{{-- @include('modal') --}}
	</div>
	<!--end::sidebar menu-->


	@push('styles')
	{{--
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet"> --}}

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
		var fetchFarm = function (e) {
			// Replace this URL with your actual API endpoint
			const apiUrl = '/api/v1/farms-list';
	
			fetch(apiUrl)
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