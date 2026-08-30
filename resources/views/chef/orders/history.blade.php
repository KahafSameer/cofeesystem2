@extends('chef.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">Order History</h2>
        <div class="row g-3">
            @forelse ($groupedOrders as $orderCode => $orders)
                @php
                    $first = $orders->first();
                    $statusBadge = match ((int) $first->status) {
                        4 => '<span class="badge bg-warning text-dark">Preparing</span>',
                        5 => '<span class="badge bg-success">Ready</span>',
                        2 => '<span class="badge bg-primary">Completed</span>',
                        3 => '<span class="badge bg-danger">Rejected</span>',
                        default => '<span class="badge bg-secondary">' . $first->status . '</span>',
                    };
                    $actions = '
                    <span>' . $statusBadge . '</span>
                    <a href="' . route('kitchen.kotPrint', $orderCode) . '" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-print me-1"></i>KOT
                    </a>';
                @endphp
                @include('chef.orders._order_card', ['orderCode' => $orderCode, 'orders' => $orders, 'actions' => $actions])
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center text-muted">No order history yet.</div>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
@endsection
