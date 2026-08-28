@extends('user.layouts.master')
@section('content')
    <section class="container my-4">
        <h2 class="text-center text-white mb-4">Our Menu</h2>
        <div class="row g-4">
            <div class="col-lg-12">

                <div class="row g-4 mb-4">
                    <div class="col-xl-3">
                        <form action="{{ route('climenu') }}" method="get">
                            <div class="input-group w-100 mx-auto d-flex">
                                <input type="search" class="form-control p-3" value="{{ request('searchKey') }}"
                                    name="searchKey" placeholder="keywords">
                                <button type="submit" class="input-group-text p-3"><i class="fa fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                    <div class="col-xl-3">
                        <form action="{{ route('reviewOrder') }}" method="get">
                            @csrf
                            <div class="input-group w-100 mx-auto d-flex">
                                <input type="hidden" name="order_code" value="{{ request('order_code') }}"
                                    class="form-control my-2" placeholder="">
                                <button type="submit" class="input-group-text p-3 text-white"
                                    {{ $orderCount == 0 ? 'disabled' : '' }}>
                                    {{ $orderCount == 0 ? 'No orders to view' : 'View your order' }}
                                </button>
                            </div>
                        </form>
                    </div>
                    @if($cartCount > 0)
                    <div class="col w-80">
                        <!-- Global View Cart Button -->
                        <div class="text-end">
                            <a href="{{ route('cartPage') }}" class="btn btn-primary">
                                <i class="fa fa-shopping-bag me-2"></i> View Cart
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="row g-4">
                    <div class="col-lg-3">
                        <!-- Sidebar -->
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <h4 class="text-white">Categories</h4>
                                <ul class="list-unstyled fruite-categorie">
                                    <li>
                                        <div class="d-flex justify-content-between fruite-name">
                                            <a href="{{ route('climenu') }}"><i class="fa-solid fa-list"></i> All Categories</a>
                                        </div>
                                    </li>
                                    @foreach ($categories as $item)
                                        <li>
                                            <div class="d-flex justify-content-between fruite-name">
                                                <a href="{{ route('climenu', $item->id) }}"><i class="fa-regular fa-circle-dot"></i> {{ $item->name }}</a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="row g-3">
                            @foreach ($products as $item)
                                <div class="col-lg-6">
                                    <div class="card shadow-sm h-100 p-2">
                                        <div class="row g-2 align-items-center">
                                            <!-- Image on Left -->
                                            <div class="col-5">
                                                <div class="position-relative">
                                                    <img src="{{ asset('productImages/' . $item->image) }}"
                                                        class="img-fluid rounded"
                                                        style="height: 120px; object-fit: cover; width: 100%;" alt="">
                                                    <small class="badge bg-primary position-absolute top-0 start-0 m-1">
                                                        {{ $item->category_name }}
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- Info in Center -->
                                            <div class="col-6">
                                                <h6 class="text-dark mb-1">{{ $item->name }}</h6>

                                               <div class="price-info mb-1">
                                                    <small class="text-muted d-block" style="font-size: 11px;">Prices:</small>
                                                    @foreach($item->sizes->take(3) as $size)
                                                        <div class="d-flex justify-content-between align-items-center" style="font-size: 12px;">
                                                            @php
                                                                $hasDiscount = $item->discount_percentage && $item->discountPrice;
                                                                $originalPrice = $size->price;
                                                                $discountedPrice = round($size->price - ($size->price * $item->discount_percentage / 100), 2);
                                                            @endphp
                                                            <span>
                                                                @if ($hasDiscount)
                                                                    <span class="text-danger fw-bold">MMK {{ $discountedPrice }}</span>
                                                                    <del class="text-muted" style="font-size: 11px;">MMK {{ $originalPrice }}</del>
                                                                @else
                                                                    <span class="text-dark fw-semibold">MMK {{ $originalPrice }}</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <p class="text-muted mb-2" style="font-size: 12px;">
                                                    {{ Str::words($item->description, 8, '...') }}
                                                </p>

                                                <form action="{{ route('addToCart', $item->id) }}" method="POST" class="d-flex align-items-center">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $item->id }}">
                                                    <input type="hidden" name="quantity" value="1" class="quantity-input">
                                                    <input type="hidden" name="orderCode" value="{{ $orderCode ?? 0 }}">
                                                    <input type="hidden" name="notes" id="noteInput_{{ $item->id }}">

                                                    @if(count($item->sizes ?? []) > 0)
                                                        <input type="hidden" name="size" value="{{ $item->sizes[0]->size }}">
                                                        <select name="size"
                                                                class="form-control form-control-sm text-center fw-bold ms-1 size-dropdown"
                                                                data-product-id="{{ $item->id }}"
                                                                style="max-width: 40px; border: 2px solid rgb(255, 166, 0); border-radius: 4px;"
                                                                {{ count($item->sizes) === 1 ? 'disabled' : '' }}>
                                                            @foreach($item->sizes as $size)
                                                                <option value="{{ $size->size }}" data-price="{{ $size->price }}">
                                                                    {{ strtoupper($size->size[0]) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @endif

                                                    <!-- Add Note Button -->
                                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#noteModal" data-product-id="{{ $item->id }}">
                                                    ✏️
                                                    </button>

                                                    <!-- Trash -->
                                                    <button type="button" formaction="{{ route('removeCart', $item->id) }}" class="btn btn-danger btn-sm remove-from-cart-btn d-none" data-product-id="{{ $item->id }}">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>

                                                    <!-- Quantity -->
                                                    <input type="text" class="form-control form-control-sm text-center quantity-display d-none"
                                                        data-product-id="{{ $item->id }}" value="{{ $item->cartQty ?? 0 }}" readonly
                                                        style="width: 40px; font-size: 10px; border: 2px solid rgb(255, 38, 0); border-radius: 2px;">

                                                    <!-- Plus -->
                                                    <button type="button" class="btn btn-success btn-sm add-to-cart-btn" data-product-id="{{ $item->id }}">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </form>

                                            </div>

                                        </div>
                                    </div>
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

                            <!-- Pagination -->
                            <div class="col-12">
                                <div class="pagination d-flex justify-content-center mt-4">
                                    {{ $products->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
//plus, quantity and trash in product card
    document.addEventListener('DOMContentLoaded', function () {
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        const removeFromCartButtons = document.querySelectorAll('.remove-from-cart-btn');
        const hideTimers = {};

        addToCartButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault(); // prevent normal form submit

                const productId = this.dataset.productId;
                const form = this.closest('form');
                const trashButton = form.querySelector(`.remove-from-cart-btn[data-product-id="${productId}"]`);
                const quantityDisplay = form.querySelector(`.quantity-display[data-product-id="${productId}"]`);
                const hiddenQuantityInput = form.querySelector('.quantity-input');

                let quantity = parseInt(quantityDisplay.value);

                quantity += 1;
                quantityDisplay.value = quantity;
                hiddenQuantityInput.value = quantity;

                // Show trash and quantity if quantity >= 1
                if (quantity >= 1) {
                    trashButton.classList.remove('d-none');
                    quantityDisplay.classList.remove('d-none');
                }

                // Submit form
                form.submit();

                // Clear previous timer if exists
                if (hideTimers[productId]) {
                    clearTimeout(hideTimers[productId]);
                }

                // Start a new timer to hide after 5 seconds
                hideTimers[productId] = setTimeout(() => {
                    const currentQty = parseInt(quantityDisplay.value) || 0;
                    if (currentQty <= 0) {
                        trashButton.classList.add('d-none');
                        quantityDisplay.classList.add('d-none');
                    }
                }, 5000); // 5000 ms = 5 seconds
            });
        });

        removeFromCartButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault(); // prevent normal form submit

                const productId = this.dataset.productId;
                const form = this.closest('form');
                const trashButton = form.querySelector(`.remove-from-cart-btn[data-product-id="${productId}"]`);
                const quantityDisplay = form.querySelector(`.quantity-display[data-product-id="${productId}"]`);
                const hiddenQuantityInput = form.querySelector('.quantity-input');
                const hideTimers = {};

                let quantity = parseInt(quantityDisplay.value);

                quantity -= 1;
                if (quantity <= 0) {
                    quantity = 0;
                    trashButton.classList.add('d-none');
                    quantityDisplay.classList.add('d-none');
                }
                quantityDisplay.value = quantity;
                hiddenQuantityInput.value = quantity;

                // Submit form
                form.submit();

                // Clear previous timer if exists
                if (hideTimers[productId]) {
                    clearTimeout(hideTimers[productId]);
                }

                // Start a new timer to hide after 5 seconds
                hideTimers[productId] = setTimeout(() => {
                    const currentQty = parseInt(quantityDisplay.value) || 0;
                    if (currentQty <= 0) {
                        trashButton.classList.add('d-none');
                        quantityDisplay.classList.add('d-none');
                    }
                }, 5000); // 5000 ms = 5 seconds
            });
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

    </script>
@endsection
