@extends('admin.layouts.master')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid justify-content-center align-items-center p-4">
        <!-- DataTales Example -->
        <div class="card shadow  mb-4 mt-4 col-lg-6 col-md-8 col-12 mx-auto" >
            <div class="card-header py-3">
                    <h3 class="m-0 fw-bold text-dark text-center">Reset Password</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('resetPassword') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" name="email" value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror" id="exampleFormControlInput1"
                            placeholder="Enter email to reset">
                        @error('email')
                            <small class="invalid-feedback">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <input type="submit" value="Reset Password" class="btn btn-primary w-100">
                        </div>
                        <div class="col-6">
                            <a href="{{ route('adminDashboard') }}" class="btn btn-secondary w-100 text-center">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>

        </div>

    </div>
    <!-- /.container-fluid -->
@endsection
