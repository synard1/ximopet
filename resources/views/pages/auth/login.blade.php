<x-auth-layout>

    <!--begin::Form-->
    <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" method="POST"
        data-kt-redirect-url="{{ route('dashboard') }}" action="{{ route('login') }}">
        @csrf
        <!--begin::Heading-->
        <div class="text-center mb-11">
            <!--begin::Title-->
            <h1 class="text-gray-900 fw-bolder mb-3">
                Sign In
            </h1>
            <!--end::Title-->
        </div>
        <!--begin::Heading-->

        @if(session('error'))
        <!--begin::Alert-->
        <div
            class="alert alert-{{ session('error')['type'] === 'error' ? 'danger' : 'warning' }} d-flex align-items-center p-5 mb-10">
            <!--begin::Icon-->
            <span
                class="svg-icon svg-icon-2hx svg-icon-{{ session('error')['type'] === 'error' ? 'danger' : 'warning' }} me-4">
                @if(session('error')['type'] === 'error')
                <i class="ki-duotone ki-cross-circle fs-2x">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                @else
                <i class="ki-duotone ki-shield-tick fs-2x">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                @endif
            </span>
            <!--end::Icon-->

            <!--begin::Wrapper-->
            <div class="d-flex flex-column">
                <!--begin::Title-->
                <h4 class="mb-1 text-{{ session('error')['type'] === 'error' ? 'danger' : 'warning' }}">
                    {{ session('error')['title'] }}
                </h4>
                <!--end::Title-->

                <!--begin::Content-->
                <span>{{ session('error')['message'] }}</span>
                <!--end::Content-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Alert-->
        @endif

        <!--begin::Input group--->
        <div class="fv-row mb-8">
            <!--begin::Email-->
            <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent"
                value="{{ old('email') }}" />
            <!--end::Email-->
            @error('email')
            <div class="fv-plugins-message-container invalid-feedback">
                <div data-field="email" data-validator="notEmpty">{{ $message }}</div>
            </div>
            @enderror
        </div>
        <!--end::Input group--->

        <!--end::Input group--->
        <div class="fv-row mb-3">
            <!--begin::Password-->
            <input type="password" placeholder="Password" name="password" autocomplete="off"
                class="form-control bg-transparent" value="" />
            <!--end::Password-->
            @error('password')
            <div class="fv-plugins-message-container invalid-feedback">
                <div data-field="password" data-validator="notEmpty">{{ $message }}</div>
            </div>
            @enderror
        </div>
        <!--end::Input group--->

        {{--
        <!--begin::Wrapper-->
        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
            <div></div>

            <!--begin::Link-->
            <a href="{{ route('password.request') }}" class="link-primary">
                Forgot Password ?
            </a>
            <!--end::Link-->
        </div>
        <!--end::Wrapper--> --}}

        <!--begin::Submit button-->
        <div class="d-grid mb-10">
            <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                @include('partials/general/_button-indicator', ['label' => 'Sign In'])
            </button>
        </div>
        <!--end::Submit button-->

        {{--
        <!--begin::Sign up-->
        <div class="text-gray-500 text-center fw-semibold fs-6">
            Not a Member yet?

            <a href="{{ route('register') }}" class="link-primary">
                Sign up
            </a>
        </div>
        <!--end::Sign up--> --}}
    </form>
    <!--end::Form-->

</x-auth-layout>