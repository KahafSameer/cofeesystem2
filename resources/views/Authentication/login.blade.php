@extends('Authentication.layouts.master')

@section('content')
<section class="d-flex align-items-center justify-content-center mt-4">
    <div class="login-wrapper d-flex flex-row overflow-hidden rounded-4 shadow-lg"
        style="max-width: 900px; background-color: rgba(69, 46, 31, 0.9);">
        <!-- Left: Image -->
        <div class="d-none d-md-block" style="width: 50%;">
            <img src="{{ asset('admin/images/coffee cup.jpg') }}" alt="Coffee Cup"
                 class="img-fluid h-100" style="object-fit: cover; opacity: 0.6;">
        </div>
        <div class="col-12 text-center d-md-none mb-4">
            <img src="{{ asset('admin/images/coffee cup.jpg') }}" alt="Coffee Cup" style="width: 80px; opacity: 0.6;">
        </div>

        <!-- Right: Login Form -->
        <div class="p-5 text-white" style="width: 50%;">
            <h3 class="fw-bold text-center mb-4">Login</h3>

            <!-- Social Login -->
            <div class="d-flex justify-content-center mb-3">
                <p class="lead fw-normal mb-0 me-3">Sign in with</p>
                <a href="{{url('/auth/google/redirect')}}" class="btn btn-light btn-sm mx-1">
                    <i class="fab fa-google"></i>
                </a>
                <a href="{{url('/auth/facebook/redirect')}}" class="btn btn-primary btn-sm mx-1">
                    <i class="fab fa-facebook-f"></i>
                </a>
            </div>
            @if (session('alert'))
                <div class="text-danger text-center alert alert-{{ session('alert.type') }}">
                    {{ session('alert.message') }}
                </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label text-white">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="Enter your email" required>
                    </div>
                    @error('email')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label text-white">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               placeholder="Enter your password" required>
                    </div>
                    @error('password')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-light rounded-pill fw-bold shadow" id="loginBtn">
                        Login
                    </button>
                </div>

                <p class="small text-center mt-3">Don't have an account?
                    <a href="{{ route('userRegister') }}" class="text-warning fw-bold">Register</a>
                </p>
            </form>
        </div>
    </div>
</section>

@endsection

