@extends('chef.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">Ready</h2>
        <div class="row g-3">
            @forelse ($groupedOrders as $orderCode => $orders)
                @include('chef.orders._order_card', ['orderCode' => $orderCode, 'orders' => $orders, 'actions' => ''])
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center text-muted">No orders ready yet.</div>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
@endsection
