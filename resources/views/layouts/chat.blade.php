<!DOCTYPE html>
<html lang=\"{{ str_replace('_', '-', app()->getLocale()) }}\" x-data=\"{ theme: $persist('auto') }\" :data-bs-theme=\"theme === 'auto' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : theme\">
<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <meta name=\"csrf-token\" content=\"{{ csrf_token() }}\">

    <title>{{ config('app.name', 'Laravel') }} - AI Chat</title>

    <!-- Fonts -->
    <link rel=\"preconnect\" href=\"https://fonts.bunny.net\">
    <link href=\"https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap\" rel=\"stylesheet\" />

    <!-- Font Awesome -->
    <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css\" rel=\"stylesheet\">

    <!-- Bootstrap -->
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css\" rel=\"stylesheet\">

    <!-- SweetAlert2 -->
    <link href=\"https://cdn.jsdelivr.net/npm/sweetalert2@11.4.0/dist/sweetalert2.min.css\" rel=\"stylesheet\">

    <!-- AI Chat Styles (only when chat is enabled) -->
    @if(config('chat.system.enabled', false))
        <link href=\"{{ asset('css/ai-chat.css') }}\" rel=\"stylesheet\">
        <link href=\"{{ asset('css/ai-chat-button-fix-direct.css') }}\" rel=\"stylesheet\">
        <link href=\"{{ asset('css/chat-bubble-fix.css') }}\" rel=\"stylesheet\">
    @endif

    <!-- Custom Styles -->
    @stack('styles')

    <!-- Alpine.js -->
    <script defer src=\"https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js\"></script>

    <!-- AI Chat Configuration -->
    <script>
        @include('partials.ai-chat-config')
    </script>

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Direct button styling with highest priority -->
    <style>
        /* Direct high-priority button styling */
        html body .ai-chat-widget .card-header button,
        html body .ai-chat-widget .card-header .btn,
        html body .ai-chat-widget .card-header .btn-sm,
        html body .ai-chat-widget .card-header .btn-outline-light {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.4) !important;
            border: 2px solid white !important;
            font-weight: bold !important;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.5) !important;
            padding: 0.25rem 0.75rem !important;
            box-shadow: 0 0 5px rgba(255, 255, 255, 0.3) !important;
        }
    </style>
</head>
<body class=\"font-sans antialiased\">
    <div class=\"min-h-screen bg-gray-100\">
        <!-- Navigation -->
        @if(isset($showNavigation) && $showNavigation)
            <nav class=\"navbar navbar-expand-lg navbar-dark bg-primary\">
                <div class=\"container-fluid\">
                    <a class=\"navbar-brand\" href=\"{{ route('dashboard') ?? '/' }}\">
                        <i class=\"fas fa-robot me-2\"></i>
                        {{ config('app.name', 'Laravel') }}
                    </a>

                    <div class=\"navbar-nav ms-auto\">
                        @auth
                            <div class=\"nav-item dropdown\">
                                <a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\">
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class=\"dropdown-menu\">
                                    <li>
                                        <form method=\"POST\" action=\"{{ route('logout') ?? '#' }}\">
                                            @csrf
                                            <button type=\"submit\" class=\"dropdown-item\">
                                                <i class=\"fas fa-sign-out-alt me-2\"></i>
                                                {{ __('Log Out') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @endauth
                    </div>
                </div>
            </nav>
        @endif

        <!-- Page Content -->
        <main class=\"{{ isset($containerClass) ? $containerClass : 'container-fluid' }} py-4\">
            @yield('content')
        </main>

        <!-- AI Chat Widget (only when chat is enabled) -->
        @auth
            @if(config('chat.system.enabled', false))
                @livewire('ai-chat-widget')
            @endif
        @endauth

        <!-- Flash Messages -->
        @if(session('success'))
            <div class=\"toast-container position-fixed bottom-0 end-0 p-3\">
                <div class=\"toast show\" role=\"alert\">
                    <div class=\"toast-header\">
                        <i class=\"fas fa-check-circle text-success me-2\"></i>
                        <strong class=\"me-auto\">Success</strong>
                        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"toast\"></button>
                    </div>
                    <div class=\"toast-body\">
                        {{ session('success') }}
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class=\"toast-container position-fixed bottom-0 end-0 p-3\">
                <div class=\"toast show\" role=\"alert\">
                    <div class=\"toast-header\">
                        <i class=\"fas fa-exclamation-triangle text-danger me-2\"></i>
                        <strong class=\"me-auto\">Error</strong>
                        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"toast\"></button>
                    </div>
                    <div class=\"toast-body\">
                        {{ session('error') }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Scripts -->
    <!-- Bootstrap -->
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js\"></script>

    <!-- SweetAlert2 -->
    <script src=\"https://cdn.jsdelivr.net/npm/sweetalert2@11.4.0/dist/sweetalert2.all.min.js\"></script>

    <!-- AI Chat JavaScript (only when chat is enabled) -->
    @if(config('chat.system.enabled', false))
        <script src="{{ asset('js/ai-chat.js') }}"></script>
        <script src="{{ asset('js/ai-chat-state.js') }}"></script>
        <script src="{{ asset('js/chat-form-debug.js') }}"></script>
        <script src="{{ asset('js/ai-chat-debug.js') }}"></script>
        <script src="{{ asset('js/ai-chat-error-tracker.js') }}"></script>
    @endif

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Custom Scripts -->
    @stack('scripts')

    <!-- Initialize toast notifications -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap toasts
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            var toastList = toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000
                });
            });

            // Auto-hide toasts after delay
            toastList.forEach(function(toast) {
                toast.show();
            });
        });
    </script>
</body>
</html>
