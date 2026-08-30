@extends('waiter.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">Current Orders</h2>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-body">
                        @if ($groupedOrders->isEmpty())
                            <p class="text-muted mb-0 text-center">No current orders.</p>
                        @else
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($groupedOrders as $orderCode => $orders)
                                        @php $first = $orders->first(); @endphp
                                        <tr>
                                            <td>{{ $orderCode }}</td>
                                            <td>{{ $first->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                @foreach ($orders as $o)
                                                    <div>
                                                        {{ $o->product->name ?? 'Product' }}
                                                        ({{ $o->size }}) x {{ $o->quantity }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if ($first->status == 1)
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif ($first->status == 2)
                                                    <span class="badge bg-success">Completed</span>
                                                @else
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('waiter.editOrder', $orderCode) }}"
                                                    class="btn btn-outline-warning btn-sm me-1">Edit</a>
                                                <a href="{{ route('waiter.orderDetails', $orderCode) }}"
                                                    class="btn btn-outline-primary btn-sm">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
