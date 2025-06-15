<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! printHtmlAttributes('html') !!}>
<!--begin::Head-->

<head>
    <base href="" />
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-debug" content="{{ config('app.debug') ? 'true' : 'false' }}">
    <meta name="app-env" content="{{ config('app.env') }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    <meta charset="utf-8" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="" />
    <link rel="canonical" href="{{ url()->current() }}" />

    {!! includeFavicon() !!}

    <!--begin::Fonts-->
    {!! includeFonts() !!}
    <!--end::Fonts-->

    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    @foreach(getGlobalAssets('css') as $path)
    {!! sprintf('
    <link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Global Stylesheets Bundle-->

    <!--begin::Vendor Stylesheets(used by this page)-->
    @foreach(getVendors('css') as $path)
    {!! sprintf('
    <link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Vendor Stylesheets-->

    <!--begin::Custom Stylesheets(optional)-->
    @foreach(getCustomCss() as $path)
    {!! sprintf('
    <link rel="stylesheet" href="%s">', asset($path)) !!}
    @endforeach
    <!--end::Custom Stylesheets-->

    @livewireStyles
</head>
<!--end::Head-->

<!--begin::Body-->

<body {!! printHtmlClasses('body') !!} {!! printHtmlAttributes('body') !!}>

    @include('partials/theme-mode/_init')

    @yield('content')

    <!--begin::Javascript-->
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    @foreach(getGlobalAssets() as $path)
    {!! sprintf('<script src="%s"></script>', asset($path)) !!}
    @endforeach
    <script src="{{ asset('assets/js/security-protection.js') }}"></script>
    <script src="{{ asset('assets/js/security-blacklist-notification.js') }}"></script>
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

    <!--begin::Laravel User Setup-->
    {{-- <script>
        // Set Laravel user info for Echo private channels BEFORE loading Echo
        window.Laravel = window.Laravel || {};
        @auth
        window.Laravel.user = {
            id: {{ auth()->id() }},
            name: "{{ auth()->user()->name }}",
            email: "{{ auth()->user()->email }}"
        };
        console.log('✅ Laravel user info set:', window.Laravel.user);
        @else
        window.Laravel.user = null;
        console.log('👤 No authenticated user');
        @endauth
    </script> --}}
    <!--end::Laravel User Setup-->

    <!--begin::Browser Notification System-->
    {{-- <script src="{{ asset('assets/js/browser-notification.js') }}"></script> --}}
    <!--end::Browser Notification System-->

    <!--begin::Laravel Echo Setup-->
    {{-- <script src="{{ asset('assets/js/echo-setup.js') }}"></script> --}}
    <!--end::Laravel Echo Setup-->

    <!--begin::Laravel Echo App Bundle-->
    {{-- <script src="{{ asset('assets/js/app.bundle.js') }}"></script> --}}
    <!--end::Laravel Echo App Bundle-->
    <!--end::Javascript-->

    <script>
        console.log('confirm');
        document.addEventListener('livewire:init', () => {
        Livewire.on('success', (message) => {
            toastr.success(message);
        });
        Livewire.on('error', (message) => {
            toastr.error(message);
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

        Livewire.on('confirm', (message, params) => {
            console.log('confirm');
            Swal.fire({
                title: params.title,
                text: params.text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: params.confirmButtonText,
                cancelButtonText: params.cancelButtonText,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch(params.onConfirmed, params.params);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Livewire.dispatch(params.onCancelled);
                }
            });
        });
    });
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
</body>
<!--end::Body-->

</html>