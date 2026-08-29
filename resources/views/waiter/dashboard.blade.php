@extends('waiter.layouts.master')
@section('content')
    <section class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col">
                <div class="row mt-4">
                    <!-- Welcome Card -->
                    <div class="col-12 mb-3">
                        <div class="card shadow">
                            <div class="card-body">
                                <h4 class="fw-bold mb-0">Welcome, {{ $waiter->name }}!</h4>
                                <p class="mb-0 text-muted mt-1">
                                    <i class="fa-solid fa-store me-1"></i> Branch:
                                    @if ($branch)
                                        {{ $branch->name }}
                                        @if ($branch->address)
                                            <small class="text-muted">({{ $branch->address }})</small>
                                        @endif
                                    @else
                                        <span class="text-danger">No branch assigned</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Cards -->
                    <div class="col-xl-3 col-md-6 col-sm-12 mb-2">
                        <div class="card border-left-warning shadow h-100 d-flex flex-column">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="text-xs fw-bold text-primary mb-1">Today's Orders</div>
                                <div class="h4 mb-0 fw-bold">{{ $todayOrders }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 col-sm-12 mb-2">
                        <div class="card border-left-warning shadow h-100 d-flex flex-column">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="text-xs fw-bold text-warning mb-1">Pending Orders</div>
                                <div class="h4 mb-0 fw-bold">{{ $pendingOrders }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 col-sm-12 mb-2">
                        <div class="card border-left-warning shadow h-100 d-flex flex-column">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="text-xs fw-bold text-success mb-1">Completed Orders</div>
                                <div class="h4 mb-0 fw-bold">{{ $completedOrders }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 col-sm-12 mb-2">
                        <div class="card border-left-warning shadow h-100 d-flex flex-column">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="text-xs fw-bold text-secondary mb-1">Total Orders</div>
                                <div class="h4 mb-0 fw-bold">{{ $totalOrders }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
