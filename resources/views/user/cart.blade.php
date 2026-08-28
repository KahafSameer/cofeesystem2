@extends('user.layouts.master')

@section('content')
<section class="container py-3" style="background-color: #ffffff;">
    <div class="row g-4">

        <!-- Cart Items Section -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold">Your Cart</h4>
                <form action="{{ route('climenu') }}" method="get">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-plus me-2"></i> Add More Items
                    </button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-secondary">
                        <tr class="text-center">
                            <th>Image</th>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Discounted</th>
                            <th>subTotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @if (isset($cartItems) && $cartItems->isNotEmpty())
                            @foreach ($cartItems as $item)
                                <tr class="text-center">
                                    <td>
                                        <img src="{{ asset('productImages/' . $item->image) }}"
                                             class="img-fluid rounded-circle"
                                             style="width: 50px; height: 50px;"
                                             alt="{{ $item->name }}">
                                    </td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->size }}</td>
                                    <td>{{ number_format($item->price, 2, '.', '') }}</td>

                                    <!-- Quantity Update Form -->
                                    <form action="{{ route('updateCart') }}" method="POST" class="cart-form">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item->id }}">
                                        <input type="hidden" name="size" value="{{ $item->size }}">
                                        <input type="hidden" name="quantity" value="{{ $item->cart_qty }}" class="quantity-input">
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <button type="submit" class="btn btn-outline-secondary btn-minus" data-product-id="{{ $item->id }}">
                                                    <i class="fa-solid fa-circle-minus"></i>
                                                </button>
                                                <input type="text" class="form-control text-center qty"
                                                       value="{{ $item->cart_qty }}" readonly
                                                       style="width: 50px; font-size: 14px;">

                                                <button type="submit" class="btn btn-outline-secondary btn-plus" data-product-id="{{ $item->id }}">
                                                    <i class="fa-solid fa-circle-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </form>

                                    <td class="unitPrice">{{ number_format($item->discountPrice, 2, '.', '') }}</td>
                                    <td class="subTotal">{{ number_format($item->discountPrice * $item->cart_qty, 2, '.', '') }}</td>

                                    <td>
                                        <form action="{{ route('removeCart', $item->cartId) }}" method="post">
                                            @csrf
                                            <button class="btn btn-danger btn-sm">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

           <div class="mt-4">
                <a href="{{ route('climenu') }}" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to Menu
                </a>
            </div>
        </div>

        <!-- Order Summary Section -->
        <div class="col-lg-4">
            <div class="card bg-dark text-white shadow">
                <div class="card-body">

                    <!-- Order Code -->
                    <div class="mb-3 text-center">
                        <h5 class="fw-bold">Order Code:</h5>
                        <p>{{ $item->orderCode ?? '' }}</p>
                    </div>

                    <!-- Payment Icons -->
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <i class="fab fa-cc-mastercard fa-2x"></i>
                        <i class="fab fa-cc-visa fa-2x"></i>
                        <i class="fab fa-cc-amex fa-2x"></i>
                        <i class="fab fa-cc-paypal fa-2x"></i>
                    </div>

                    <!-- Payment Form -->
                        <form>
                            <div class="mb-2">
                                <input type="text" id="typeName" class="form-control" placeholder="Cardholder's Name" />
                            </div>
                            <div class="mb-2">
                                <input type="text" id="typeText" class="form-control" placeholder="1234 5678 9012 3457" maxlength="19" />
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <input type="text" id="typeExp" class="form-control" placeholder="MM/YYYY" maxlength="7" />
                                </div>
                                <div class="col-md-6">
                                    <input type="password" id="typeText" class="form-control" placeholder="CVV" maxlength="3" />
                                </div>
                            </div>

                        </form>

                    <hr class="bg-light my-4">
                    <div class="d-flex justify-content-between fw-bold">
                        <p>Tax</p>
                        <p id="taxAmount">{{ number_format($taxAmount, 2, '.', '') }}</p>
                    </div>

                    <div class="d-flex justify-content-between fw-bold">
                        <p>Deli Fees</p>
                        <p id="deliveryFeeAmount">{{ number_format($deliveryFee, 2, '.', '') }}</p>
                    </div>
                    <div class="d-flex justify-content-between fw-bold">
                        <p>Sub Total</p>
                        <p id="subtotalAmount">{{ number_format($subtotal, 2, '.', '') }}</p>
                    </div>

                    <!-- Confirm Payment Button -->
                    <form action="{{ route('paymentConfirm', $item->id) }}" method="POST" class="mt-4">
                        @csrf
                        <input type="hidden" name="orderCode" value="{{ $item->orderCode }}">
                        <button type="submit" class="btn btn-success w-100">
                            Confirm Payment <span class="float-end" id="cart-total">{{ number_format($total, 2, '.', '') }} MMK</span>
                        </button>
                    </form>

                </div>
            </div>
        </div>

    </div>
</section>
@endsection

@section('scripts')
<script>
    var taxRate = {{ $taxRate }};
    var deliveryFee = {{ $deliveryFee }};

    $(document).ready(function () {

    $('.btn-plus, .btn-minus').click(function (e) {
        e.preventDefault();

        let $row = $(this).closest('tr');
        let $qtyInput = $row.find('.qty');
        let quantity = parseInt($qtyInput.val());

        // Update quantity
        if ($(this).hasClass('btn-plus')) {
            quantity += 1;
        } else if ($(this).hasClass('btn-minus') && quantity > 1) {
            quantity -= 1;
        } else {
            return;
        }

        $qtyInput.val(quantity);

        let productId = $row.find('input[name="product_id"]').val();
        let size = $row.find('input[name="size"]').val();

        // Update subtotal
        let unitPrice = parseFloat($row.find('.unitPrice').text());
        let newSubtotal = (unitPrice * quantity).toFixed(2);
        $row.find('.subTotal').text(newSubtotal);

        // Update total in cart
        updateCartTotal();

        // Update server cart without reload
        updateCart(productId, quantity, size);
    });

    function updateCart(productId, quantity, size) {
        $.ajax({
            url: '{{ route("updateCart") }}',
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                size: size,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    // Update DOM
                    $('#subtotalAmount').text(response.subtotal.toFixed(2));
                    $('#taxAmount').text(response.taxAmount.toFixed(2));
                    $('#cart-total').text(response.total.toFixed(2));
                    $('#confirm-payment-total').text(response.total.toFixed(2) + ' MMK');
                } else {
                    alert("Server failed to update cart.");
                }
            },
            error: function (xhr) {
                console.error("Error:", xhr.responseText);
            }
        });
    }

    function updateCartTotal() {
    let subtotal = 0;
    let smallestUnit = 10; // same as controller

    $('.qty').each(function () {
        let $row = $(this).closest('tr');
        let quantity = parseInt($(this).val());
        let price = parseFloat($row.find('.unitPrice').text());
        let subTotal = price * quantity;

        $row.find('.subTotal').text(subTotal.toFixed(2));

        subtotal += subTotal;
    });

    // Tax calculation same as controller:
    let tax = Math.ceil(((subtotal * taxRate) / 100) / smallestUnit) * smallestUnit;

    // Total calculation same as controller:
    let total = Math.ceil((subtotal + tax + deliveryFee) / smallestUnit) * smallestUnit;

    // Update DOM:
    $('#subtotalAmount').text(subtotal.toFixed(2));
    $('#taxAmount').text(tax.toFixed(2));
    $('#cart-total').text(total.toFixed(2));
}

});
</script>
@endsection
