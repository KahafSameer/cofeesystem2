@extends('admin.layouts.master')

@section('content')
    <!-- Begin Page Content -->
    <section class="container-fluid">
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="col-md-6">
                <div class="card ">
                    <div class="card-body">
                        <h2 class="intro-y text-lg text-center fw-bold mt-2 mb-4 ">
                            Change your password
                        </h2>

                        <form action="{{ url('admin/password/update/' . auth()->user()->id) }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label for="oldPassword" class="form-label font-medium">Old Password</label>
                                <input type="password" name="oldPassword" value="{{ old('oldPassword') }}"
                                    class="form-control @error('oldPassword') is-invalid @enderror" id="oldPassword">
                                @error('oldPassword')
                                    <small class="invalid-feedback text-red-500">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label for="newPassword" class="form-label font-medium">New Password</label>
                                <input type="password" name="newPassword" value="{{ old('newPassword') }}"
                                    class="form-control @error('newPassword') is-invalid @enderror" id="newPassword">
                                @error('newPassword')
                                    <small class="invalid-feedback text-red-500">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label for="confirmPassword" class="form-label font-medium">Confirm Password</label>
                                <input type="password" name="confirmPassword" value="{{ old('confirmPassword') }}"
                                    class="form-control @error('confirmPassword') is-invalid @enderror"
                                    id="confirmPassword">
                                @error('confirmPassword')
                                    <small class="invalid-feedback text-red-500">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-6">
                                        <button type="submit" class="btn btn-primary w-100">Update</button>
                                    </div>
                                    <div class="col-6">
                                        <a href="{{ route('profile.overview') }}" class="btn btn-secondary w-100">
                                            Back
                                        </a>
                                    </div>
                                </div>
                            </div>


                        </form>

                        <div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>
@endsection


