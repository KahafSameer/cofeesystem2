@extends('waiter.layouts.master')
@section('content')
    <link rel="stylesheet" href="{{ asset('admin/CSS/waiter.css') }}">

    <section class="container my-4 waiter-order-wrap">
        <h2 class="text-center wo-title mb-4">
            <i class="fa-solid fa-utensils me-2"></i>New Order
        </h2>

        <div class="row g-4">
            <div class="col-lg-4">
                <!-- Cart Summary -->
                <div class="wo-cart">
                    <div class="wo-cart-header d-flex justify-content-between align-items-center">
                        <h5><i class="fa fa-shopping-bag me-2"></i>Cart</h5>
                        <form action="{{ route('waiter.cart') }}" method="GET" class="m-0">
                            <button class="btn btn-coffee btn-sm">Review</button>
                        </form>
                    </div>
                    <div class="card-body">
                        @if ($cartItems->isEmpty())
                            <p class="wo-empty-msg mb-0">Your cart is empty. Select products from the menu.</p>
                        @else
                            <table class="table table-sm wo-cart-table">
                                <thead>
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
                                            <td class="item-name">{{ $item->name }}</td>
                                            <td>{{ $item->size }}</td>
                                            <td>{{ $item->cart_qty }}</td>
                                            <td>{{ number_format($item->discount_price * $item->cart_qty, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="wo-cart-totals">
                                <div class="row-line"><span>Subtotal</span><span>PKR {{ number_format($subTotal, 0) }}</span></div>
                                <div class="row-line"><span>Tax ({{ $taxRate }}%)</span><span>PKR {{ number_format($taxAmount, 0) }}</span></div>
                                <div class="total-line">
                                    <span>Total</span>
                                    <span class="wo-total-amount">PKR {{ number_format($total, 0) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <!-- Search -->
                <form action="{{ route('waiter.newOrder') }}" method="get" class="mb-3">
                    <div class="input-group w-50 wo-search">
                        <input type="text" class="form-control" value="{{ request('searchKey') }}"
                            name="searchKey" placeholder="Search products...">
                        <button type="submit" class="input-group-text"><i class="fa fa-search"></i></button>
                    </div>
                </form>

                <!-- Categories -->
                <div class="mb-3">
                    <a href="{{ route('waiter.newOrder') }}" class="btn btn-sm wo-cat {{ empty($selectedCategoryId) ? 'active' : '' }}">All</a>
                    @foreach ($categories as $cat)
                        <a href="{{ route('waiter.newOrder', ['categoryId' => $cat->id]) }}"
                            class="btn btn-sm wo-cat {{ $selectedCategoryId == $cat->id ? 'active' : '' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>

                <!-- Products -->
                <div class="row g-3">
                    @forelse ($products as $item)
                        <div class="col-md-4 col-sm-6">
                            <div class="card h-100 p-2 wo-card">
                                <img src="{{ asset('productImages/' . $item->image) }}" class="wo-img"
                                    alt="">
                                <div class="p-2 d-flex flex-column">
                                    <h6 class="wo-name mt-1 mb-1">{{ $item->name }}</h6>
                                    <small class="wo-size mb-2">
                                        @foreach ($item->sizes->take(3) as $size)
                                            <span class="d-block">
                                                @php
                                                    $price = $size->price;
                                                    if ($item->discount_percentage) {
                                                        $price = round($size->price - ($size->price * $item->discount_percentage / 100), 2);
                                                    }
                                                @endphp
                                                <span class="wo-price">{{ number_format($price, 0) }} PKR</span> / {{ $size->size }}
                                                @if ($item->discount_percentage)
                                                    <del class="ms-1">{{ number_format($size->price, 0) }}</del>
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
                                        <button class="btn btn-sm w-100 wo-add"><i class="fa fa-plus me-1"></i>Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12"><p class="text-center wo-unavailable">No products found.</p></div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection