<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Reverb WebSocket Meta Configuration -->
    <meta name="reverb-key" content="{{ env('REVERB_APP_KEY', 'chatkey123456789') }}">
    <meta name="reverb-host" content="{{ env('REVERB_HOST', 'localhost') }}">
    <meta name="reverb-port" content="{{ env('REVERB_PORT', 8080) }}">
    <meta name="reverb-scheme" content="{{ env('REVERB_SCHEME', 'http') }}">

    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
        <meta name="user-name" content="{{ auth()->user()->name }}">
    @endauth
    <title>@yield('title', 'Simple Chat App')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('chat.index') }}">
                <i class="bi bi-chat-dots-fill fs-4"></i>
                <span>ChatApp</span>
            </a>

            <div class="d-flex align-items-center gap-3 ms-auto">
                @auth
                    <div class="d-flex align-items-center gap-2 text-white">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="avatar-img-sm rounded-circle border border-white" style="width: 32px; height: 32px; object-fit: cover;">
                        <span class="fw-semibold d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="d-none d-sm-inline">Logout</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-light">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div id="app-toast" class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toast-message">Notification</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Notification Sound & Toast Helpers -->
    <script>
        window.showToast = function(message, type = 'primary') {
            const toastEl = document.getElementById('app-toast');
            const toastBody = document.getElementById('toast-message');
            if (toastEl && toastBody) {
                toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
                toastBody.textContent = message;
                const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
                toast.show();
            }
        };

        window.playNotificationSound = function() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            } catch (e) {
                // AudioContext autoplay fallback
            }
        };
    </script>

    @stack('scripts')
</body>
</html>
