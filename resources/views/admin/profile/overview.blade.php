@extends('admin.layouts.master')
@section('content')
    <!-- BEGIN: Content -->
    <section class="container-fluid">
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="card shadow-sm" style="border-radius: 8px; max-width: 600px;" >
                <div class="row">
                    <h2 class="intro-y fw-bold text-center mt-2 mb-4">
                        Profile Layout
                    </h2>
                    <!-- Profile Image Column -->
                    <div class="col-md-6 d-flex flex-column align-items-center">
                        <div class="mb-3">
                            @if (auth()->user() && auth()->user()->profile == null)
                                <img class="img-profile img-thumbnail rounded-circle mb-3" id="output"
                                     src="{{ asset('admin/images/undraw_profile.svg') }}"
                                     style="width: 150px;  border-radius: 50%;">
                            @else
                                <img class="img-profile img-thumbnail rounded-circle mb-3" id="output"
                                     src="{{ asset('adminProfile/' . auth()->user()->profile) }}"
                                     style="width: 150px;  border-radius: 50%;">
                            @endif
                        </div>
                        <h4 class="text-center">Name: {{ auth()->user()->name }}</h4>
                        <p class="text-muted text-center">Role: {{ old('role', auth()->user()->role) }}</p>
                    </div>

                    <!-- Contact Details Column -->
                    <div class="col-md-5 mt-5">
                        <h5 class="fw-bold mt-3">Contact Details</h5>
                        <div class="mt-3">
                            <p class="mb-2">
                                <i class="bi bi-envelope me-2"></i>
                                {{ auth()->user()->email }}
                            </p>
                            <p class="mb-2">
                                <i class="bi bi-telephone me-2"></i>
                                {{ old('phone', auth()->user()->phone) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tabs Section -->
                <div class="mt-4">
                    <ul class="nav nav-tabs justify-content-center">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ route('adminDashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('profile.detail') }}">Account & Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('passwordpage') }}">Reset Password</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
