@extends('waiter.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">New Order</h2>

        <div class="row g-4">
            <div class="col-lg-4">
                <!-- Cart Summary -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fa fa-shopping-bag me-2"></i>Cart</h5>
                        <form action="{{ route('waiter.cart') }}" method="GET" class="m-0">
                            <button class="btn btn-primary btn-sm">Review</button>
                        </form>
                    </div>
                    <div class="card-body">
                        @if ($cartItems->isEmpty())
                            <p class="text-muted mb-0">Your cart is empty. Select products from the menu.</p>
                        @else
                            <table class="table table-sm table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Item</th>
                                        <th>Size</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cartItems as $item)
                                        <tr>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $item->size }}</td>
                                            <td>{{ $item->cart_qty }}</td>
                                            <td>{{ number_format($item->discount_price * $item->cart_qty, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Subtotal</span>
                                <span>{{ number_format($subTotal, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Tax ({{ $taxRate }}%)</span>
                                <span>{{ number_format($taxAmount, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold mt-1">
                                <span>Total</span>
                                <span>{{ number_format($total, 0) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <!-- Search -->
                <form action="{{ route('waiter.newOrder') }}" method="get" class="mb-3">
                    <div class="input-group w-50">
                        <input type="text" class="form-control" value="{{ request('searchKey') }}"
                            name="searchKey" placeholder="Search products...">
                        <button type="submit" class="input-group-text"><i class="fa fa-search"></i></button>
                    </div>
                </form>

                <!-- Categories -->
                <div class="mb-3">
                    <a href="{{ route('waiter.newOrder') }}" class="btn btn-sm {{ empty($selectedCategoryId) ? 'btn-dark' : 'btn-outline-light' }}">All</a>
                    @foreach ($categories as $cat)
                        <a href="{{ route('waiter.newOrder', ['categoryId' => $cat->id]) }}"
                            class="btn btn-sm {{ $selectedCategoryId == $cat->id ? 'btn-dark' : 'btn-outline-light' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>

                <!-- Products -->
                <div class="row g-3">
                    @forelse ($products as $item)
                        <div class="col-md-4 col-sm-6">
                            <div class="card shadow-sm h-100 p-2">
                                <img src="{{ asset('productImages/' . $item->image) }}"
                                    class="img-fluid rounded" style="height: 120px; object-fit: cover; width: 100%;" alt="">
                                <h6 class="text-dark mt-2 mb-1">{{ $item->name }}</h6>
                                <small class="text-muted mb-1">
                                    @foreach ($item->sizes->take(3) as $size)
                                        <span class="d-block">
                                            @php
                                                $price = $size->price;
                                                if ($item->discount_percentage) {
                                                    $price = round($size->price - ($size->price * $item->discount_percentage / 100), 2);
                                                }
                                            @endphp
                                            {{ $size->size }}: {{ number_format($price, 0) }} MMK
                                            @if ($item->discount_percentage)
                                                <del class="text-muted" style="font-size: 11px;">{{ number_format($size->price, 0) }}</del>
                                            @endif
                                        </span>
                                    @endforeach
                                </small>

                                <form action="{{ route('waiter.addToCart') }}" method="POST" class="mt-auto">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item->id }}">
                                    <input type="hidden" name="orderCode" value="{{ $orderCode }}">
                                    <input type="hidden" name="noteInput" value="">
                                    <div class="input-group input-group-sm mb-2">
                                        @if (count($item->sizes ?? []) > 0)
                                            <select name="size" class="form-select form-select-sm"
                                                {{ count($item->sizes) === 1 ? 'disabled' : '' }}>
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
