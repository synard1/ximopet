<!DOCTYPE html>
<!--
Author: Keenthemes
Product Name: MetronicProduct Version: 8.2.5
Purchase: https://1.envato.market/EA4JP
Website: http://www.keenthemes.com
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
License: For each use you must have a valid license purchased only from above link in order to legally use the theme for your project.
-->
<html lang="en">
	<!--begin::Head-->
	<head>
		<base href=""/>
        <title>{{ config('app.name', 'Laravel') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta charset="utf-8"/>
        <meta name="description" content=""/>
        <meta name="keywords" content=""/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta property="og:locale" content="en_US"/>
        <meta property="og:type" content="article"/>
        <meta property="og:title" content=""/>
        <link rel="canonical" href="{{ url()->current() }}"/>
		<!--begin::Fonts(mandatory for all pages)-->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
		<!--end::Fonts-->
		<!--begin::Vendor Stylesheets(used for this page only)-->
		<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
		<!--end::Vendor Stylesheets-->
		<!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
		<link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style28.bundle.css" rel="stylesheet" type="text/css" />
		<!--end::Global Stylesheets Bundle-->
		<script>// Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body id="kt_app_body" data-kt-app-header-stacked="true" data-kt-app-header-primary-enabled="true" data-kt-app-header-secondary-enabled="true" data-kt-app-header-tertiary-enabled="true" data-kt-app-toolbar-enabled="true" class="app-default">
		<!--begin::Theme mode setup on page load-->
		<script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if ( localStorage.getItem("data-bs-theme") !== null ) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script>
		<!--end::Theme mode setup on page load-->
		<!--begin::App-->
		<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
			<!--begin::Page-->
			<div class="app-page flex-column flex-column-fluid" id="kt_app_page">
				<!--begin::Header-->
				<div id="kt_app_header" class="app-header">
					<!--begin::Header primary-->
					<div class="app-header-primary">
						<!--begin::Header primary container-->
						<div class="app-container container-xxl d-flex align-items-stretch justify-content-between py-2 py-lg-0" id="kt_app_header_primary_container">
							<!--begin::Primary header-->
							<div class="d-flex align-items-center flex-stack flex-row-fluid">
								<!--begin::Links-->
								<div class="d-flex gap-3 gap-lg-10">
									<!--begin::Link-->
									<a href="https://www.facebook.com/keenthemes/" class="d-flex align-items-center" target="_blank">
										<img src="assets/media/svg/brand-logos/facebook-5.svg" class="w-20px" alt="" />
										<span class="text-gray-700 text-hover-primary fw-bold fs-5 ps-2">Facebook</span>
									</a>
									<!--end::Link-->
									<!--begin::Link-->
									<a href="https://twitter.com/keenthemes" class="d-flex align-items-center" target="_blank">
										<img src="assets/media/svg/brand-logos/twitter-2.svg" class="w-20px" alt="" />
										<span class="text-gray-700 text-hover-primary fw-bold fs-5 ps-2">Twitter</span>
									</a>
									<!--end::Link-->
								</div>
								<!--end::Links-->
							</div>
							<!--end::Primary header-->
						</div>
						<!--end::Header primary container-->
					</div>
					<!--end::Header primary-->
					<!--begin::Header secondary-->
					<div class="app-header-secondary" data-kt-sticky="true" data-kt-sticky-name="app-header-secondary-sticky" data-kt-sticky-offset="{default: 'false', lg: '300px'}">
						<!--begin::Header secondary container-->
						<div class="app-container container-xxl d-flex align-items-stretch py-5 py-lg-0" id="kt_app_header_secondary_container">
							<!--begin::Header secondary-->
							<div class="app-navbar-item d-flex flex-stack flex-row-fluid">
								<!--begin::Logo wrapper-->
								<div class="d-flex align-items-center">
									<!--begin::Header mobile toggle-->
									<div class="d-flex align-items-center d-lg-none ms-n3 me-1" title="Show sidebar menu">
										<div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_header_menu_toggle">
											<i class="ki-duotone ki-abstract-14 fs-1">
												<span class="path1"></span>
												<span class="path2"></span>
											</i>
										</div>
									</div>
									<!--end::Header mobile toggle-->
									<!--begin::Logo-->
									<a href="index.html" class="d-flex align-items-center me-2">
										<img alt="Logo" src="assets/media/logos/demo-28-small.svg" class="h-20px d-sm-none d-inline theme-light-show" />
										<img alt="Logo" src="assets/media/logos/demo-28.svg" class="h-20px h-lg-25px theme-light-show d-none d-sm-inline" />
										<img alt="Logo" src="assets/media/logos/demo-28-dark.png" class="h-20px h-lg-25px theme-dark-show" />
									</a>
									<!--end::Logo-->
								</div>
								<!--end::Logo wrapper-->
								<!--begin::Wrapper-->
								<div class="d-flex align-items-center">
									<!--begin::Filter menu-->
									<select name="campaign-type" data-control="select2" data-hide-search="true" class="form-select form-select-lg w-90px w-sm-125px fs-6 text-gray-600 ps-4 border-gray-300">
										<option value="Twitter" selected="selected">ALL</option>
										<option value="Twitter">Books</option>
										<option value="Twitter">Computers</option>
										<option value="Twitter">Deals</option>
										<option value="Twitter">Movies</option>
									</select>
									<!--end::Filter menu-->
									<!--begin::Search-->
									<div class="d-flex align-items-center mx-3">
										<!--begin::Search-->
										<div id="kt_header_search" class="header-search d-flex align-items-center search-custom w-lg-400px" data-kt-search-keypress="true" data-kt-search-min-length="2" data-kt-search-enter="enter" data-kt-search-layout="menu" data-kt-search-responsive="lg" data-kt-menu-trigger="auto" data-kt-menu-permanent="true" data-kt-menu-placement="bottom-start">
											<!--begin::Tablet and mobile search toggle-->
											<div data-kt-search-element="toggle" class="search-toggle-mobile d-flex d-lg-none align-items-center">
												<div class="d-flex">
													<i class="ki-duotone ki-magnifier fs-1">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
												</div>
											</div>
											<!--end::Tablet and mobile search toggle-->
											<!--begin::Form(use d-none d-lg-block classes for responsive search)-->
											<form data-kt-search-element="form" class="d-none d-lg-block w-100 position-relative mb-5 mb-lg-0" autocomplete="off">
												<!--begin::Hidden input(Added to disable form autocomplete)-->
												<input type="hidden" />
												<!--end::Hidden input-->
												<!--begin::Icon-->
												<i class="ki-duotone ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5">
													<span class="path1"></span>
													<span class="path2"></span>
												</i>
												<!--end::Icon-->
												<!--begin::Input-->
												<input type="text" class="search-input form-control form-control-lg ps-13" name="search" value="" placeholder="Search..." data-kt-search-element="input" />
												<!--end::Input-->
												<!--begin::Spinner-->
												<span class="search-spinner position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-5" data-kt-search-element="spinner">
													<span class="spinner-border h-15px w-15px align-middle text-gray-500"></span>
												</span>
												<!--end::Spinner-->
												<!--begin::Reset-->
												<span class="search-reset btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-4" data-kt-search-element="clear">
													<i class="ki-duotone ki-cross fs-2 fs-lg-1 me-0">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
												</span>
												<!--end::Reset-->
											</form>
											<!--end::Form-->
										</div>
										<!--end::Search-->
									</div>
									<!--end::Search-->
								</div>
								<!--end::Wrapper-->
								<!--begin::Navbar-->
								<div class="app-navbar">

								</div>
							</div>
							<!--end::Secondary header-->
						</div>
						<!--end::Header secondary container-->
					</div>
					<!--end::Header secondary-->
					<!--begin::Header tertiary-->
					<div class="app-header-tertiary app-header-mobile-drawer" data-kt-drawer="true" data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-sticky="false" data-kt-sticky-name="app-header-tertiary-sticky" data-kt-sticky-offset="{default: 'false', lg: '300px'}" data-kt-swapper="true" data-kt-swapper-mode="append" data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header'}">
						<!--begin::Header tertiary container-->
						<div class="app-container container-xxl app-container-fit-mobile d-flex align-items-stretch" id="kt_app_header_tertiary_container">
							<!--begin::Menu wrapper-->
							<div class="app-header-menu d-flex align-items-stretch w-100">
								<!--begin::Menu-->
								<div class="menu menu-rounded menu-active-bg menu-state-primary menu-column menu-lg-row menu-title-gray-700 menu-icon-gray-500 menu-arrow-gray-500 menu-bullet-gray-500 my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0" id="kt_app_header_menu" data-kt-menu="true">
									<!--begin:Menu item-->
									<div class="menu-item here show">
										<span class="menu-link py-3">
											<span class="menu-title">Home</span>
										</span>
									</div>
									<!--end:Menu item-->
									<!--begin:Menu item-->
									<div class="menu-item">
										<span class="menu-link py-3">
											<span class="menu-title">Download</span>
										</span>
									</div>
									<!--end:Menu item-->
									<!--begin:Menu item-->
									<div class="menu-item">
										<span class="menu-link py-3">
											<span class="menu-title">Contact Us</span>
										</span>
									</div>
									<!--end:Menu item-->
								</div>
								<!--end::Menu-->
							</div>
							<!--end::Menu wrapper-->
						</div>
						<!--end::Header tertiary container-->
					</div>
					<!--end::Header tertiary-->
				</div>
				<!--end::Header-->
				<!--begin::Wrapper-->
				<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
					<!--begin::Wrapper container-->
					<div class="app-container container-xxl d-flex flex-row flex-column-fluid">
						<!--begin::Main-->
						<div class="app-main flex-column flex-row-fluid" id="kt_app_main">
							<!--begin::Content wrapper-->
							<div class="d-flex flex-column flex-column-fluid">
								<!--begin::Toolbar-->
								<div id="kt_app_toolbar" class="app-toolbar align-items-center justify-content-between py-4 py-lg-6">
									<!--begin::Toolbar wrapper-->
									<div class="d-flex flex-grow-1 flex-stack flex-wrap gap-2" id="kt_toolbar">
										<!--begin::Page title-->
										<div class="d-flex flex-column align-items-start me-3 gap-1 gap-lg-2">
											<!--begin::Title-->
											<h1 class="d-flex text-gray-900 fw-bold m-0 fs-3">Blog Home</h1>
											<!--end::Title-->
											<!--begin::Breadcrumb-->
											<ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
												<!--begin::Item-->
												<li class="breadcrumb-item text-gray-600">
													<a href="index.html" class="text-gray-600 text-hover-primary">Home</a>
												</li>
												<!--end::Item-->
												<!--begin::Item-->
												<li class="breadcrumb-item text-gray-600">Blog</li>
												<!--end::Item-->
												<!--begin::Item-->
												<li class="breadcrumb-item text-gray-500">Blog Home</li>
												<!--end::Item-->
											</ul>
											<!--end::Breadcrumb-->
										</div>
										<!--end::Page title-->

									</div>
									<!--end::Toolbar wrapper-->
								</div>
								<!--end::Toolbar-->
								<!--begin::Content-->
								<div id="kt_app_content" class="app-content flex-column-fluid">
									<!--begin::Home card-->
									<div class="card">
										<!--begin::Body-->
										<div class="card-body p-lg-20">
											<!--begin::Section-->
											<div class="mb-17">
												<!--begin::Title-->
												<h3 class="text-gray-900 mb-7">Latest Articles, News & Updates</h3>
												<!--end::Title-->
												<!--begin::Separator-->
												<div class="separator separator-dashed mb-9"></div>
												<!--end::Separator-->
												<!--begin::Row-->
												<div class="row">
													<!--begin::Col-->
													<div class="col-md-6">
														<!--begin::Feature post-->
														<div class="h-100 d-flex flex-column justify-content-between pe-lg-6 mb-lg-0 mb-10">
															<!--begin::Video-->
															<div class="mb-3">
																<iframe class="embed-responsive-item card-rounded h-275px w-100" src="https://www.youtube.com/embed/TWdDZYNqlg4" allowfullscreen="allowfullscreen"></iframe>
															</div>
															<!--end::Video-->
															<!--begin::Body-->
															<div class="mb-5">
																<!--begin::Title-->
																<a href="#" class="fs-2 text-gray-900 fw-bold text-hover-primary text-gray-900 lh-base">Admin Panel - How To Get Started Tutorial. 
																<br />Create easy customizable applications</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 text-gray-600 text-gray-900 mt-4">We’ve been focused on making the from v4 to v5 but we have also not been afraid to step away been focused on from v4 to v5 speaker approachable making focused a but from a step away afraid to step away been focused Writing a blog post is a little like driving; you can study the highway code (or read articles telling you how to write a blog post) for months, but nothing can prepare you for the real thing like getting behind the wheel</div>
																<!--end::Text-->
															</div>
															<!--end::Body-->
															<!--begin::Footer-->
															<div class="d-flex flex-stack flex-wrap">
																<!--begin::Item-->
																<div class="d-flex align-items-center pe-2">
																	<!--begin::Avatar-->
																	<div class="symbol symbol-35px symbol-circle me-3">
																		<img alt="" src="assets/media/avatars/300-9.jpg" />
																	</div>
																	<!--end::Avatar-->
																	<!--begin::Text-->
																	<div class="fs-5 fw-bold">
																		<a href="pages/user-profile/overview.html" class="text-gray-700 text-hover-primary">David Morgan</a>
																		<span class="text-muted">on Apr 27 2021</span>
																	</div>
																	<!--end::Text-->
																</div>
																<!--end::Item-->
																<!--begin::Label-->
																<span class="badge badge-light-primary fw-bold my-2">TUTORIALS</span>
																<!--end::Label-->
															</div>
															<!--end::Footer-->
														</div>
														<!--end::Feature post-->
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col-md-6 justify-content-between d-flex flex-column">
														<!--begin::Post-->
														<div class="ps-lg-6 mb-16 mt-md-0 mt-17">
															<!--begin::Body-->
															<div class="mb-6">
																<!--begin::Title-->
																<a href="#" class="fw-bold text-gray-900 mb-4 fs-2 lh-base text-hover-primary">Bootstrap Admin Theme - How To Get Started Tutorial. Create customizable applications</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 mt-4 text-gray-600 text-gray-900">We’ve been focused on making the from v4 to v5 a but we’ve also not been afraid to step away been focused on from v4 to v5 speaker approachable making focused</div>
																<!--end::Text-->
															</div>
															<!--end::Body-->
															<!--begin::Footer-->
															<div class="d-flex flex-stack flex-wrap">
																<!--begin::Item-->
																<div class="d-flex align-items-center pe-2">
																	<!--begin::Avatar-->
																	<div class="symbol symbol-35px symbol-circle me-3">
																		<img src="assets/media/avatars/300-20.jpg" class="" alt="" />
																	</div>
																	<!--end::Avatar-->
																	<!--begin::Text-->
																	<div class="fs-5 fw-bold">
																		<a href="pages/user-profile/overview.html" class="text-gray-700 text-hover-primary">Jane Miller</a>
																		<span class="text-muted">on Apr 27 2021</span>
																	</div>
																	<!--end::Text-->
																</div>
																<!--end::Item-->
																<!--begin::Label-->
																<span class="badge badge-light-info fw-bold my-2">BLOG</span>
																<!--end::Label-->
															</div>
															<!--end::Footer-->
														</div>
														<!--end::Post-->
														<!--begin::Post-->
														<div class="ps-lg-6 mb-16">
															<!--begin::Body-->
															<div class="mb-6">
																<!--begin::Title-->
																<a href="#" class="fw-bold text-gray-900 mb-4 fs-2 lh-base text-hover-primary">Angular Admin Theme - How To Get Started Tutorial.</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 mt-4 text-gray-600 text-gray-900">We’ve been focused on making the from v4 to v5 a but we’ve also not been afraid to step away</div>
																<!--end::Text-->
															</div>
															<!--end::Body-->
															<!--begin::Footer-->
															<div class="d-flex flex-stack flex-wrap">
																<!--begin::Item-->
																<div class="d-flex align-items-center pe-2">
																	<!--begin::Avatar-->
																	<div class="symbol symbol-35px symbol-circle me-3">
																		<img src="assets/media/avatars/300-9.jpg" class="" alt="" />
																	</div>
																	<!--end::Avatar-->
																	<!--begin::Text-->
																	<div class="fs-5 fw-bold">
																		<a href="pages/user-profile/overview.html" class="text-gray-700 text-hover-primary">Cris Morgan</a>
																		<span class="text-muted">on Mar 14 2021</span>
																	</div>
																	<!--end::Text-->
																</div>
																<!--end::Item-->
																<!--begin::Label-->
																<span class="badge badge-light-primary fw-bold my-2">TUTORIALS</span>
																<!--end::Label-->
															</div>
															<!--end::Footer-->
														</div>
														<!--end::Post-->
														<!--begin::Post-->
														<div class="ps-lg-6">
															<!--begin::Body-->
															<div class="mb-6">
																<!--begin::Title-->
																<a href="#" class="fw-bold text-gray-900 mb-4 fs-2 lh-base text-hover-primary">React Admin Theme - How To Get Started Tutorial. Create best applications</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 mt-4 text-gray-600 text-gray-900">We’ve been focused on making the from v4 to v5 but we’ve also not been afraid to step away been focused</div>
																<!--end::Text-->
															</div>
															<!--end::Body-->
															<!--begin::Footer-->
															<div class="d-flex flex-stack flex-wrap">
																<!--begin::Item-->
																<div class="d-flex align-items-center pe-2">
																	<!--begin::Avatar-->
																	<div class="symbol symbol-35px symbol-circle me-3">
																		<img src="assets/media/avatars/300-19.jpg" class="" alt="" />
																	</div>
																	<!--end::Avatar-->
																	<!--begin::Text-->
																	<div class="fs-5 fw-bold">
																		<a href="pages/user-profile/overview.html" class="text-gray-700 text-hover-primary">Cris Morgan</a>
																		<span class="text-muted">on Mar 14 2021</span>
																	</div>
																	<!--end::Text-->
																</div>
																<!--end::Item-->
																<!--begin::Label-->
																<span class="badge badge-light-warning fw-bold my-2">NEWS</span>
																<!--end::Label-->
															</div>
															<!--end::Footer-->
														</div>
														<!--end::Post-->
													</div>
													<!--end::Col-->
												</div>
												<!--begin::Row-->
											</div>
											<!--end::Section-->
											<!--begin::Section-->
											<div class="mb-17">
												<!--begin::Content-->
												<div class="d-flex flex-stack mb-5">
													<!--begin::Title-->
													<h3 class="text-gray-900">Video Tutorials</h3>
													<!--end::Title-->
													<!--begin::Link-->
													<a href="#" class="fs-6 fw-semibold link-primary">View All Videos</a>
													<!--end::Link-->
												</div>
												<!--end::Content-->
												<!--begin::Separator-->
												<div class="separator separator-dashed mb-9"></div>
												<!--end::Separator-->
												<!--begin::Row-->
												<div class="row g-10">
													<!--begin::Col-->
													<div class="col-md-4">
														<!--begin::Feature post-->
														<div class="card-xl-stretch me-md-6">
															<!--begin::Image-->
															<a class="d-block bgi-no-repeat bgi-size-cover bgi-position-center card-rounded position-relative min-h-175px mb-5" style="background-image:url('assets/media/stock/600x400/img-73.jpg')" data-fslightbox="lightbox-video-tutorials" href="https://www.youtube.com/embed/btornGtLwIo">
																<img src="assets/media/svg/misc/video-play.svg" class="position-absolute top-50 start-50 translate-middle" alt="" />
															</a>
															<!--end::Image-->
															<!--begin::Body-->
															<div class="m-0">
																<!--begin::Title-->
																<a href="pages/user-profile/overview.html" class="fs-4 text-gray-900 fw-bold text-hover-primary text-gray-900 lh-base">Admin Panel - How To Started the Dashboard Tutorial</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 text-gray-600 text-gray-900 my-4">We’ve been focused on making a the from also not been afraid to and step away been focused create eye</div>
																<!--end::Text-->
																<!--begin::Content-->
																<div class="fs-6 fw-bold">
																	<!--begin::Author-->
																	<a href="pages/user-profile/overview.html" class="text-gray-700 text-hover-primary">Jane Miller</a>
																	<!--end::Author-->
																	<!--begin::Date-->
																	<span class="text-muted">on Mar 21 2021</span>
																	<!--end::Date-->
																</div>
																<!--end::Content-->
															</div>
															<!--end::Body-->
														</div>
														<!--end::Feature post-->
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col-md-4">
														<!--begin::Feature post-->
														<div class="card-xl-stretch mx-md-3">
															<!--begin::Image-->
															<a class="d-block bgi-no-repeat bgi-size-cover bgi-position-center card-rounded position-relative min-h-175px mb-5" style="background-image:url('assets/media/stock/600x400/img-74.jpg')" data-fslightbox="lightbox-video-tutorials" href="https://www.youtube.com/embed/btornGtLwIo">
																<img src="assets/media/svg/misc/video-play.svg" class="position-absolute top-50 start-50 translate-middle" alt="" />
															</a>
															<!--end::Image-->
															<!--begin::Body-->
															<div class="m-0">
																<!--begin::Title-->
																<a href="pages/user-profile/overview.html" class="fs-4 text-gray-900 fw-bold text-hover-primary text-gray-900 lh-base">Admin Panel - How To Started the Dashboard Tutorial</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 text-gray-600 text-gray-900 my-4">We’ve been focused on making the from v4 to v5 but we have also not been afraid to step away been focused</div>
																<!--end::Text-->
																<!--begin::Content-->
																<div class="fs-6 fw-bold">
																	<!--begin::Author-->
																	<a href="pages/user-profile/overview.html" class="text-gray-700 text-hover-primary">Cris Morgan</a>
																	<!--end::Author-->
																	<!--begin::Date-->
																	<span class="text-muted">on Apr 14 2021</span>
																	<!--end::Date-->
																</div>
																<!--end::Content-->
															</div>
															<!--end::Body-->
														</div>
														<!--end::Feature post-->
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col-md-4">
														<!--begin::Feature post-->
														<div class="card-xl-stretch ms-md-6">
															<!--begin::Image-->
															<a class="d-block bgi-no-repeat bgi-size-cover bgi-position-center card-rounded position-relative min-h-175px mb-5" style="background-image:url('assets/media/stock/600x400/img-47.jpg')" data-fslightbox="lightbox-video-tutorials" href="https://www.youtube.com/embed/TWdDZYNqlg4">
																<img src="assets/media/svg/misc/video-play.svg" class="position-absolute top-50 start-50 translate-middle" alt="" />
															</a>
															<!--end::Image-->
															<!--begin::Body-->
															<div class="m-0">
																<!--begin::Title-->
																<a href="pages/user-profile/overview.html" class="fs-4 text-gray-900 fw-bold text-hover-primary text-gray-900 lh-base">Admin Panel - How To Started the Dashboard Tutorial</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 text-gray-600 text-gray-900 my-4">We’ve been focused on making the from v4 to v5 but we’ve also not been afraid to step away been focused</div>
																<!--end::Text-->
																<!--begin::Content-->
																<div class="fs-6 fw-bold">
																	<!--begin::Author-->
																	<a href="pages/user-profile/overview.html" class="text-gray-700 text-hover-primary">Carles Nilson</a>
																	<!--end::Author-->
																	<!--begin::Date-->
																	<span class="text-muted">on May 14 2021</span>
																	<!--end::Date-->
																</div>
																<!--end::Content-->
															</div>
															<!--end::Body-->
														</div>
														<!--end::Feature post-->
													</div>
													<!--end::Col-->
												</div>
												<!--end::Row-->
											</div>
											<!--end::Section-->
											<!--begin::Section-->
											<div class="mb-17">
												<!--begin::Content-->
												<div class="d-flex flex-stack mb-5">
													<!--begin::Title-->
													<h3 class="text-gray-900">Hottest Bundles</h3>
													<!--end::Title-->
													<!--begin::Link-->
													<a href="#" class="fs-6 fw-semibold link-primary">View All Offers</a>
													<!--end::Link-->
												</div>
												<!--end::Content-->
												<!--begin::Separator-->
												<div class="separator separator-dashed mb-9"></div>
												<!--end::Separator-->
												<!--begin::Row-->
												<div class="row g-10">
													<!--begin::Col-->
													<div class="col-md-4">
														<!--begin::Hot sales post-->
														<div class="card-xl-stretch me-md-6">
															<!--begin::Overlay-->
															<a class="d-block overlay" data-fslightbox="lightbox-hot-sales" href="assets/media/stock/600x400/img-23.jpg">
																<!--begin::Image-->
																<div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover card-rounded min-h-175px" style="background-image:url('assets/media/stock/600x400/img-23.jpg')"></div>
																<!--end::Image-->
																<!--begin::Action-->
																<div class="overlay-layer card-rounded bg-dark bg-opacity-25">
																	<i class="ki-duotone ki-eye fs-2x text-white">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																	</i>
																</div>
																<!--end::Action-->
															</a>
															<!--end::Overlay-->
															<!--begin::Body-->
															<div class="mt-5">
																<!--begin::Title-->
																<a href="#" class="fs-4 text-gray-900 fw-bold text-hover-primary text-gray-900 lh-base">25 Products Mega Bundle with 50% off discount amazing</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 text-gray-600 text-gray-900 mt-3">We’ve been focused on making a the from also not been eye</div>
																<!--end::Text-->
																<!--begin::Text-->
																<div class="fs-6 fw-bold mt-5 d-flex flex-stack">
																	<!--begin::Label-->
																	<span class="badge border border-dashed fs-2 fw-bold text-gray-900 p-2">
																	<span class="fs-6 fw-semibold text-gray-500">$</span>28</span>
																	<!--end::Label-->
																	<!--begin::Action-->
																	<a href="#" class="btn btn-sm btn-primary">Purchase</a>
																	<!--end::Action-->
																</div>
																<!--end::Text-->
															</div>
															<!--end::Body-->
														</div>
														<!--end::Hot sales post-->
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col-md-4">
														<!--begin::Hot sales post-->
														<div class="card-xl-stretch mx-md-3">
															<!--begin::Overlay-->
															<a class="d-block overlay" data-fslightbox="lightbox-hot-sales" href="assets/media/stock/600x600/img-14.jpg">
																<!--begin::Image-->
																<div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover card-rounded min-h-175px" style="background-image:url('assets/media/stock/600x600/img-14.jpg')"></div>
																<!--end::Image-->
																<!--begin::Action-->
																<div class="overlay-layer card-rounded bg-dark bg-opacity-25">
																	<i class="ki-duotone ki-eye fs-2x text-white">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																	</i>
																</div>
																<!--end::Action-->
															</a>
															<!--end::Overlay-->
															<!--begin::Body-->
															<div class="mt-5">
																<!--begin::Title-->
																<a href="#" class="fs-4 text-gray-900 fw-bold text-hover-primary text-gray-900 lh-base">25 Products Mega Bundle with 50% off discount amazing</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 text-gray-600 text-gray-900 mt-3">We’ve been focused on making a the from also not been eye</div>
																<!--end::Text-->
																<!--begin::Text-->
																<div class="fs-6 fw-bold mt-5 d-flex flex-stack">
																	<!--begin::Label-->
																	<span class="badge border border-dashed fs-2 fw-bold text-gray-900 p-2">
																	<span class="fs-6 fw-semibold text-gray-500">$</span>27</span>
																	<!--end::Label-->
																	<!--begin::Action-->
																	<a href="#" class="btn btn-sm btn-primary">Purchase</a>
																	<!--end::Action-->
																</div>
																<!--end::Text-->
															</div>
															<!--end::Body-->
														</div>
														<!--end::Hot sales post-->
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col-md-4">
														<!--begin::Hot sales post-->
														<div class="card-xl-stretch ms-md-6">
															<!--begin::Overlay-->
															<a class="d-block overlay" data-fslightbox="lightbox-hot-sales" href="assets/media/stock/600x400/img-71.jpg">
																<!--begin::Image-->
																<div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover card-rounded min-h-175px" style="background-image:url('assets/media/stock/600x400/img-71.jpg')"></div>
																<!--end::Image-->
																<!--begin::Action-->
																<div class="overlay-layer card-rounded bg-dark bg-opacity-25">
																	<i class="ki-duotone ki-eye fs-2x text-white">
																		<span class="path1"></span>
																		<span class="path2"></span>
																		<span class="path3"></span>
																	</i>
																</div>
																<!--end::Action-->
															</a>
															<!--end::Overlay-->
															<!--begin::Body-->
															<div class="mt-5">
																<!--begin::Title-->
																<a href="#" class="fs-4 text-gray-900 fw-bold text-hover-primary text-gray-900 lh-base">25 Products Mega Bundle with 50% off discount amazing</a>
																<!--end::Title-->
																<!--begin::Text-->
																<div class="fw-semibold fs-5 text-gray-600 text-gray-900 mt-3">We’ve been focused on making a the from also not been eye</div>
																<!--end::Text-->
																<!--begin::Text-->
																<div class="fs-6 fw-bold mt-5 d-flex flex-stack">
																	<!--begin::Label-->
																	<span class="badge border border-dashed fs-2 fw-bold text-gray-900 p-2">
																	<span class="fs-6 fw-semibold text-gray-500">$</span>25</span>
																	<!--end::Label-->
																	<!--begin::Action-->
																	<a href="#" class="btn btn-sm btn-primary">Purchase</a>
																	<!--end::Action-->
																</div>
																<!--end::Text-->
															</div>
															<!--end::Body-->
														</div>
														<!--end::Hot sales post-->
													</div>
													<!--end::Col-->
												</div>
												<!--end::Row-->
											</div>
											<!--end::Section-->
											<!--begin::latest instagram-->
											<div class="">
												<!--begin::Section-->
												<div class="m-0">
													<!--begin::Content-->
													<div class="d-flex flex-stack">
														<!--begin::Title-->
														<h3 class="text-gray-900">Latest Instagram Posts</h3>
														<!--end::Title-->
														<!--begin::Link-->
														<a href="#" class="fs-6 fw-semibold link-primary">View Instagram</a>
														<!--end::Link-->
													</div>
													<!--end::Content-->
													<!--begin::Separator-->
													<div class="separator separator-dashed border-gray-300 mb-9 mt-5"></div>
													<!--end::Separator-->
												</div>
												<!--end::Section-->
												<!--begin::Row-->
												<div class="row g-10 row-cols-2 row-cols-lg-5">
													<!--begin::Col-->
													<div class="col">
														<!--begin::Overlay-->
														<a class="d-block overlay" data-fslightbox="lightbox-hot-sales" href="assets/media/stock/900x600/16.jpg">
															<!--begin::Image-->
															<div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover card-rounded min-h-175px" style="background-image:url('assets/media/stock/900x600/16.jpg')"></div>
															<!--end::Image-->
															<!--begin::Action-->
															<div class="overlay-layer card-rounded bg-dark bg-opacity-25">
																<i class="ki-duotone ki-eye fs-3x text-white">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																</i>
															</div>
															<!--end::Action-->
														</a>
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col">
														<!--begin::Overlay-->
														<a class="d-block overlay" data-fslightbox="lightbox-hot-sales" href="assets/media/stock/900x600/13.jpg">
															<!--begin::Image-->
															<div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover card-rounded min-h-175px" style="background-image:url('assets/media/stock/900x600/13.jpg')"></div>
															<!--end::Image-->
															<!--begin::Action-->
															<div class="overlay-layer card-rounded bg-dark bg-opacity-25">
																<i class="ki-duotone ki-eye fs-3x text-white">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																</i>
															</div>
															<!--end::Action-->
														</a>
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col">
														<!--begin::Overlay-->
														<a class="d-block overlay" data-fslightbox="lightbox-hot-sales" href="assets/media/stock/900x600/19.jpg">
															<!--begin::Image-->
															<div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover card-rounded min-h-175px" style="background-image:url('assets/media/stock/900x600/19.jpg')"></div>
															<!--end::Image-->
															<!--begin::Action-->
															<div class="overlay-layer card-rounded bg-dark bg-opacity-25">
																<i class="ki-duotone ki-eye fs-3x text-white">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																</i>
															</div>
															<!--end::Action-->
														</a>
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col">
														<!--begin::Overlay-->
														<a class="d-block overlay" data-fslightbox="lightbox-hot-sales" href="assets/media/stock/900x600/15.jpg">
															<!--begin::Image-->
															<div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover card-rounded min-h-175px" style="background-image:url('assets/media/stock/900x600/15.jpg')"></div>
															<!--end::Image-->
															<!--begin::Action-->
															<div class="overlay-layer card-rounded bg-dark bg-opacity-25">
																<i class="ki-duotone ki-eye fs-3x text-white">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																</i>
															</div>
															<!--end::Action-->
														</a>
													</div>
													<!--end::Col-->
													<!--begin::Col-->
													<div class="col">
														<!--begin::Overlay-->
														<a class="d-block overlay" data-fslightbox="lightbox-hot-sales" href="assets/media/stock/900x600/12.jpg">
															<!--begin::Image-->
															<div class="overlay-wrapper bgi-no-repeat bgi-position-center bgi-size-cover card-rounded min-h-175px" style="background-image:url('assets/media/stock/900x600/12.jpg')"></div>
															<!--end::Image-->
															<!--begin::Action-->
															<div class="overlay-layer card-rounded bg-dark bg-opacity-25">
																<i class="ki-duotone ki-eye fs-3x text-white">
																	<span class="path1"></span>
																	<span class="path2"></span>
																	<span class="path3"></span>
																</i>
															</div>
															<!--end::Action-->
														</a>
													</div>
													<!--end::Col-->
												</div>
												<!--begin::Row-->
											</div>
											<!--end::latest instagram-->
										</div>
										<!--end::Body-->
									</div>
									<!--end::Home card-->
								</div>
								<!--end::Content-->
							</div>
							<!--end::Content wrapper-->
							<!--begin::Footer-->
							<div id="kt_app_footer" class="app-footer align-items-center justify-content-between">
								<!--begin::Copyright-->
								<div class="text-gray-900 order-2 order-md-1">
									<span class="text-muted fw-semibold me-1">2024&copy;</span>
									<a href="https://keenthemes.com" target="_blank" class="text-gray-800 text-hover-primary">Keenthemes</a>
								</div>
								<!--end::Copyright-->
								<!--begin::Menu-->
								<ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
									<li class="menu-item">
										<a href="https://keenthemes.com" target="_blank" class="menu-link px-2">About</a>
									</li>
									<li class="menu-item">
										<a href="https://devs.keenthemes.com" target="_blank" class="menu-link px-2">Support</a>
									</li>
									<li class="menu-item">
										<a href="https://1.envato.market/EA4JP" target="_blank" class="menu-link px-2">Purchase</a>
									</li>
								</ul>
								<!--end::Menu-->
							</div>
							<!--end::Footer-->
						</div>
						<!--end:::Main-->
					</div>
					<!--end::Wrapper container-->
				</div>
				<!--end::Wrapper-->
			</div>
			<!--end::Page-->
		</div>
		<!--end::App-->
		<!--end::Drawers-->
		<!--begin::Scrolltop-->
		<div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
			<i class="ki-duotone ki-arrow-up">
				<span class="path1"></span>
				<span class="path2"></span>
			</i>
		</div>
		<!--end::Scrolltop-->
		<!--begin::Javascript-->
		<script>var hostUrl = "assets/";</script>
		<!--begin::Global Javascript Bundle(mandatory for all pages)-->
		<script src="assets/plugins/global/plugins.bundle.js"></script>
		<script src="assets/js/scripts.bundle.js"></script>
		<!--end::Global Javascript Bundle-->
		<!--begin::Vendors Javascript(used for this page only)-->
		<script src="assets/plugins/custom/fslightbox/fslightbox.bundle.js"></script>
		<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
		<!--end::Vendors Javascript-->
		<!--end::Javascript-->
	</body>
	<!--end::Body-->
</html>