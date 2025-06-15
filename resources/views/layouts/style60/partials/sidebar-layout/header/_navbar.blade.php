<!--begin::Navbar-->
<div class="app-navbar flex-shrink-0 gap-2">
    <!--begin::User menu-->
    <div class="app-navbar-item ms-1">
        <!--begin::Menu wrapper-->
        <div class="cursor-pointer symbol position-relative symbol-35px"
            data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
            data-kt-menu-placement="bottom-end">
            @if(Auth::user()->profile_photo_url)
            <img src="{{ \Auth::user()->profile_photo_url }}" class="rounded-3" alt="user" />
            @else
            <div
                class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', Auth::user()->name) }}">
                {{ substr(Auth::user()->name, 0, 1) }}
            </div>
            @endif
        </div>
        @include('partials/menus/_user-account-menu')
        <!--end::Menu wrapper-->
    </div>
    <!--end::User menu-->
    {{--
    <!--begin::Header menu toggle-->
    <div class="app-navbar-item d-lg-none" title="Show header menu">
        <button class="btn btn-sm btn-icon btn-custom h-35px w-35px" id="kt_header_secondary_mobile_toggle">
            <i class="ki-outline ki-element-4 fs-2"></i>
        </button>
    </div>
    <!--end::Header menu toggle--> --}}
    {{--
    <!--begin::Header menu toggle-->
    <div class="app-navbar-item d-lg-none me-n3" title="Show header menu">
        <button class="btn btn-sm btn-icon btn-custom h-35px w-35px" id="kt_app_sidebar_mobile_toggle">
            <i class="ki-outline ki-setting-3 fs-2"></i>
        </button>
    </div>
    <!--end::Header menu toggle--> --}}
</div>
<!--end::Navbar-->