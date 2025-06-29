<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
	data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
	data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_header_menu_toggle">

	<!--begin::Sidebar toggle button-->
	<div class="sidebar-toggle-btn" id="kt_sidebar_toggle">
		<i class="ki-duotone ki-arrow-left fs-2" id="sidebar_toggle_icon">
			<span class="path1"></span>
			<span class="path2"></span>
		</i>
	</div>
	<!--end::Sidebar toggle button-->

	<!--begin::Sidebar wrapper-->
	<div id="kt_app_sidebar_wrapper" class="flex-grow-1 hover-scroll-y mt-9 mb-5 px-2 mx-3 ms-lg-7 me-lg-5"
		data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto"
		data-kt-scroll-dependencies="{default: false, lg: '#kt_app_header'}" data-kt-scroll-offset="5px"
		style="height: 826px;">
		{{-- @include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/sidebar/_filter') --}}
		{{-- @include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/sidebar/_menu') --}}

		<livewire:menu />


		{{-- {{ request()->url() }}

		@if (request()->is('/'))
		<p>You are on the home page</p>
		@elseif (request()->is('products/*'))
		<p>You are viewing a product page</p>
		@elseif (request()->is('admin*'))
		<p>You are in the admin area</p>
		@else
		<p>You are on another page</p>
		@endif --}}
	</div>
	<!--end::Sidebar wrapper-->
</div>
<!--end::Sidebar-->