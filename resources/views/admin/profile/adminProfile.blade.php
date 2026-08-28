@extends('admin.layouts.master')

@section('content')
    <section class="container-fluid">
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="col-md-12">
                <form action="{{ url('admin/profile/update/' . auth()->user()->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row justify-content-center">
                        <div class="col-md-10 col-lg-8">
                            <div class="card shadow-lg rounded-4">
                                <div class="card-body p-4">
                                    <h2 class="text-center text-dark fw-bold mb-4">Update Account Information</h2>

                                    <div class="row">
                                        <!--  Profile Picture -->
                                        <div class="col-md-4 mb-4">
                                            <label for="image" class="form-label text-muted">Profile</label>
                                            <input type="hidden" name="oldImage" value="{{ auth()->user()->profile }}">
                                            <div class="d-flex justify-content-center mb-3">
                                                @if (auth()->user()->profile == null)
                                                    <img class="img-profile img-thumbnail rounded-circle"
                                                         id="output" src="{{ asset('admin/images/undraw_profile.svg') }}" style="width: 150px;">
                                                @else
                                                    <img class="img-profile img-thumbnail rounded-circle"
                                                         id="output" src="{{ asset('adminProfile/' . auth()->user()->profile) }}" style="width: 150px;">
                                                @endif
                                            </div>
                                            <input type="file" name="image" class="form-control mt-2 @error('image') is-invalid @enderror" onchange="loadFile(event)">
                                            @error('image')
                                                <small class="invalid-feedback">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <!-- Name, Phone, Update button -->
                                        <div class="col-md-4 mb-4">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Name</label>
                                                <input type="text" name="name"
                                                       @if (auth()->user()->provider != 'simple') disabled @endif
                                                       class="form-control @error('name') is-invalid @enderror"
                                                       id="name" placeholder="Enter your name"
                                                       value="{{ old('name', auth()->user()->name ?? auth()->user()->nickname) }}">
                                                @error('name')
                                                    <small class="invalid-feedback">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Phone</label>
                                                <input type="text" name="phone"
                                                       class="form-control @error('phone') is-invalid @enderror"
                                                       id="phone" placeholder="09xxxxxxxx"
                                                       value="{{ old('phone', auth()->user()->phone) }}">
                                                @error('phone')
                                                    <small class="invalid-feedback">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <input type="submit" value="Update" class="btn btn-primary w-100 mt-3 rounded-pill">
                                        </div>

                                        <!--Email, Role, Links -->
                                        <div class="col-md-4 mb-4">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" name="email"
                                                       @if (auth()->user()->provider != 'simple') disabled @endif
                                                       class="form-control @error('email') is-invalid @enderror"
                                                       id="email" placeholder="Email Address"
                                                       value="{{ auth()->user()->email }}">
                                                @error('email')
                                                    <small class="invalid-feedback">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="mb-3">
                                                <label for="role" class="form-label">Role</label>
                                                <input type="text" name="role"
                                                       class="form-control @error('role') is-invalid @enderror"
                                                       id="role" placeholder="User Role"
                                                       value="{{ old('role', auth()->user()->role) }}">
                                                @error('role')
                                                    <small class="invalid-feedback">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="d-grid gap-2">
                                                <a href="{{ route('profile.overview') }}" class="btn btn-outline-secondary w-100 mt-3 rounded-pill">
                                                    Profile Overview
                                                </a>
                                                <a href="{{ route('passwordpage') }}" class="btn btn-outline-secondary w-100 mt-2 rounded-pill">
                                                    Change Password
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
