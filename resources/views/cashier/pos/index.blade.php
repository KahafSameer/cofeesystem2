@extends('admin.layouts.master')

@section('content')
    <link rel="stylesheet" href="{{ asset('admin/CSS/booking.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/CSS/cashier.css') }}">

    <div class="container-fluid mt-4 cashier-pos-wrap">
        <!-- Active Tickets strip -->
        <div class="card pos-section mb-4">
            <div class="card-header pos-section-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-ticket-alt me-2"></i>Active Tickets</span>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('cashier.sessions') }}" class="btn btn-running btn-sm">
                        <i class="fa-solid fa-receipt me-1"></i> Running Bills
                    </a>
                    <button type="button" class="btn btn-new-order btn-sm" id="newOrderButton">
                        <i class="fa-solid fa-plus me-1"></i> NEW ORDER
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if ($drafts->isNotEmpty())
                    <div class="row g-3">
                        @foreach ($drafts as $draft)
                            @php
                                $sum = $summary[$draft->order_code] ?? ['item_count' => 0, 'total' => 0];
                                $isCurrent = $current && $current->order_code === $draft->order_code;
                            @endphp
                            <div class="col-md-4 mb-1">
                                <div class="ticket-card {{ $isCurrent ? 'is-current' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="ticket-code">#{{ $draft->order_code }}</span>
                                        @if ($isCurrent)
                                            <span class="badge status-badge current">Current</span>
                                        @elseif ($draft->status === 'suspended')
                                            <span class="badge status-badge suspended">Suspended</span>
                                        @else
                                            <span class="badge status-badge active">Active</span>
                                        @endif
                                    </div>
                                    <div class="ticket-summary mt-1">
                                        <strong>{{ $sum['item_count'] }}</strong> Items &middot; PKR <strong>{{ number_format($sum['total'], 0) }}</strong>
                                    </div>
                                    <div class="mt-2 d-flex flex-wrap gap-1 ticket-actions">
                                        @if (! $isCurrent)
                                            <form action="{{ route('cashier.resume', $draft->order_code) }}" method="POST" class="me-1">
                                                @csrf
                                                <button class="btn btn-sm btn-ticket-continue">Continue</button>
                                            </form>
                                            <form action="{{ route('cashier.discard', $draft->order_code) }}" method="POST"
                                                onsubmit="return confirm('Discard ticket {{ $draft->order_code }}? This cannot be undone.')">
                                                @csrf
                                                <button class="btn btn-sm btn-ticket-discard">Discard</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-ticket-hold hold-ticket-btn"
                                                data-code="{{ $draft->order_code }}">
                                                <i class="fa-solid fa-pause me-1"></i>Hold / Suspend
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">No active tickets. Click <strong>NEW ORDER</strong> to start.</p>
                @endif
            </div>
        </div>

        @if ($current)
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="{{ route('adminDashboard') }}" class="btn btn-coffee d-inline-flex align-items-center">
                            <i class="fa-solid fa-arrow-left me-2"></i>Dashboard
                        </a>
                        <div class="pos-ticket-title">
                            <i class="fa-solid fa-receipt me-1"></i>Current Ticket: <strong>#{{ $current->order_code }}</strong>
                        </div>
                    </div>

                    <!-- Category filter -->
                    <div class="row mb-4">
                        @foreach ($categories as $category)
                            <div class="col-md-2 col-3 mb-2">
                                <form action="{{ route('cashier.index') }}" method="GET">
                                    @csrf
                                    <input type="hidden" name="categoryId" value="{{ $category->id }}">
                                    <button type="submit" class="category-btn w-100 {{ $selectedCategoryId == $category->id ? 'selected' : '' }}">
                                        <div class="card category-card text-center">
                                            <h6 class="card-title mb-0">{{ $category->name }}</h6>
                                        </div>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    <!-- Product grid -->
                    @if ($selectedCategoryId)
                        @if ($productbyCategory->isNotEmpty())
                            <div class="row">
                                @foreach ($productbyCategory as $item)
                                    <div class="col-md-4 col-6 mb-4">
                                        @php
                                            $defaultSize = $item->sizes[0]->size ?? 'Medium';
                                        @endphp
                                        <form action="{{ route('cashier.cart.add') }}" method="POST" class="h-100 d-flex flex-column">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $item->id }}">
                                            <input type="hidden" name="quantity" class="quantity-input" value="1">
                                            <input type="hidden" name="notes" id="noteInput_{{ $item->id }}">
                                            @if (count($item->sizes) === 1)
                                                <input type="hidden" name="size" value="{{ $defaultSize }}">
                                            @endif
                                            <div class="card h-100 product-card">
                                                <div class="position-relative">
                                                    <img src="{{ asset('productImages/' . $item->image) }}"
                                                        class="card-img-top product-image" alt="{{ $item->name }}">
                                                    <span class="badge product-badge position-absolute top-0 start-0 m-2">{{ $item->name }}</span>
                                                </div>
                                                <div class="card-body d-flex flex-column">
                                                    <p class="mb-2 text-muted small pos-price">Price:
                                                        <strong class="text-dark" id="price-{{ $item->id }}">
                                                            {{ number_format($item->sizes[0]->price ?? 0) }}
                                                        </strong>
                                                    </p>
                                                    <div class="input-group input-group-sm m-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-minus" data-product-id="{{ $item->id }}">
                                                            <i class="fa-solid fa-circle-minus"></i>
                                                        </button>
                                                        <input type="text" class="form-control text-center qty" value="1"
                                                            readonly style="max-width: 40px;">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-plus" data-product-id="{{ $item->id }}">
                                                            <i class="fa-solid fa-circle-plus"></i>
                                                        </button>
                                                        @if (count($item->sizes) > 0)
                                                            <select name="size"
                                                                class="form-control form-control-sm text-center fw-bold ms-1 size-dropdown"
                                                                data-product-id="{{ $item->id }}"
                                                                style="max-width: 44px;"
                                                                {{ count($item->sizes) === 1 ? 'disabled' : '' }}>
                                                                @foreach ($item->sizes as $size)
                                                                    <option value="{{ $size->size }}" data-price="{{ $size->price }}"
                                                                        {{ $size->size == $defaultSize ? 'selected' : '' }}>
                                                                        {{ strtoupper($size->size[0]) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    </div>
                                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                                        <button type="button" class="btn btn-outline-primary btn-sm ms-1 pos-note-btn"
                                                            data-bs-toggle="modal" data-bs-target="#noteModal" data-product-id="{{ $item->id }}">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>
                                                        <button type="submit" class="btn btn-success btn-sm mt-1 pos-add-btn">Add to Ticket</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p>No products in this category.</p>
                        @endif
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="pos-ticket-wrap">
                        <div class="pos-ticket-header d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-receipt me-1"></i>Ticket #{{ $current->order_code }}</span>
                            <span class="badge pos-ticket-count">{{ $itemCount }} items</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                            <table class="table table-hover align-middle pos-ticket-items">
                                <thead>
                                    <tr>
                                        <th>Items</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Amount</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($cartItems as $item)
                                        <tr>
                                            <td>
                                                <p class="mb-0 item-name">{{ $item->name }}</p>
                                                <small class="text-muted item-note d-block">{{ $item->notes }}</small>
                                                <small class="text-muted">{{ strtoupper(substr($item->size, 0, 1)) }}</small>
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('cashier.cart.update') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="cart_id" value="{{ $item->cartId }}">
                                                    <input type="number" name="qty" min="1" value="{{ $item->cart_qty }}"
                                                        class="form-control form-control-sm text-center" style="width: 60px;"
                                                        onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="text-end line-amount">{{ number_format($item->discountPrice * $item->cart_qty) }}</td>
                                            <td class="text-center">
                                                <form action="{{ route('cashier.cart.remove') }}" method="POST"
                                                    onsubmit="return confirm('Remove this item?')">
                                                    @csrf
                                                    <input type="hidden" name="cart_id" value="{{ $item->cartId }}">
                                                    <button class="btn btn-sm remove-item-btn"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No items on this ticket yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            </div>

                            <!-- Order type (persisted per ticket) -->
                            <div class="mt-1">
                                <label class="form-label pos-pay-title"><i class="fa-solid fa-burger me-1"></i>Order Type</label>
                                <select id="orderType" name="orderType" class="form-select">
                                    <option value="eat_in" {{ $orderType === 'eat_in' ? 'selected' : '' }}>Eat at Shop</option>
                                    <option value="take_away" {{ $orderType === 'take_away' ? 'selected' : '' }}>Take Away</option>
                                    <option value="delivery" {{ $orderType === 'delivery' ? 'selected' : '' }}>Delivery</option>
                                </select>
                            </div>
                            @if ($orderType === 'delivery')
                                <div class="mt-2">
                                    <select id="deliveryLocation" class="form-select">
                                        <option value="" disabled {{ ! $current->delivery_location_id ? 'selected' : '' }}>Select a location</option>
                                        @foreach ($deliveryLocations as $location)
                                            <option value="{{ $location->id }}"
                                                {{ $current->delivery_location_id == $location->id ? 'selected' : '' }}>
                                                {{ $location->township }} ({{ number_format($location->fees) }} PKR)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <!-- Totals -->
                            <div class="pos-totals mt-4">
                                <div class="row-line"><span>Items</span><span>{{ $itemCount }}</span></div>
                                <div class="row-line"><span>Subtotal</span><span>PKR {{ number_format($subTotal, 0) }}</span></div>
                                <div class="row-line"><span>Tax</span><span>PKR {{ number_format($taxAmount, 0) }}</span></div>
                                @if ($orderType === 'delivery')
                                    <div class="row-line"><span>Delivery Fee</span><span>PKR {{ number_format($deliveryFee, 0) }}</span></div>
                                @endif
                                <div class="total-line">
                                    <span>Total</span>
                                    <span class="pos-total-amount">PKR {{ number_format($total, 0) }}</span>
                                </div>
                            </div>

                            <!-- Payment -->
                            <div class="mt-4">
                                <label class="form-label pos-pay-title"><i class="fa-solid fa-money-bill-wave me-1"></i>Payment Method</label>
                                <input type="hidden" id="selectedPaymentMethod" value="">
                                <div class="d-grid gap-2 d-md-flex justify-content-start" role="group">
                                    <button type="button" class="btn payment-method-btn" data-method="cash" onclick="showPaymentSection('cash')">Cash</button>
                                    <button type="button" class="btn payment-method-btn" data-method="card" onclick="showPaymentSection('card')">Card</button>
                                    <button type="button" class="btn payment-method-btn" data-method="mobile" onclick="showPaymentSection('mobile')">Mobile</button>
                                </div>
                            </div>
                            <div id="paymentDetails" class="mt-3">
                                <div id="cashPaymentSection" style="display: none;">
                                    <label class="form-label" for="cashReceived">Cash Received</label>
                                    <input type="number" class="form-control" id="cashReceived" min="0"
                                        placeholder="Enter cash received" onchange="calculateChange()">
                                    <p class="mt-2">Change Due: <span id="changeDue">0.00</span></p>
                                </div>
                                <div id="cardPaymentSection" style="display: none;">
                                    <label class="form-label" for="cardNumber">Card Number</label>
                                    <input type="text" class="form-control" id="cardNumber" placeholder="XXXX-XXXX-XXXX-XXXX">
                                    <label class="form-label mt-2" for="expirationDate">Expiration Date</label>
                                    <input type="text" class="form-control" id="expirationDate" placeholder="MM/YY">
                                    <label class="form-label mt-2" for="cvv">CVV</label>
                                    <input type="text" class="form-control" id="cvv" placeholder="CVV">
                                </div>
                                <div id="mobilePaymentSection" style="display: none; text-align: center;">
                                    <p class="text-muted">Scan the QR code with your mobile payment app.</p>
                                </div>
                            </div>

                            <div class="d-flex mt-4 justify-content-between gap-2">
                                <button type="button" class="btn btn-hold hold-ticket-btn"
                                    data-code="{{ $current->order_code }}">
                                    <i class="fa-solid fa-pause me-1"></i>Hold / Suspend
                                </button>
                                <button id="confirm-payment-btn" type="button" class="btn btn-coffee pos-confirm-btn"
                                    {{ $cartItems->isEmpty() ? 'disabled' : '' }}>
                                    <i class="fa-solid fa-check-circle me-1"></i>Confirm &amp; Pay
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Note modal -->
            <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header pos-modal-header">
                            <h5 class="modal-title mb-0"><i class="fa-solid fa-pen me-2"></i>Add Special Instructions</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="close"></button>
                        </div>
                        <div class="modal-body">
                            <textarea class="form-control" id="noteTextarea" rows="3" placeholder="eg. no milk"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-coffee" id="saveNoteBtn">Save Note</button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Empty state -->
            <div class="text-center py-5 pos-empty-state">
                <div class="icon-circle">
                    <i class="fa-solid fa-cash-register fa-3x"></i>
                </div>
                <h4>No Active Ticket</h4>
                <p>Click <strong>NEW ORDER</strong> to start a new ticket for the next customer.</p>
                <button type="button" class="btn btn-coffee btn-lg" id="newOrderButtonEmpty">
                    <i class="fa-solid fa-plus me-1"></i> NEW ORDER
                </button>
            </div>
        @endif
</div>
@endsection
@section('scripts')
    <script>
    // NEW ORDER - create an independent empty ticket and reload
    function newOrder() {
        fetch("{{ route('cashier.new') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json",
                "Accept": "application/json"
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = "{{ route('cashier.index') }}";
            } else {
                alert(data.error || 'Could not create a new ticket.');
            }
        })
        .catch(() => alert('Could not create a new ticket.'));
    }

    // HOLD / SUSPEND - the current ticket is saved, no order/payment is created
    function holdTicket(code) {
        fetch("{{ url('cashier') }}/" + code + "/suspend", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json",
                "Accept": "application/json"
            }
        })
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || data.status !== 'success') {
                throw new Error(data.error || 'Could not hold the ticket.');
            }
            window.location.reload();
        })
        .catch(error => alert(error.message || 'Could not hold the ticket.'));
    }

    document.addEventListener('DOMContentLoaded', () => {
        const newOrderButton = document.getElementById('newOrderButton');
        if (newOrderButton) newOrderButton.addEventListener('click', newOrder);
        const newOrderButtonEmpty = document.getElementById('newOrderButtonEmpty');
        if (newOrderButtonEmpty) newOrderButtonEmpty.addEventListener('click', newOrder);

        document.querySelectorAll('.hold-ticket-btn').forEach(btn => {
            btn.addEventListener('click', () => holdTicket(btn.getAttribute('data-code')));
        });

        // Plus / Minus quantity
        document.querySelectorAll('.btn-plus').forEach(button => {
            button.addEventListener('click', function () {
                const qty = this.closest('.input-group').querySelector('.qty');
                const hidden = this.closest('form').querySelector('.quantity-input');
                let v = parseInt(qty.value) || 0;
                v += 1;
                qty.value = v;
                hidden.value = v;
            });
        });

        document.querySelectorAll('.btn-minus').forEach(button => {
            button.addEventListener('click', function () {
                const qty = this.closest('.input-group').querySelector('.qty');
                const hidden = this.closest('form').querySelector('.quantity-input');
                let v = parseInt(qty.value) || 0;
                if (v > 1) {
                    v -= 1;
                    qty.value = v;
                    hidden.value = v;
                }
            });
        });

        // Size -> price preview
        document.querySelectorAll('.size-dropdown').forEach(dropdown => {
            dropdown.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                const priceElement = document.getElementById('price-' + this.getAttribute('data-product-id'));
                if (priceElement) priceElement.textContent = parseInt(price).toLocaleString();
            });
        });

        // Order type / delivery location are persisted per ticket
        const orderTypeSelect = document.getElementById('orderType');
        if (orderTypeSelect) {
            orderTypeSelect.addEventListener('change', persistOrderMeta);
        }
        const deliveryLocationSelect = document.getElementById('deliveryLocation');
        if (deliveryLocationSelect) {
            deliveryLocationSelect.addEventListener('change', persistOrderMeta);
        }
    });

    function persistOrderMeta() {
        const orderType = document.getElementById('orderType').value;
        const deliveryLocation = document.getElementById('deliveryLocation');

        fetch("{{ route('cashier.orderType') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                orderType: orderType,
                deliveryLocation: deliveryLocation && deliveryLocation.value ? deliveryLocation.value : null
            })
        })
        .then(() => window.location.reload());
    }

    // Payment method toggling (keeps the coffee "active" highlight in sync)
    function showPaymentSection(method) {
        ['cashPaymentSection', 'cardPaymentSection', 'mobilePaymentSection'].forEach(id => {
            document.getElementById(id).style.display = 'none';
        });
        document.getElementById(method === 'cash' ? 'cashPaymentSection'
            : method === 'card' ? 'cardPaymentSection' : 'mobilePaymentSection').style.display = 'block';
        document.getElementById('selectedPaymentMethod').value = method;

        document.querySelectorAll('.payment-method-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-method') === method);
        });
    }

    function calculateChange() {
        const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
        const total = {{ $total ?? 0 }};
        const btn = document.getElementById('confirm-payment-btn');
        if (cashReceived < total) {
            document.getElementById('changeDue').innerText = '0.00';
            btn.disabled = true;
            alert('Not enough cash received.');
            return;
        }
        document.getElementById('changeDue').innerText = (cashReceived - total).toFixed(2);
        btn.disabled = false;
    }

    // Confirm & Pay -> existing Order + PaymentRecord + existing payment slip
    document.getElementById('confirm-payment-btn').addEventListener('click', function () {
        const btn = this;
        btn.disabled = true;

        const paymentMethod = document.getElementById('selectedPaymentMethod').value;
        if (!paymentMethod) {
            alert('Select a payment method first.');
            btn.disabled = false;
            return;
        }

        const orderCode = "{{ $orderCode }}";
        const orderType = document.getElementById('orderType').value;
        const total = {{ $total ?? 0 }};

        const body = {
            orderCode: orderCode,
            paymentMethod: paymentMethod,
            orderType: orderType,
            totalAmount: total,
            changeDue: 0
        };

        if (orderType === 'delivery') {
            body.deliveryLocation = document.getElementById('deliveryLocation')?.value || null;
        }

        if (paymentMethod === 'cash') {
            const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
            if (cashReceived < total) {
                alert('Not enough cash received.');
                btn.disabled = false;
                return;
            }
            body.cashReceived = cashReceived;
            body.changeDue = cashReceived - total;
        } else {
            body.cashReceived = total;
        }

        fetch("{{ url('cashier') }}/" + orderCode + "/charge", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(body)
        })
        .then(response => response.json().then(data => ({ ok: response.ok, status: response.status, data })))
        .then(({ ok, status, data }) => {
            if (!ok || !data.orderCode) {
                throw new Error(data.error || 'Failed to finalize the ticket.');
            }
            // Reuse the existing bill/payment-slip engine
            return fetch("{{ route('generatePaymentSlip') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({ orderCode: data.orderCode })
            });
        })
        .then(response => response.text())
        .then(html => {
            const printWindow = window.open("", "Print Payment Slip", "width=600,height=400");
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.print();
            window.location.href = "{{ route('cashier.index') }}";
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'An error occurred while processing the payment.');
            btn.disabled = false;
        });
    });

    // Note modal
    let selectedProductId = null;
    document.querySelectorAll('[data-bs-target="#noteModal"]').forEach(button => {
        button.addEventListener('click', function () {
            selectedProductId = this.getAttribute('data-product-id');
            document.getElementById('noteTextarea').value = document.getElementById('noteInput_' + selectedProductId)?.value || '';
        });
    });
    document.getElementById('saveNoteBtn').addEventListener('click', function () {
        const note = document.getElementById('noteTextarea').value;
        if (selectedProductId) {
            document.getElementById('noteInput_' + selectedProductId).value = note;
        }
        const modal = bootstrap.Modal.getInstance(document.getElementById('noteModal'));
        modal.hide();
    });
    </script>
@endsection