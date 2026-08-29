@extends('waiter.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">Order Details</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="fw-bold">Order #{{ $order->order_code }}</h5>
                            <div>
                                @if ($order->status == 1)
                                    <a href="{{ route('waiter.editOrder', $order->order_code) }}"
                                        class="btn btn-warning btn-sm me-1">Edit</a>
                                @endif
                                <a href="{{ route('waiter.currentOrders') }}" class="btn btn-secondary btn-sm">Back</a>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Order Date:</strong><br>{{ $order->created_at->format('Y-m-d H:i') }}</div>
                            <div class="col-md-4"><strong>Branch:</strong><br>{{ $order->branch->name ?? '-' }}</div>
                            <div class="col-md-4">
                                <strong>Status:</strong><br>
                                @if ($order->status == 1)
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif ($order->status == 2)
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Order Type:</strong>
                                {{ $order->order_type == 1 ? 'Eat In' : ($order->order_type == 2 ? 'Take Away' : 'Delivery') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Payment Method:</strong>
                                {{ ucfirst($order->payment_method ?? '-') }}
                            </div>
                        </div>

                        <h6 class="fw-bold">Items</h6>
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item</th>
                                    <th>Size</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details as $d)
                                    <tr>
                                        <td>{{ $d->product->name ?? 'Product' }}</td>
                                        <td>{{ $d->size }}</td>
                                        <td>{{ $d->quantity }}</td>
                                        <td>{{ number_format($d->totalprice, 0) }}</td>
                                        <td>{{ $d->notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($paymentRecord)
                            <div class="d-flex justify-content-end">
                                <div style="min-width: 250px;">
                                    <div class="d-flex justify-content-between">
                                        <span>Net Amount</span><span>{{ number_format($paymentRecord->net_amount, 0) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Paid</span><span>{{ number_format($paymentRecord->paid_amount, 0) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Change</span><span>{{ number_format($paymentRecord->change_amount, 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
