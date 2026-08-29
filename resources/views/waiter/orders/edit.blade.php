@extends('waiter.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">Edit Order #{{ $order->order_code }}</h2>

        <div class="row g-4">
            <!-- Current order items -->
            <div class="col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fa fa-clipboard-list me-2"></i>Order Items</h5>
                        <span class="badge bg-warning text-dark">Pending</span>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item</th>
                                    <th>Size</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Notes</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orderItems as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->size }}</td>
                                        <td>
                                            <form action="{{ route('waiter.updateOrderItem') }}" method="POST" class="d-flex align-items-center">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $item->id }}">
                                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1"
                                                    class="form-control form-control-sm" style="max-width: 55px;">
                                                <button class="btn btn-sm btn-outline-primary ms-1">OK</button>
                                            </form>
                                        </td>
                                        <td>{{ number_format($item->discount_price, 0) }}</td>
                                        <td>
                                            <form action="{{ route('waiter.updateOrderItem') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $item->id }}">
                                                <input type="hidden" name="quantity" value="{{ $item->quantity }}">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="notes" value="{{ $item->notes }}" class="form-control">
                                                    <button class="btn btn-outline-secondary">OK</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('waiter.removeOrderItem') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $item->id }}">
                                                <button class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <hr>
                        <div class="d-flex justify-content-end">
                            <div style="min-width: 200px;">
                                <div class="d-flex justify-content-between">
                                    <span>Subtotal</span><span>{{ number_format($subTotal, 0) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Tax ({{ $taxRate }}%)</span><span>{{ number_format($taxAmount, 0) }}</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total</span><span>{{ number_format($total, 0) }}</span>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-bold">Order Details</h6>
                        <form action="{{ route('waiter.updateOrderMeta', $order->order_code) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Order Type</label>
                                    <select name="order_type" class="form-select">
                                        <option value="eat_in" {{ $order->order_type == 1 ? 'selected' : '' }}>Eat In</option>
                                        <option value="take_away" {{ $order->order_type == 2 ? 'selected' : '' }}>Take Away</option>
                                        <option value="delivery" {{ $order->order_type == 3 ? 'selected' : '' }}>Delivery</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="cash" {{ strtolower($order->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="card" {{ strtolower($order->payment_method) == 'card' ? 'selected' : '' }}>Card</option>
                                        <option value="mobile" {{ strtolower($order->payment_method) == 'mobile' ? 'selected' : '' }}>Mobile</option>
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm w-100">Update Details</button>
                        </form>

                        <a href="{{ route('waiter.currentOrders') }}" class="btn btn-secondary btn-sm w-100 mt-2">Back to Current Orders</a>
                    </div>
                </div>
            </div>

            <!-- Add items from menu -->
            <div class="col-lg-7">
                <h5 class="text-white mb-2">Add More Items</h5>
                <form action="{{ route('waiter.editOrder', $order->order_code) }}" method="get" class="mb-2">
                    <div class="input-group w-50">
                        <input type="text" name="searchKey" value="{{ request('searchKey') }}" class="form-control" placeholder="Search products...">
                        <button class="input-group-text"><i class="fa fa-search"></i></button>
                    </div>
                </form>
                <div class="mb-3">
                    @foreach ($categories as $cat)
                        <a href="{{ route('waiter.editOrder', ['orderCode' => $order->order_code, 'categoryId' => $cat->id]) }}"
                            class="btn btn-sm btn-outline-light">{{ $cat->name }}</a>
                    @endforeach
                </div>
                <div class="row g-3">
                    @forelse ($products as $item)
                        @if (request('categoryId') && $item->category_id != request('categoryId'))
                            @continue
                        @endif
                        @if (request('searchKey') && ! str_contains(strtolower($item->name), strtolower(request('searchKey'))))
                            @continue
                        @endif
                        <div class="col-md-4 col-sm-6">
                            <div class="card shadow-sm h-100 p-2">
                                <h6 class="text-dark mb-1">{{ $item->name }}</h6>
                                <small class="text-muted mb-1">
                                    @foreach ($item->sizes->take(3) as $size)
                                        <span class="d-block">
                                            @php
                                                $price = $size->price;
                                                if ($item->discount_percentage) {
                                                    $price = round($size->price - ($size->price * $item->discount_percentage / 100), 2);
                                                }
                                            @endphp
                                            {{ $size->size }}: {{ number_format($price, 0) }} PKR
                                        </span>
                                    @endforeach
                                </small>
                                <form action="{{ route('waiter.addToOrder', $order->order_code) }}" method="POST" class="mt-auto">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item->id }}">
                                    <div class="input-group input-group-sm mb-2">
                                        @if (count($item->sizes ?? []) > 0)
                                            <select name="size" class="form-select form-select-sm" {{ count($item->sizes) === 1 ? 'disabled' : '' }}>
                                                @foreach ($item->sizes as $size)
                                                    <option value="{{ $size->size }}">{{ $size->size }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="hidden" name="size" value="">
                                        @endif
                                        <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm text-center" style="max-width: 60px;">
                                    </div>
                                    <input type="text" name="notes" class="form-control form-control-sm mb-2" placeholder="Notes (optional)">
                                    <button class="btn btn-success btn-sm w-100"><i class="fa fa-plus me-1"></i>Add</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><p class="text-white text-center">No products found.</p></div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
