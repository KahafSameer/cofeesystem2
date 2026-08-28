@extends('Authentication.layouts.master')
@section('content')
<section class="d-flex align-items-center justify-content-center">
    <div class="register-wrapper d-flex flex-row overflow-hidden rounded-4 shadow-lg"
        style="max-width: 800px; background-color: rgba(69, 46, 31, 0.9);">

        <div class="d-none d-md-block" style="width: 50%;">
            <img src="{{ asset('admin/images/coffee.jpg') }}" alt="Coffee Cup"
                 class="img-fluid h-80" style="object-fit: cover; opacity: 0.6;">
        </div>


        <div class="p-3 text-white" style="width: 60%;">
            <h3 class="fw-bold text-center text-white">Register</h3>
            <form action="{{ route('register')}}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="name" class="form-label text-white">Full Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Enter your full name" required>
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label text-white">Email address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" placeholder="Enter your email" required>
                    @error('name')
                    <small class="invalid-feedback">{{ $message }}</small>
                @enderror
                </div>
                <div class="mb-4">
                    <label for="phone" class="form-label text-white">Phone Number</label>
                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="Enter your phone number" required>
                    @error('name')
                    <small class="invalid-feedback">{{ $message }}</small>
                @enderror
                </div>
                {{-- <div class="mb-4">
                    <label for="password" class="form-label text-white">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Enter your password" >
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="confirmPassword" class="form-label text-white">Confirm Password</label>
                    <input type="password" class="form-control @error('confirmPassword') is-invalid @enderror" name="confirmPassword" placeholder="Confirm your password" >
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div> --}}


                <div class="form-group row">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <input type="password"
                            class="form-control form-control-user @error('password') is-invalid @enderror"
                            placeholder="Password" name="password" value="{{ old('password') }}">
                        @error('password')
                            <small class="invalid-feedback">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="col-sm-6 mb-3">
                        <input type="password"
                            class="form-control form-control-user @error('password_confirmation') is-invalid @enderror"
                            placeholder="Repeat Password" name="password_confirmation"
                            value="{{ old('password_confirmation') }}">
                        @error('password_confirmation')
                            <small class="invalid-feedback">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-light rounded-pill fw-bold shadow">Register</button>
                </div>

                <p class="small text-center text-white">Already have an account? <a href="{{route ('userLogin')}}">Login</a></p>
            </form>
        </div>
    </div>
</section>

@endsection

