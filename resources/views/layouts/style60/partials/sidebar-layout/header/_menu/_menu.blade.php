@php
$currentUrl = request()->url();
$isDashboard = $currentUrl === url('/') || $currentUrl === url('/dashboard');

$generalMenu = [
'rekanan/suppliers',
'rekanan/customers',
'inventory/docs',
'inventory/stocks',
'users',
'user/roles',
'user/permissions',

];

$peternakanMenu = [
'data/farms',
'data/kandangs',
'data/ternaks',
'data/stoks',
'master-data/farms',
'master-data/kandangs',
'ternak/afkir',
'ternak/jual',
'ternak/mati',
];

$transaksiMenu = [
'pembelian/doc',
'pembelian/pakan',
'pembelian/ovk',
'pembelian/stock',
'penjualan/ternak',
'transaksi/penjualan',
'transaksi/harian',
'transaksi/stoks',
];

$reportMenu = [
'reports/performa',
'reports/penjualan',
];

function isActive($routes) {
return collect($routes)->contains(function ($route) {
return request()->is($route);
}) ? 'active' : '';
}
@endphp

<!--begin::Menu wrapper-->
<div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true"
	data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}"
	data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}"
	data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true"
	data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
	data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_menu_wrapper'}">
	<!--begin::Menu-->
	<div class="menu menu-rounded menu-column menu-lg-row menu-active-bg menu-title-gray-700 menu-state-gray-900 menu-icon-gray-500 menu-arrow-gray-500 menu-state-icon-primary menu-state-bullet-primary fw-semibold fs-6 align-items-stretch my-5 my-lg-0 px-2 px-lg-0"
		id="#kt_app_header_menu" data-kt-menu="true">
		<!--begin:Menu item-->
		<div class="menu-item menu-lg-down-accordion me-0 me-lg-2 {{ $isDashboard ? 'here show menu-here-bg' : '' }}">
			<!--begin:Menu link-->
			<a href="/" class="menu-link">
				<span class="menu-title">Dashboards</span>
				<span class="menu-arrow d-lg-none"></span>
			</a>
			<!--end:Menu link-->
		</div>
		<!--end:Menu item-->
		<!--begin:Menu item-->
		<div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
			class="menu-item menu-lg-down-accordion me-0 me-lg-2 {{ !$isDashboard && (isActive($generalMenu) || isActive($peternakanMenu) || isActive($transaksiMenu)) ? 'here show menu-here-bg' : '' }}">

			<!--begin:Menu link-->
			<span class="menu-link">
				<span class="menu-title">Pages</span>
				<span class="menu-arrow d-lg-none"></span>
			</span>
			<!--end:Menu link-->
			<!--begin:Menu sub-->
			<div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-600px">
				<!--begin:Pages menu-->
				<div class="menu-active-bg px-4 px-lg-0">
					<!--begin:Tabs nav-->
					<div class="d-flex w-100 overflow-auto">
						<ul class="nav nav-stretch nav-line-tabs fw-bold fs-6 p-0 p-lg-10 flex-nowrap flex-grow-1"
							role="tablist">
							<!--begin:Nav item-->
							<li class="nav-item mx-lg-1" role="presentation">
								<a class="nav-link py-3 py-lg-6 text-active-primary {{ $isDashboard || isActive($generalMenu) || isActive($reportMenu) ? 'active' : '' }}"
									href="#" data-bs-toggle="tab" data-bs-target="#kt_app_header_menu_pages_pages"
									aria-selected="true" role="tab">General</a>
							</li>
							<!--end:Nav item-->
							<!--begin:Nav item-->
							<li class="nav-item mx-lg-1" role="presentation">
								<a class="nav-link py-3 py-lg-6 text-active-primary {{ isActive($peternakanMenu) }}"
									href="#" data-bs-toggle="tab" data-bs-target="#peternakan" aria-selected="false"
									tabindex="-1" role="tab">Peternakan</a>
							</li>
							<li class="nav-item mx-lg-1" role="presentation">
								<a class="nav-link py-3 py-lg-6 text-active-primary {{ isActive($transaksiMenu) }}"
									href="#" data-bs-toggle="tab" data-bs-target="#transaksi" aria-selected="false"
									tabindex="-1" role="tab">Transaksi</a>
							</li>
							<!--end:Nav item-->
						</ul>
					</div>
					<!--end:Tabs nav-->
					<!--begin:Tab content-->
					<div class="tab-content py-4 py-lg-8 px-lg-7">
						<!--begin:Tab pane-->
						<div class="tab-pane w-lg-1000px {{ $isDashboard || isActive($generalMenu) || isActive($reportMenu) ? 'active' : ''}}"
							id="kt_app_header_menu_pages_pages" role="tabpanel">
							<!--begin:Row-->
							<div class="row">
								<!--begin:Col-->
								<div class="col-lg-8">
									<!--begin:Row-->
									<div class="row">
										<!--begin:Col-->
										<div class="col-lg-3 mb-6 mb-lg-0">
											<!--begin:Menu heading-->
											<h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">User Profile</h4>
											<!--end:Menu heading-->
											<!--begin:Menu item-->
											<div class="menu-item p-0 m-0">
												<!--begin:Menu link-->
												<a href="#" class="menu-link">
													<span class="menu-title">Overview</span>
												</a>
												<!--end:Menu link-->
											</div>
											<!--end:Menu item-->
											<!--begin:Menu item-->
											<div class="menu-item p-0 m-0">
												<!--begin:Menu link-->
												<a href="#" class="menu-link">
													<span class="menu-title">Activity</span>
												</a>
												<!--end:Menu link-->
											</div>
											<!--end:Menu item-->
										</div>
										<!--end:Col-->

										@can('read supplier management' , 'read customer management')
											<!--begin:Col-->
										<div class="col-lg-3 mb-6 mb-lg-0">
											<!--begin:Menu section-->
											<div class="mb-6">
												<!--begin:Menu heading-->
												<h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">Rekanan</h4>
												<!--end:Menu heading-->
												<!--begin:Menu item-->
												<div class="menu-item p-0 m-0">
													<!--begin:Menu link-->
													<a href="/rekanan/suppliers"
														class="menu-link {{ request()->is('rekanan/suppliers') ? 'active' : '' }}">
														<span class="menu-title">Supplier</span>
													</a>
													<!--end:Menu link-->
												</div>
												<!--end:Menu item-->
												<!--begin:Menu item-->
												<div class="menu-item p-0 m-0">
													<!--begin:Menu link-->
													<a href="/rekanan/customers"
														class="menu-link {{ request()->is('rekanan/customers') ? 'active' : '' }}">
														<span class="menu-title">Pembeli</span>
													</a>
													<!--end:Menu link-->
												</div>
												<!--end:Menu item-->
											</div>
											<!--end:Menu section-->
											<!--begin:Menu section-->
											<div class="mb-0">
												<!--begin:Menu heading-->
												<h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">Inventory</h4>
												<!--end:Menu heading-->
												<!--begin:Menu item-->
												<div class="menu-item p-0 m-0">
													<!--begin:Menu link-->
													<a href="/inventory/docs"
														class="menu-link {{ request()->is('inventory/docs') ? 'active' : '' }}">
														<span class="menu-title">DOC</span>
													</a>
													<!--end:Menu link-->
												</div>
												<!--end:Menu item-->
												<!--begin:Menu item-->
												<div class="menu-item p-0 m-0">
													<!--begin:Menu link-->
													<a href="/inventory/stocks"
														class="menu-link {{ request()->is('inventory/stocks') ? 'active' : '' }}">
														<span class="menu-title">Stock</span>
													</a>
													<!--end:Menu link-->
												</div>
												<!--end:Menu item-->
												<!--begin:Menu item-->
												<div class="menu-item p-0 m-0">
													<!--begin:Menu link-->
													<a href="#" class="menu-link">
														<span class="menu-title">Storage</span>
													</a>
													<!--end:Menu link-->
												</div>
												<!--end:Menu item-->
											</div>
											<!--end:Menu section-->
										</div>
										<!--end:Col-->
										@endcan
										

										@can('read user management')
											<!--begin:Col-->
										<div class="col-lg-3 mb-6 mb-lg-0">
											<!--begin:Menu section-->
											<div class="mb-6">
												<!--begin:Menu heading-->
												<h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">User</h4>
												<!--end:Menu heading-->
												<!--begin:Menu item-->
												<div class="menu-item p-0 m-0">
													<!--begin:Menu link-->
													<a href="/users"
														class="menu-link {{ request()->is('rekanan/suppliers') ? 'active' : '' }}">
														<span class="menu-title">User List</span>
													</a>
													<!--end:Menu link-->
												</div>
												<!--end:Menu item-->
												<!--begin:Menu item-->
												<div class="menu-item p-0 m-0">
													<!--begin:Menu link-->
													<a href="/user/roles"
														class="menu-link {{ request()->is('user/roles') ? 'active' : '' }}">
														<span class="menu-title">User Role</span>
													</a>
													<!--end:Menu link-->
												</div>
												<!--end:Menu item-->
												@if(auth()->user()->hasRole(['SuperAdmin']))
												<!--begin:Menu item-->
												<div class="menu-item p-0 m-0">
													<!--begin:Menu link-->
													<a href="/user/permissions"
														class="menu-link {{ request()->is('user/permissions') ? 'active' : '' }}">
														<span class="menu-title">User Permission</span>
													</a>
													<!--end:Menu link-->
												</div>
												<!--end:Menu item-->
												@endif
											</div>
											<!--end:Menu section-->
										</div>
										<!--end:Col-->
										@endcan
										

									</div>
									<!--end:Row-->
								</div>
								<!--end:Col-->
							</div>
							<!--end:Row-->
						</div>
						<!--end:Tab pane-->
						<!--begin:Tab pane-->
						<div class="tab-pane w-lg-1000px {{ isActive($peternakanMenu) }}" id="peternakan"
							role="tabpanel">
							<!--begin:Row-->
							<div class="row">
								<!--begin:Col-->
								<div class="col-lg-3 mb-6 mb-lg-0">
									<!--begin:Menu section-->
									<div class="mb-6">
										<!--begin:Menu heading-->
										<h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">Farm</h4>
										<!--end:Menu heading-->
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/data/farms"
												class="menu-link {{ request()->is('data/farms') || request()->is('master-data/farms') ? 'active' : '' }}">
												<span class="menu-title">Data Farm</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/data/kandangs"
												class="menu-link {{ request()->is('data/kandangs') || request()->is('master-data/kandangs') ? 'active' : '' }}">
												<span class="menu-title">Data Kandang</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/data/ternaks"
												class="menu-link {{ request()->is('data/ternaks') || request()->is('master-data/ternaks') ? 'active' : '' }}">
												<span class="menu-title">Data Ternak</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/data/stoks"
												class="menu-link {{ request()->is('data/stoks') || request()->is('master-data/stoks') ? 'active' : '' }}">
												<span class="menu-title">Data Stoks</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->
									</div>
									<!--end:Menu section-->
								</div>
								<!--end:Col-->
							</div>
							<!--end:Row-->
						</div>
						<!--end:Tab pane-->
						<!--begin:Tab pane-->
						<div class="tab-pane w-lg-1000px {{ isActive($transaksiMenu) }}" id="transaksi" role="tabpanel">
							<!--begin:Row-->
							<div class="row">
								<!--begin:Col-->
								<div class="col-lg-2 mb-4 mb-lg-0">
									<!--begin:Menu section-->
									<div class="mb-6">
										<!--begin:Menu heading-->
										<h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">Pembelian</h4>
										<!--end:Menu heading-->
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/pembelian/doc"
												class="menu-link {{ request()->is('pembelian/doc') ? 'active' : '' }}">
												<span class="menu-title">DOC</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->
										@if(auth()->user()->hasRole(['Operator']))
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/pembelian/stock"
												class="menu-link {{ request()->is('pembelian/stock') || request()->is('transaksi/stoks') ? 'active' : '' }}">
												<span class="menu-title">Stock</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->

										@else
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/pembelian/pakan"
												class="menu-link {{ request()->is('pembelian/pakan') ? 'active' : '' }}">
												<span class="menu-title">Pakan</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/pembelian/ovk"
												class="menu-link {{ request()->is('pembelian/ovk') ? 'active' : '' }}">
												<span class="menu-title">OVK</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->

										@endif
									</div>
									<!--end:Menu section-->
								</div>
								<!--end:Col-->
								<!--begin:Col-->
								<div class="col-lg-2 mb-4 mb-lg-0">
									<!--begin:Menu section-->
									<div class="mb-6">
										<!--begin:Menu heading-->
										<h4 class="fs-6 fs-lg-4 fw-bold mb-3 ms-4">Penjualan</h4>
										<!--end:Menu heading-->
										<!--begin:Menu item-->
										<div class="menu-item p-0 m-0">
											<!--begin:Menu link-->
											<a href="/transaksi/penjualan"
												class="menu-link {{ request()->is('transaksi/penjualan') ? 'active' : '' }}">
												<span class="menu-title">Ternak</span>
											</a>
											<!--end:Menu link-->
										</div>
										<!--end:Menu item-->
									</div>
									<!--end:Menu section-->
								</div>
								<!--end:Col-->
								<!--begin:Col-->
								<div class="col-lg-2 mb-4 mb-lg-0">
									<!--begin:Menu item-->
									<div class="menu-item p-0 m-0">
										<!--begin:Menu link-->
										<a href="/transaksi/harian"
											class="menu-link {{ request()->is('transaksi/harian') ? 'active' : '' }}">
											<span class="menu-title">Harian</span>
										</a>
										<!--end:Menu link-->
									</div>
									<!--end:Menu item-->
								</div>
								<!--end:Col-->
							</div>
							<!--end:Row-->
						</div>
						<!--end:Tab pane-->
					</div>
					<!--end:Tab content-->
				</div>
				<!--end:Pages menu-->
			</div>
			<!--end:Menu sub-->
		</div>
		<!--end:Menu item-->

		@can('read report management')
		<!--begin:Menu item-->
		<div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
			class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2 {{ !$isDashboard && (isActive($reportMenu)) ? 'here show menu-here-bg' : '' }}">
			<!--begin:Menu link--><span class="menu-link"><span class="menu-title">Reports</span><span
					class="menu-arrow d-lg-none"></span></span>
			<!--end:Menu link-->
			<!--begin:Menu sub-->
			<div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px" style="">
				<!--begin:Menu item-->
				<div class="menu-item">
					<!--begin:Menu link--><a class="menu-link {{ request()->is('reports/penjualan') ? 'active' : '' }}"
						href="/reports/penjualan"
						target="_self"><span class="menu-icon"><i class="ki-outline ki-code fs-2"></i></span><span
							class="menu-title">Penjualan</span></a>
					<!--end:Menu link-->
				</div>
				<!--end:Menu item-->
				<!--begin:Menu item-->
				<div class="menu-item">
					<!--begin:Menu link--><a class="menu-link {{ request()->is('reports/performa') ? 'active' : '' }}"
						href="/reports/performa"
						target="_self"><span class="menu-icon"><i class="ki-outline ki-code fs-2"></i></span><span
							class="menu-title">Performa</span></a>
					<!--end:Menu link-->
				</div>
				<!--end:Menu item-->
			</div>
			<!--end:Menu sub-->
		</div>
		<!--end:Menu item-->
			
		@endcan
		

	</div>
	<!--end::Menu-->
</div>
<!--end::Menu wrapper-->