<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="{{ asset('admin/CSS/booking.css') }}">
</head>

<body>
    <div class="container-fluid mt-4">
        {{-- <div class="row"> --}}
        <div class="row">
            <div class="col-lg-8">
                <a href="{{ route('adminDashboard') }}" class="btn btn-primary d-inline-flex align-items-center mb-2">
                    <i class="fa-solid fa-arrow-left me-2"></i>Back
                </a>

                <div class="d-flex flex-wrap mb-3 align-items-center">
                    <div class="row w-100">
                        <!-- Search Form -->
                        <form action="{{ route('getProductsByCategory') }}" method="GET"
                            class="col-lg-4 mb-2 mb-lg-0">
                            @csrf
                            <div class="input-group">
                                @if (isset($selectedCategoryId))
                                    <input type="hidden" name="categoryId" value="{{ $selectedCategoryId }}">
                                @endif
                                 <input type="text" name="searchKey" value="{{ request('searchKey') }}"
                                    class="form-control" placeholder="Search products...">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Right-Side Content -->
                        <div class="col-lg-8 d-flex text-white justify-content-end mt-2 mt-lg-0 ms-auto">
                            <!-- Order Code Display and New Order Button -->
                            <div class="d-flex align-items-center mx-3">
                                <span class="mx-2"><strong id="orderCodeDisplay">{{ $orderCode ?? 0 }}</strong></span>
                                <button type="button" class="btn btn-primary" id="newOrderButton">New
                                    Order</button>
                            </div>

                            <!-- Dropdown for Order Codes -->
                            <div class="dropdown">
                                <a class="btn btn-secondary dropdown-toggle" href="#" role="button"
                                    id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                    Order Codes
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink" id="orderCodeDropdown">
                                    <!-- Order codes will be loaded here by JavaScript -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Category List -->
                <div class="row mb-3">
                    @foreach ($categories as $category)
                        <div class="col-md-3 mb-2">
                            <form action="{{ route('getProductsByCategory') }}" method="GET">
                                @csrf
                                <input type="hidden" name="categoryId" value="{{ $category->id }}">
                                <button type="submit" class="category-btn">
                                    <div class="card category-card text-center">
                                        <h6 class="card-title">{{ $category->name }}</h6>
                                    </div>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <!-- Product List -->
                @if ((isset($selectedCategoryId) || request('searchKey')) && $productbyCategory->isNotEmpty())
                    <div class="row">
                        @foreach ($productbyCategory as $item)
                            <div class="col-md-3 mb-3">
                                @php
                                    $selectedSize = $existingCart->size ?? ($item->sizes[0]->size ?? 'M');
                                @endphp
                                <form action="{{ route('additems', $item->id) }}" method="POST" class="h-100 d-flex flex-column">
                                    @csrf
                                    <input type="hidden" name="orderCode" class="order-code" value="{{ $orderCode }}">
                                    <input type="hidden" name="product_id" value="{{ $item->id }}">
                                    <input type="hidden" name="quantity" class="quantity-input" value="{{ $cartItemsCount[$item->id] ?? 0 }}">
                                    <input type="hidden" name="notes" id="noteInput_{{ $item->id }}">

                                        @if(count($item->sizes) === 1)
                                            <input type="hidden" name="size" value="{{ $selectedSize }}">
                                        @endif
                                    <div class="card h-100 shadow-sm border-1 product-card">
                                        <div class="position-relative">
                                            <img src="{{ asset('productImages/' . $item->image) }}"
                                                class="card-img-top product-image" alt="{{ $item->name }}">

                                            <span class="badge product-badge position-absolute top-0 start-0 m-2">{{ $item->name }}</span>
                                        </div>

                                        <div class="card-body d-flex flex-column">
                                            <p class="mb-2 text-muted small">Price:
                                                <strong class="text-dark" id="price-{{ $item->id }}">
                                                    {{ number_format($item->sizes[0]->price ?? 0) }}
                                                </strong>
                                            </p>

                                            <div class="input-group input-group-sm m-2">
                                                <!-- Minus Button -->
                                                <button type="button" class="btn btn-outline-secondary btn-sm btn-minus" data-product-id="{{ $item->id }}">
                                                    <i class="fa-solid fa-circle-minus"></i>
                                                </button>

                                                <!-- Quantity Display -->
                                                <input type="text" class="form-control text-center qty" name="quantity_display"
                                                    value="{{ $existingCart->qty ?? 0 }}" readonly style="max-width: 40px;">

                                                <!-- Plus Button -->
                                                <button type="button" class="btn btn-outline-secondary btn-sm btn-plus" data-product-id="{{ $item->id }}">
                                                    <i class="fa-solid fa-circle-plus"></i>
                                                </button>

                                                <!-- Size Dropdown -->
                                                @if(count($item->sizes) > 0)
                                                    <select name="size"
                                                            class="form-control form-control-sm text-center fw-bold ms-1 size-dropdown"
                                                            data-product-id="{{ $item->id }}"
                                                            style="max-width: 40px; border: 2px solid rgb(255, 166, 0); border-radius: 4px;"
                                                            {{ count($item->sizes) === 1 ? 'disabled' : '' }}>
                                                        @foreach($item->sizes as $size)
                                                            <option value="{{ $size->size }}" data-price="{{ $size->price }}"
                                                                {{ $size->size == $selectedSize ? 'selected' : '' }}>
                                                                {{ strtoupper($size->size[0]) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>

                                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                                <button type="button" class="btn btn-outline-primary btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#noteModal" data-product-id="{{ $item->id }}">
                                                    ✏️
                                                </button>

                                                <button type="submit" class="btn btn-success btn-sm mt-1">Add to Cart</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endforeach

                            <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5>Add Special Instructions</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <textarea class="form-control" id="noteTextarea" rows="3" placeholder="eg.no milk"></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary" id="saveNoteBtn">Save Note</button>

                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                @endif
            </div>

            <!-- Ticket Section -->
            <div class="col-lg-4 ">
                <div class="card">
                    <div class="card-body tab-content">
                        <!-- Ticket Tab Content -->
                        <div class="tab-pane fade show active" id="ticketTab">
                            <table class="table table-hover align-middle">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Items</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-center">Price </th>
                                        <th class="text-center">Size </th>
                                         <th class="text-center">Discount</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($cartItems) && $cartItems->isNotEmpty())
                                        @foreach ($cartItems as $item)
                                            <tr>
                                                <td>
                                                    <p class="mb-0">{{ $item->name }}</p>
                                                </td>
                                                <td class="text-center">
                                                    <p class="mb-0">{{ $item->cart_qty }}</p>
                                                </td>
                                                <td class="text-center">
                                                    <p class="mb-0">{{ number_format($item->price) }}</p>
                                                </td>

                                                <td class="text-center">
                                                    <p class="mb-0">{{ strtoupper(substr($item->size, 0, 1)) }}</p>

                                                </td>
                                                <td class="text-center">
                                                    <p class="mb-0">{{ intval($item->discount_percentage) }}%</p>
                                                </td>
                                                <td class="text-end">
                                                    <p class="mb-0">
                                                        {{ number_format($item->discountPrice * $item->cart_qty) }}</p>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="4" class="text-center">No items in the cart.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            @php
                                $orderType = request('orderType', '');
                                $deliveryLocationId = request('deliveryLocation');
                                $deliveryLocations = \App\Models\DeliveryFees::all(); // fetch from DB
                            @endphp

                            <div class="mt-1">
                                <select id="orderType" name="orderType" class="form-select" onchange="setOrderType(this.value)">
                                    <option value="" disabled {{ !$orderType ? 'selected' : '' }}>Select Order Type</option>
                                    <option value="eat_in" {{ $orderType === 'eat_in' ? 'selected' : '' }}>Eat at Shop</option>
                                    <option value="take_away" {{ $orderType === 'take_away' ? 'selected' : '' }}>Take Away</option>
                                    <option value="delivery" {{ $orderType === 'delivery' ? 'selected' : '' }}>Delivery</option>
                                </select>
                            </div>
                            @if ($orderType === 'delivery')
                                <div class="mt-2">
                                    <select id="deliveryLocation" name="deliveryLocation" class="form-select" onchange="setDeliveryLocation(this.value)">
                                        <option value="" disabled selected>Select a location</option>
                                        @foreach ($deliveryLocations as $location)
                                            <option value="{{ $location->id }}" {{ $deliveryLocationId == $location->id ? 'selected' : '' }}>
                                                {{ $location->township }} ({{ number_format($location->fees) }} MMK)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="mt-3">
                                <!-- Total Summary -->
                                <div class="d-flex justify-content-between">
                                    <span>Subtotal</span>
                                    <span> {{ number_format($subTotal ?? 0, 0) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Tax</span>
                                    <span> {{ number_format($taxAmount ?? 0, 0) }}</span>
                                </div>
                                @php
                                    $orderType = request('orderType', '');
                                    $deliveryLocationId = request('deliveryLocation');
                                    $selectedLocation = \App\Models\DeliveryFees::find($deliveryLocationId);
                                    $deliveryFee = $selectedLocation ? $selectedLocation->fees : 0;
                                @endphp

                                <!-- delivery charges -->
                                @if (!empty($orderType) && $orderType === 'delivery')
                                    <div class="d-flex justify-content-between">
                                        <span>Delivery Fee</span>
                                        <span>{{ number_format($deliveryFee, 0) }}</span>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between">
                                    <span>Total</span>
                                    <span> {{ number_format($total ?? 0, 0) }}</span>
                                    <input type="hidden" id="totalAmount" name="totalAmount"
                                        value="{{ $total ?? 0 }}">
                                    {{-- <input type="number" class="form-control" id="cashReceived" placeholder="Enter cash received" onchange="calculateChange()"> --}}
                                </div>

                            </div>


                            <!-- Payment Method Section  -->
                            <div class="mt-4">
                                <h5>Select Payment Method</h5>
                                <div class="btn-group" role="group" aria-label="Payment Methods">
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="showPaymentSection('cash')">Cash</button>
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="showPaymentSection('card')">Card</button>
                                    <button type="button" class="btn btn-outline-primary"
                                        onclick="showPaymentSection('mobile')">Mobile Payment</button>
                                </div>
                            </div>
                            <div id="paymentDetails" class="mt-4">
                                <!-- Cash Payment Section -->
                                <div id="cashPaymentSection" style="display: none;">
                                    <label for="cashReceived">Cash Received</label>
                                    <input type="number" class="form-control" id="cashReceived"
                                        placeholder="Enter cash received" onchange="calculateChange()">
                                    <p class="mt-2">Change Due: <span id="changeDue">0</span></p>
                                </div>
                                <div id="cardPaymentSection" style="display: none;">
                                    <label for="cardNumber">Card Number</label>
                                    <input type="text" class="form-control" id="cardNumber"
                                        placeholder="XXXX-XXXX-XXXX-XXXX">
                                    <label for="expirationDate" class="mt-2">Expiration Date</label>
                                    <input type="text" class="form-control" id="expirationDate"
                                        placeholder="MM/YY">
                                    <label for="cvv" class="mt-2">CVV</label>
                                    <input type="text" class="form-control" id="cvv" placeholder="CVV">
                                </div>

                                <div id="mobilePaymentSection" style="display: none; text-align: center;">
                                    <p>Scan QR Code or complete payment using the mobile app.</p>
                                    <img class="img-profile img-thumbnail mb-3" id="output"
                                        src="{{ asset('adminProfile/QRCode.png') }}"
                                        style="width: 150px; height: 150px;">
                                    <p class="text-muted" style="font-size: 0.9rem;">Open your mobile payment app and
                                        scan the code to proceed.</p>
                                </div>

                            </div>

                            <div class="d-flex mt-4 justify-content-between">
                                <form action="{{ route('clearCart') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="orderCode" value="{{ $orderCode ?? 0 }}">
                                    <button class="btn btn-outline-secondary">Clear Items</button>
                                </form>

                                <form action="{{ route('orderConfirm') }}" method="POST" id="paymentForm">
                                    @csrf
                                    <input type="hidden" name="orderCode" value="{{ $orderCode ?? 0 }}">
                                    <input type="hidden" id="selectedPaymentMethod" name="paymentMethod" value="">
                                    <input type="hidden" name="orderType" id="hiddenOrderType" value="{{ request('orderType') }}">
                                    <input type="hidden" name="deliveryLocation" id="hiddenDeliveryLocation" value="{{ request('deliveryLocation') }}">
                                    <input type="hidden" name="totalAmount" id="totalAmount">
                                    <input type="hidden" name="notes" id="notes">

                                    <button id="confirm-payment-btn" type="button" class="btn btn-primary">Confirm
                                        Payment</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
<script>
//Dropdown OrderCodes
    document.addEventListener('DOMContentLoaded', () => {
        const dropdownMenuLink = document.getElementById('dropdownMenuLink');
        const orderCodeDropdown = document.getElementById('orderCodeDropdown');

        // Event to fetch order codes when dropdown click
        dropdownMenuLink.addEventListener('click', () => {

            orderCodeDropdown.innerHTML = '';

            // Fetch order codes from the server
            fetch("{{ route('getOrderCodes') }}")
                .then(response => response.json())
                .then(orderCodes => {
                    orderCodes.forEach(orderCode => {
                        const li = document.createElement('li');

                        li.innerHTML =
                            `<a class="dropdown-item order-code-link" href="{{ route('getProductsByCategory') }}?orderCode=${orderCode}" data-order-code="${orderCode}">${orderCode}</a>`;
                        orderCodeDropdown.appendChild(li);
                    });
                })
                .catch(error => console.error('Error fetching order codes:', error));
        });

        // New Order button click event to generate order code
        document.getElementById('newOrderButton').addEventListener('click', () => {
            const orderCode = 'ORD-' + Date.now();

            fetch("{{ route('storeOrderCode') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        orderCode
                    })
                })
                .then(response => {
                    if (response.ok) {
                        document.getElementById('orderCodeDisplay').innerText = orderCode;
                    }
                })
                .catch(error => console.error('Error storing order code:', error));
        });
    });

