@extends('waiter.layouts.master')
@section('content')
    <section class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="text-white mb-0">Session #{{ $session->session_code }}</h2>
                    <a href="{{ route('waiter.sessions') }}" class="btn btn-outline-light btn-sm"><i class="fa fa-arrow-left me-1"></i>Back</a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Status:
                                @if ($session->isOpen())
                                    <span class="badge bg-success">Open</span>
                                @elseif ($session->isBillRequested())
                                    <span class="badge bg-warning text-dark">Bill Requested</span>
                                @else
                                    <span class="badge bg-secondary">Closed</span>
                                @endif
                            </span>
                            <div class="small text-muted mt-1">
                                Opened: {{ $session->opened_at?->format('Y-m-d H:i') }}
                                @if ($session->bill_requested_at)
                                    &middot; Bill requested: {{ $session->bill_requested_at->format('Y-m-d H:i') }}
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Running Total</div>
                            <h3 class="fw-bold text-success mb-0">{{ number_format($total, 0) }} PKR</h3>
                        </div>
                    </div>
                </div>

                @if ($session->isOpen())
                    <div class="mb-3">
                        <a href="{{ route('waiter.sessionNewOrder', $session) }}" class="btn btn-primary">
                            <i class="fa fa-plus me-1"></i> Add More Items
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fa fa-info-circle me-1"></i> The bill has been requested. No more items can be added to this session.
                    </div>
                @endif

                <div class="card shadow">
                    <div class="card-body">
                        @if ($groupedOrders->isEmpty())
                            <p class="text-muted mb-0 text-center">No orders in this session yet.</p>
                        @else
                            @foreach ($groupedOrders as $orderCode => $orders)
                                @php $first = $orders->first(); @endphp
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>Order #{{ $orderCode }}</strong>
                                        <span class="small text-muted">{{ $first->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <table class="table table-sm table-bordered mb-2">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Item</th>
                                                <th>Size</th>
                                                <th>Qty</th>
                                                <th>Unit Price</th>
                                                <th>Line Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $o)
                                                <tr>
                                                    <td>{{ $o->product->name ?? 'Product' }}</td>
                                                    <td>{{ $o->size }}</td>
                                                    <td>{{ $o->quantity }}</td>
                                                    <td>{{ number_format($o->totalprice, 0) }}</td>
                                                    <td>{{ number_format($o->totalprice * $o->quantity, 0) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach

                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Subtotal</span>
                                <span>{{ number_format($subTotal, 0) }} PKR</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Tax ({{ $taxRate }}%)</span>
                                <span>{{ number_format($taxAmount, 0) }} PKR</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span>{{ number_format($total, 0) }} PKR</span>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($session->isOpen() && ! $groupedOrders->isEmpty())
                    <form action="{{ route('waiter.requestBill', $session) }}" method="POST" class="mt-3 text-end">
                        @csrf
                        <button class="btn btn-warning" onclick="return confirm('Request the bill for this session? No more items can be added.');">
                            <i class="fa fa-file-invoice me-1"></i> Request Bill
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
