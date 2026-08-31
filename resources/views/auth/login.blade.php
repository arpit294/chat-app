@extends('layouts.app')

@section('title', 'Login - ChatApp')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="text-primary mb-2">
                            <i class="bi bi-chat-dots-fill display-5"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Welcome to ChatApp</h4>
                        <p class="text-muted small">Sign in to start messaging in real time</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Quick 1-Click Demo Login -->
                    <div class="mb-4 p-3 bg-light rounded border">
                        <div class="small fw-bold text-secondary mb-2">⚡ Quick 1-Click Demo Login:</div>
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 demo-login-btn" data-email="alice@example.com" data-pass="password">
                                    Alice Johnson
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-success btn-sm w-100 demo-login-btn" data-email="bob@example.com" data-pass="password">
                                    Bob Smith
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-dark btn-sm w-100 demo-login-btn" data-email="charlie@example.com" data-pass="password">
                                    Charlie Brown
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100 demo-login-btn" data-email="diana@example.com" data-pass="password">
                                    Diana Prince
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-center my-3 text-muted small">
                        <span>— or sign in with credentials —</span>
                    </div>

                    <!-- Login Form -->
                    <form action="{{ route('login') }}" method="POST" id="login-form">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', 'alice@example.com') }}" required placeholder="name@example.com">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold">Password</label>
                            <input type="password" class="form-control" id="password" name="password" value="password" required placeholder="••••••••">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                            <label class="form-check-label small text-muted" for="remember">Remember me</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>

                    <!-- Footer Link -->
                    <div class="text-center mt-4 pt-2 border-top">
                        <span class="text-muted small">Don't have an account?</span>
                        <a href="{{ route('register') }}" class="small fw-semibold ms-1">Register here</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.demo-login-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('email').value = this.getAttribute('data-email');
            document.getElementById('password').value = this.getAttribute('data-pass');
            document.getElementById('login-form').submit();
        });
    });
</script>
@endpush
@endsection
