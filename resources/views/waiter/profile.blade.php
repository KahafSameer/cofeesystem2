@extends('waiter.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">My Profile</h2>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 150px;">Name</th>
                                <td>{{ $waiter->name }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $waiter->email }}</td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td>{{ $waiter->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Role</th>
                                <td>{{ ucfirst($waiter->role) }}</td>
                            </tr>
                            <tr>
                                <th>Branch</th>
                                <td>
                                    @if ($branch)
                                        {{ $branch->name }}
                                        @if ($branch->address)
                                            <small class="text-muted">({{ $branch->address }})</small>
                                        @endif
                                    @else
                                        <span class="text-danger">No branch assigned</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        <p class="text-muted small mb-0">
                            <i class="fa fa-info-circle me-1"></i>
                            Branch assignment is managed by your administrator and cannot be changed here.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
