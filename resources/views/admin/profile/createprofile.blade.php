@extends('admin.layouts.master')

@section('content')
        <section class="container-fluid">
            <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
                <div class="card shadow col-5">
                    <div class="card-header py-3">
                        <div class="">
                            <div class="">
                                <h3 class="m-0 fw-bold text-center">Add User Account</h3>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profile.addNewUser') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="profile" class="form-label">User Profile</label>
                                <select id="profile" name="profile" class="form-select">
                                    <option value="admin">Admin</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="chef">Chef</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" id="name">
                                @error('name')
                                    <small class="invalid-feedback">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" id="email">
                                @error('email')
                                    <small class="invalid-feedback">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password">
                                        @error('password')
                                            <small class="invalid-feedback">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="confirmpassword" class="form-label">Confirm Password</label>
                                        <input type="password" name="confirmpassword" class="form-control @error('confirmpassword') is-invalid @enderror" id="confirmpassword">
                                        @error('confirmpassword')
                                            <small class="invalid-feedback">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <input type="submit" value="Create" class="btn btn-coffee w-100">
                        </form>
                    </div>
                </div>
            </div>

        </section>
@endsection
