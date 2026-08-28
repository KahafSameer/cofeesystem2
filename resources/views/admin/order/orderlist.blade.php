@extends('admin.layouts.master')
@section('content')
    <section class="container mt-4">
        <div class="row justify-content-between align-items-center">
            <div class="col-12 col-lg-12">
                <div class="card border-left-warning shadow border-2">
                    <div class="card-body">
                        <h2 class="text-medium text-center fw-bold mb-3">Daily Customer Order</h2>
                            <a href="{{ route('adminDashboard') }}" class="btn btn-secondary align-items-center justify-content-center shadow-sm mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-left me-2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5"/>
                                </svg> Back
                            </a>
                        <div class="table-responsive ">
                            <table class="table table-hover table-bordered align-middle text-center">
                                <thead class="table">
                                    <tr>
                                        <th>Order Code</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($groupedOrders as $orderCode => $orders)
                                        <tr>
                                            <td>{{ $orderCode }}</td>
                                            <td>{{ \Carbon\Carbon::parse($orders->first()->created_at)->format('j-F-y') }}
                                            </td>
                                            <td>
                                                @php
                                                    $statuses = $orders->pluck('status');

                                                    if ($statuses->every(fn($status) => $status == 2)) {
                                                        $orderStatus = 'Success';
                                                    } elseif ($statuses->every(fn($status) => $status == 1)) {
                                                        $orderStatus = 'Pending';
                                                    } elseif ($statuses->every(fn($status) => $status == 3)) {
                                                        $orderStatus = 'Rejected';
                                                    } else {
                                                        $orderStatus = 'Partial Processing';
                                                    }
                                                @endphp
                                                <span
                                                    class="badge bg-{{ $orderStatus == 'Partial Processing' ? 'warning' : ($orderStatus == 'Success' ? 'success' : ($orderStatus == 'Rejected' ? 'danger' : 'primary')) }}">
                                                    {{ $orderStatus }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('order.viewOrder', $orderCode) }}"
                                                    class="btn btn-primary w-100">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end">{{ $groupedOrders->links() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
