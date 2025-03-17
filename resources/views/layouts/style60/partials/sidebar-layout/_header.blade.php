<!--begin::Header-->
<div id="kt_app_header" class="app-header">
	<!--begin::Header primary-->
	<div class="app-header-primary">
		<!--begin::Header primary container-->
		<div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_primary_container">
			<!--begin::Header primary wrapper-->
			<div class="d-flex flex-stack flex-grow-1">
				<div class="d-flex">
					<!--begin::Logo-->
					<div class="app-header-logo d-flex flex-center gap-2 me-lg-15">
						<!--begin::Sidebar toggle-->
						<button class="btn btn-icon btn-sm btn-custom d-flex d-lg-none ms-n2" id="kt_app_header_menu_toggle">
							<i class="ki-outline ki-abstract-14 fs-2"></i>
						</button>
						<!--end::Sidebar toggle-->
						<!--begin::Logo image-->
						<a href="index.html">
							<img alt="Logo" src="/assets/media/logos/logo.png" class="mh-85px"/>
							{{-- <img alt="Logo" src="/assets/media/logos/logo.png" class="mh-25px" /> --}}
						</a>
						<!--end::Logo image-->
					</div>
					<!--end::Logo-->
					<!--begin::Menu wrapper-->
					<div class="d-flex align-items-stretch" id="kt_app_header_menu_wrapper">
						@include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/header/_menu/_menu')
					</div>
					<!--end::Menu wrapper-->
				</div>
				@include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/header/_navbar')
			</div>
			<!--end::Header primary wrapper-->
		</div>
		<!--end::Header primary container-->
	</div>
	<!--end::Header primary-->
	<!--begin::Header secondary-->
	{{-- @include(config('settings.KT_THEME_LAYOUT_DIR').'/partials/sidebar-layout/header/_secondary') --}}
	<!--end::Header secondary-->
</div>
<!--end::Header-->