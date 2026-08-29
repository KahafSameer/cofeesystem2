@extends('waiter.layouts.master')
@section('content')
    <link rel="stylesheet" href="{{ asset('admin/CSS/waiter.css') }}">

    <section class="container my-4 waiter-order-wrap">
        <h2 class="text-center wo-title mb-4">
            <i class="fa-solid fa-pen-to-square me-2"></i>Edit Order #{{ $order->order_code }}
        </h2>

        <div class="row g-4">
            <!-- Current order items -->
            <div class="col-lg-5">
                <div class="wo-panel">
                    <div class="wo-panel-header">
                        <h5><i class="fa fa-clipboard-list me-2"></i>Order Items</h5>
                        <span class="wo-status-pill"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>
                    </div>
                    <div class="card-body">
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
                                @foreach ($orderItems as $item)
                                    <tr>
                                        <td class="item-name">
                                            {{ $item->name }}
                                            @if ($item->notes)
                                                <small class="item-note d-block">{{ $item->notes }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->size }}</td>
                                        <td>
                                            <form action="{{ route('waiter.updateOrderItem') }}" method="POST" class="d-flex align-items-center justify-content-center">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $item->id }}">
                                                <div class="qty-stepper">
                                                    <button type="button" class="qty-btn qty-minus" aria-label="Decrease">−</button>
                                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" class="qty-input">
                                                    <button type="button" class="qty-btn qty-plus" aria-label="Increase">+</button>
                                                </div>
                                                <button class="btn btn-sm btn-outline-primary ms-1">OK</button>
                                            </form>
                                        </td>
                                        <td>{{ number_format($item->discount_price, 0) }}</td>
                                    </tr>
                                    <tr class="wo-notes-row">
                                        <td colspan="4" class="bg-transparent">
                                            <div class="d-flex align-items-center w-100">
                                                <form action="{{ route('waiter.updateOrderItem') }}" method="POST" class="d-flex align-items-center flex-grow-1">
                                                    @csrf
                                                    <input type="hidden" name="order_id" value="{{ $item->id }}">
                                                    <input type="hidden" name="quantity" value="{{ $item->quantity }}">
                                                    <label class="form-label mb-0 me-2 text-muted small">Notes</label>
                                                    <div class="input-group input-group-sm flex-grow-1">
                                                        <input type="text" name="notes" value="{{ $item->notes }}" class="form-control">
                                                        <button class="btn btn-outline-secondary">OK</button>
                                                    </div>
                                                </form>
                                                <form action="{{ route('waiter.removeOrderItem') }}" method="POST" class="d-inline ms-2">
                                                    @csrf
                                                    <input type="hidden" name="order_id" value="{{ $item->id }}">
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="wo-cart-totals mt-3">
                            <div class="row-line"><span>Subtotal</span><span>PKR {{ number_format($subTotal, 0) }}</span></div>
                            <div class="row-line"><span>Tax ({{ $taxRate }}%)</span><span>PKR {{ number_format($taxAmount, 0) }}</span></div>
                            <div class="total-line">
                                <span>Total</span>
                                <span class="wo-total-amount">PKR {{ number_format($total, 0) }}</span>
                            </div>
                        </div>

                        <div class="wo-details-box mt-4">
                            <div class="wo-details-title mb-2"><i class="fa-solid fa-gears me-1"></i>Order Details</div>
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
                                <button class="btn btn-coffee btn-sm w-100 mt-1">
                                    <i class="fa-solid fa-check me-1"></i>Update Details
                                </button>
                            </form>
                        </div>

                        <a href="{{ route('waiter.currentOrders') }}" class="wo-back-btn btn btn-sm w-100 mt-3">
                            <i class="fa-solid fa-arrow-left me-1"></i>Back to Current Orders
                        </a>
                    </div>
                </div>
            </div>

            <!-- Add items from menu -->
            <div class="col-lg-7">
                <h5 class="wo-sub-title"><i class="fa-solid fa-plus-circle me-2"></i>Add More Items</h5>
                <form action="{{ route('waiter.editOrder', $order->order_code) }}" method="get" class="mb-2">
                    <div class="input-group w-50 wo-search">
                        <input type="text" name="searchKey" value="{{ request('searchKey') }}" class="form-control" placeholder="Search products...">
                        <button class="input-group-text"><i class="fa fa-search"></i></button>
                    </div>
                </form>
                <div class="mb-3">
                    <a href="{{ route('waiter.editOrder', $order->order_code) }}"
                        class="btn btn-sm wo-cat {{ empty(request('categoryId')) ? 'active' : '' }}">All</a>
                    @foreach ($categories as $cat)
                        <a href="{{ route('waiter.editOrder', ['orderCode' => $order->order_code, 'categoryId' => $cat->id]) }}"
                            class="btn btn-sm wo-cat {{ request('categoryId') == $cat->id ? 'active' : '' }}">{{ $cat->name }}</a>
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
                            <div class="card h-100 p-2 wo-card">
                                <img src="{{ asset('productImages/' . $item->image) }}" class="wo-img" alt="">
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
                                    <form action="{{ route('waiter.addToOrder', $order->order_code) }}" method="POST" class="mt-auto">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item->id }}">
                                        <div class="d-flex gap-2 mb-2">
                                            @if (count($item->sizes ?? []) > 0)
                                                <select name="size" class="form-select form-select-sm flex-grow-1" {{ count($item->sizes) === 1 ? 'disabled' : '' }}>
                                                    @foreach ($item->sizes as $size)
                                                        <option value="{{ $size->size }}">{{ $size->size }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="hidden" name="size" value="">
                                            @endif
                                            <div class="qty-stepper">
                                                <button type="button" class="qty-btn qty-minus" aria-label="Decrease">−</button>
                                                <input type="number" name="quantity" value="1" min="1" class="qty-input">
                                                <button type="button" class="qty-btn qty-plus" aria-label="Increase">+</button>
                                            </div>
                                        </div>
                                        <input type="text" name="notes" class="form-control form-control-sm mb-2" placeholder="Notes (optional)">
                                        <button class="btn btn-sm w-100 wo-add"><i class="fa fa-plus me-1"></i>Add to Order</button>
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

@section('scripts')
    <script>
        (function () {
            function syncStepper(stepper) {
                const input = stepper.querySelector('.qty-input');
                if (!input) return;
                const min = parseInt(input.min || '1', 10);
                const minus = stepper.querySelector('.qty-minus');
                if (minus) minus.disabled = (parseInt(input.value || min, 10) <= min);
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.qty-btn');
                if (!btn || btn.disabled) return;
                const stepper = btn.closest('.qty-stepper');
                if (!stepper) return;
                const input = stepper.querySelector('.qty-input');
                const min = parseInt(input.min || '1', 10);
                const max = parseInt(input.max || '', 10);
                let value = parseInt(input.value || min, 10);
                value += btn.classList.contains('qty-plus') ? 1 : -1;
                if (value < min) value = min;
                if (max && value > max) value = max;
                input.value = value;
                syncStepper(stepper);
            });

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.qty-stepper').forEach(syncStepper);
            });
        })();
    </script>
@endsection