@extends('chef.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">Preparing</h2>
        <div class="row g-3">
            @forelse ($groupedOrders as $orderCode => $orders)
                @php
                    $actions = '
                    <form action="' . route('chef.markReady', $orderCode) . '" method="POST" class="m-0">
                        ' . csrf_field() . '
                        <button class="btn btn-success btn-sm"><i class="fa fa-check me-1"></i>Mark Ready</button>
                    </form>';
                @endphp
                @include('chef.orders._order_card', ['orderCode' => $orderCode, 'orders' => $orders, 'actions' => $actions])
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center text-muted">No orders currently preparing.</div>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
@endsection