//Plus or Minus button
    document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-plus').forEach(button => {
        button.addEventListener('click', function () {
            const quantityDisplay = this.closest('.input-group').querySelector('.qty');
            const hiddenQuantityInput = this.closest('form').querySelector('.quantity-input');
            let quantity = parseInt(quantityDisplay.value) || 0;

            quantity += 1;
            quantityDisplay.value = quantity;
            hiddenQuantityInput.value = quantity;
        });
    });

    document.querySelectorAll('.btn-minus').forEach(button => {
        button.addEventListener('click', function () {
            const quantityDisplay = this.closest('.input-group').querySelector('.qty');
            const hiddenQuantityInput = this.closest('form').querySelector('.quantity-input');
            let quantity = parseInt(quantityDisplay.value) || 0;

            if (quantity > 1) {
                quantity -= 1;
                quantityDisplay.value = quantity;
                hiddenQuantityInput.value = quantity;
            }
        });
    });
});

//size and price
    document.querySelectorAll('.size-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const productId = this.getAttribute('data-product-id');
            const priceElement = document.getElementById('price-' + productId);
            if (priceElement) {
                priceElement.textContent = parseInt(price).toLocaleString(); // format with commas
            }
        });
    });

//Payment Method
    function showPaymentSection(method) {
        document.getElementById('cashPaymentSection').style.display = 'none';
        document.getElementById('cardPaymentSection').style.display = 'none';
        document.getElementById('mobilePaymentSection').style.display = 'none';

        if (method === 'cash') {
            document.getElementById('cashPaymentSection').style.display = 'block';
        } else if (method === 'card') {
            document.getElementById('cardPaymentSection').style.display = 'block';
        } else if (method === 'mobile') {
            document.getElementById('mobilePaymentSection').style.display = 'block';
        }

        document.getElementById('selectedPaymentMethod').value = method;
    }

