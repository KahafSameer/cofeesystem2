@extends('waiter.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">Review Your Order</h2>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-body">
                        @if ($cartItems->isEmpty())
                            <p class="text-muted mb-0">Your cart is empty.</p>
                            <a href="{{ route('waiter.newOrder') }}" class="btn btn-primary mt-3">Browse Menu</a>
                        @else
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Product</th>
                                        <th>Size</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                        <th>Notes</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cartItems as $item)
                                        <tr>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $item->size }}</td>
                                            <td>
                                                <form action="{{ route('waiter.updateCart') }}" method="POST" class="d-flex align-items-center">
                                                    @csrf
                                                    <input type="hidden" name="cart_id" value="{{ $item->cartId }}">
                                                    <input type="number" name="qty" value="{{ $item->cart_qty }}" min="1"
                                                        class="form-control form-control-sm" style="max-width: 60px;">
                                                    <button class="btn btn-sm btn-outline-primary ms-1">OK</button>
                                                </form>
                                            </td>
                                            <td>{{ number_format($item->discount_price, 0) }}</td>
                                            <td>{{ number_format($item->discount_price * $item->cart_qty, 0) }}</td>
                                            <td>
                                                <form action="{{ route('waiter.updateCart') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="cart_id" value="{{ $item->cartId }}">
                                                    <input type="hidden" name="qty" value="{{ $item->cart_qty }}">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="notes" value="{{ $item->notes }}"
                                                            class="form-control">
                                                        <button class="btn btn-outline-secondary">Save</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <form action="{{ route('waiter.removeCart', $item->cartId) }}" method="POST">
                                                    @csrf
                                                    <button class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <hr>
                            <div class="d-flex justify-content-end">
                                <div style="min-width: 250px;">
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
                            <h5 class="fw-bold">Place Order</h5>
                            <form action="{{ route('waiter.placeOrder') }}" method="POST">
                                @csrf
                                <input type="hidden" name="orderCode" value="{{ $orderCode }}">
                                <input type="hidden" name="totalAmount" value="{{ $total }}">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Order Type</label>
                                        <select name="orderType" class="form-select" required>
                                            <option value="eat_in">Eat In</option>
                                            <option value="take_away">Take Away</option>
                                            <option value="delivery">Delivery</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select name="paymentMethod" class="form-select" required>
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="mobile">Mobile</option>
                                        </select>
                                    </div>
                                </div>
                                <button class="btn btn-success w-100"><i class="fa fa-check me-2"></i>Place Order</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
