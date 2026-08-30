@extends('chef.layouts.master')
@section('content')
    <section class="container my-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="text-white mb-0">Order #{{ $order->order_code }}</h2>
                    <a href="{{ url()->previous() }}" class="btn btn-outline-light btn-sm"><i class="fa fa-arrow-left me-1"></i>Back</a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row small text-muted mb-3">
                            <div class="col-6">
                                <div><i class="fa fa-user me-1"></i> Waiter: {{ $order->waiter->name ?? 'N/A' }}</div>
                                <div><i class="fa fa-store me-1"></i> Branch: {{ $order->branch->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-6">
                                <div><i class="fa fa-clock me-1"></i> Time: {{ $order->created_at->format('h:i A') }}</div>
                                <div><i class="fa fa-calendar me-1"></i> Date: {{ $order->created_at->format('Y-m-d') }}</div>
                                @if ($order->customerSession)
                                    <div><i class="fa fa-receipt me-1"></i> Session: {{ $order->customerSession->session_code }}</div>
                                @endif
                            </div>
                        </div>

                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item</th>
                                    <th>Size</th>
                                    <th>Qty</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($details as $o)
                                    <tr>
                                        <td>{{ $o->product->name ?? 'Product' }}</td>
                                        <td>{{ $o->size }}</td>
                                        <td>{{ $o->quantity }}</td>
                                        <td>{{ $o->notes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <p class="mb-0">
                            <strong>Status:</strong>
                            @if ((int) $order->status === 1)
                                <span class="badge bg-warning text-dark">New</span>
                            @elseif ((int) $order->status === 4)
                                <span class="badge bg-info">Preparing</span>
                            @elseif ((int) $order->status === 5)
                                <span class="badge bg-success">Ready</span>
                            @elseif ((int) $order->status === 2)
                                <span class="badge bg-primary">Completed</span>
                            @elseif ((int) $order->status === 3)
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </p>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                        <a href="{{ route('kitchen.kotPrint', $order->order_code) }}" target="_blank" class="btn btn-outline-secondary">
                            <i class="fa fa-print me-1"></i> Print KOT
                        </a>
                        @if ((int) $order->status === 1)
                            <form action="{{ route('chef.startPreparing', $order->order_code) }}" method="POST" class="m-0">
                                @csrf
                                <button class="btn btn-warning"><i class="fa fa-fire me-1"></i> Start Preparing</button>
                            </form>
                        @elseif ((int) $order->status === 4)
                            <form action="{{ route('chef.markReady', $order->order_code) }}" method="POST" class="m-0">
                                @csrf
                                <button class="btn btn-success"><i class="fa fa-check me-1"></i> Mark Ready</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