//calculate total payment
    function calculateChange() {
        const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
        const total = {{ $total ?? 0 }};
        const confirmPaymentBtn = document.getElementById('confirm-payment-btn');

        if (cashReceived < total) {
            alert("Not enough balance");
            document.getElementById('changeDue').innerText = "0.00";
            confirmPaymentBtn.disabled = true;
            return;
        }
        const changeDue = cashReceived - total;
        document.getElementById('changeDue').innerText = changeDue.toFixed(2);
        confirmPaymentBtn.disabled = false;

    }


//Confirm Payment
    document.getElementById("confirm-payment-btn").addEventListener("click", function (event) {
    event.preventDefault();


    const paymentMethod = document.getElementById('selectedPaymentMethod').value;
    const selectedOrderType = document.getElementById('orderType').value;


    const formData = new FormData(document.getElementById('paymentForm'));
    const totalAmount = {{ $total ?? 0 }};

    document.getElementById('hiddenOrderType').value = selectedOrderType;

    if (selectedOrderType !== 'delivery') {
    formData.delete('deliveryLocation'); // remove it if it exists
    }

     formData.append('totalAmount', totalAmount);

    if (paymentMethod === 'cash') {
        const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
        const changeDue = parseFloat(document.getElementById('changeDue').innerText) || 0;

        if (cashReceived < totalAmount) {
            alert("Not enough balance.");
            return;
        }

        formData.append('cashReceived', cashReceived);
        formData.append('changeDue', changeDue);
    } else {
// Card and Mobile don't need these
        formData.append('cashReceived', totalAmount);
        formData.append('changeDue', 0);
    }

    fetch('{{ route('orderConfirm') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(async response => {
        if (!response.ok) {
            const errorText = await response.text();
            console.error("Server error:", errorText);
            throw new Error("Failed to confirm the order.");
        }
        return response.json();
    })
    .then(data => {
//  Generate receipt
        return fetch('{{ route('generatePaymentSlip') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                orderCode: data.orderCode,
                cashReceived: formData.get('cashReceived'),
                changeDue: formData.get('changeDue')
            })
        });
    })
    .then(response => response.text())
    .then(html => {
        const newWindow = window.open("", "Print Payment Slip", "width=600,height=400");
        newWindow.document.write(html);
        newWindow.document.close();
        newWindow.print();
        window.location.href = '{{ route('adminDashboard') }}';
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred while processing the payment. Please check the booking data.");
    });
});

//note modal
    let selectedProductId = null;
    // When "Add Note" button is clicked
    document.querySelectorAll('[data-bs-target="#noteModal"]').forEach(button => {
            button.addEventListener('click', function () {
                selectedProductId = this.getAttribute('data-product-id');
                document.getElementById('noteTextarea').value = document.getElementById('noteInput_' + selectedProductId)?.value || '';
            });
        });

// When "Save Note" button in Modal is clicked
    document.getElementById('saveNoteBtn').addEventListener('click', function () {
        const note = document.getElementById('noteTextarea').value;
        if (selectedProductId) {
            document.getElementById('noteInput_' + selectedProductId).value = note;
        }
        // Hide modal
        var modal = bootstrap.Modal.getInstance(document.getElementById('noteModal'));
        modal.hide();
    });


//when order type is delivery
    function setOrderType(orderType) {
        const url = new URL(window.location.href);
        url.searchParams.set('orderType', orderType);
        url.searchParams.delete('deliveryLocation');
        window.location.href = url.toString(); // full page reload with ?orderType=delivery
    }

    function setDeliveryLocation(locationId) {
        const url = new URL(window.location.href);
        url.searchParams.set('deliveryLocation', locationId);
        window.location.href = url.toString();
    }

</script>
