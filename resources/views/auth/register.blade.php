@extends('layouts.app')

@section('title', 'Register - ChatApp')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="text-primary mb-2">
                            <i class="bi bi-person-plus-fill display-5"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Create an Account</h4>
                        <p class="text-muted small">Join ChatApp to start messaging</p>
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

                    <!-- Registration Form -->
                    <form action="{{ route('register') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. John Doe">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required placeholder="name@example.com">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Minimum 8 characters">
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label small fw-semibold">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Register
                        </button>
                    </form>

                    <!-- Footer Link -->
                    <div class="text-center mt-4 pt-2 border-top">
                        <span class="text-muted small">Already have an account?</span>
                        <a href="{{ route('login') }}" class="small fw-semibold ms-1">Sign in here</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
