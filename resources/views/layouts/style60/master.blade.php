<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! printHtmlAttributes('html') !!}>
<!--begin::Head-->
<head>
    <base href=""/>
    <title>{{ config('app.name', 'Laravel') }} | {{ config('app.info') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta property="og:locale" content="en_US"/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content=""/>
    <link rel="canonical" href="{{ url()->current() }}"/>

    {!! includeFavicon() !!}

    <!--begin::Fonts-->
    {!! includeFonts() !!}
    <!--end::Fonts-->

    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    @foreach(getGlobalAssets('css') as $path)
        {!! sprintf('<link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Global Stylesheets Bundle-->

    <!--begin::Vendor Stylesheets(used by this page)-->
    @foreach(getVendors('css') as $path)
        {!! sprintf('<link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Vendor Stylesheets-->

    <!--begin::Custom Stylesheets(optional)-->
    @foreach(getCustomCss() as $path)
        {!! sprintf('<link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Custom Stylesheets-->

    @livewireStyles
    @stack('styles')

</head>
<!--end::Head-->

<!--begin::Body-->
@php
    $currentRoute = Route::current();
    $isAuthRoute = $currentRoute && $currentRoute->middleware('auth:sanctum');
@endphp

    {{-- @if($isAuthRoute)
    <body  id="kt_body"  class="app-blank" >

    @else
    <body id="kt_app_body" data-kt-app-page-loading-enabled="true" data-kt-app-page-loading="on" data-kt-app-header-fixed="true" data-kt-app-header-fixed-mobile="true" data-kt-app-header-stacked="true" data-kt-app-header-primary-enabled="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
    @endif --}}
    <body id="kt_app_body" data-kt-app-page-loading-enabled="true" data-kt-app-page-loading="on" data-kt-app-header-fixed="true" data-kt-app-header-fixed-mobile="true" data-kt-app-header-stacked="true" data-kt-app-header-primary-enabled="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">



@include('partials/theme-mode/_init')

@yield('content')

<!--begin::Javascript-->
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
@foreach(getGlobalAssets() as $path)
    {!! sprintf('<script src="%s"></script>', asset($path)) !!}
@endforeach
<!--end::Global Javascript Bundle-->

<!--begin::Vendors Javascript(used by this page)-->
@foreach(getVendors('js') as $path)
    {!! sprintf('<script src="%s"></script>', asset($path)) !!}
@endforeach
<!--end::Vendors Javascript-->

<!--begin::Custom Javascript(optional)-->
@foreach(getCustomJs() as $path)
    {!! sprintf('<script src="%s"></script>', asset($path)) !!}
@endforeach
<!--end::Custom Javascript-->
@stack('scripts')
<!--end::Javascript-->

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('success', (message) => {
            toastr.success(message);

            // Close any open modal dialogs
             $('.modal').modal('hide');  // Assuming you're using Bootstrap's modal

             // Reload DataTables
            $('.table').each(function() {
                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().ajax.reload();
                }
            });
        });
        Livewire.on('error', (message) => {
            toastr.error(message);
        });

        Livewire.on('info', (message) => {
            toastr.info(message);
        });

        Livewire.on('swal', (message, icon, confirmButtonText) => {
            if (typeof icon === 'undefined') {
                icon = 'success';
            }
            if (typeof confirmButtonText === 'undefined') {
                confirmButtonText = 'Ok, got it!';
            }
            Swal.fire({
                text: message,
                icon: icon,
                buttonsStyling: false,
                confirmButtonText: confirmButtonText,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
        });
    });

    const showLoadingSpinner = () => {
        const loadingEl = document.createElement("div");
        document.body.append(loadingEl);
        loadingEl.classList.add("page-loader");
        loadingEl.innerHTML = `
            <span class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </span>
        `;
        KTApp.showPageLoading();
        setTimeout(() => {
            KTApp.hidePageLoading();
            loadingEl.remove();
        }, 3000);
    };

    // Example: Setting up global AJAX headers
    $.ajaxSetup({
         headers: {
             'Authorization': @json('Bearer ' . session('auth_token')),
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         }
     });

     // Define global JavaScript variables
    window.AuthToken = "{{ session('auth_token') }}";
</script>

@livewireScripts
<!-- Check if Livewire is running -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Livewire !== 'undefined') {
            console.log('Livewire is running');
        } else {
            console.log('Livewire is not defined');
        }
    });
</script>
<script type="text/javascript">
    // Define global JavaScript variables
    window.AuthToken = "{{ session('auth_token') }}";
</script>
</body>
<!--end::Body-->

</html>
