@extends('user.layouts.master')
@section('content')
<section class="container my-5 py-4" style="background-color: #f8f9fa; border-radius: 10px;">
    <div class="row d-flex justify-content-center align-items-center">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <!-- Cart Section -->
                    <a href="{{ route('climenu') }}" class="btn btn-primary justify-content-end">
                        <i class="fa-solid fa-arrow-left me-2"></i>Back
                    </a>
                    <h2 class="mb-2 text-center">Your Order</h2>
                    <div class="table-responsive">
                        <!-- Cart Table -->
                        <table class="table table-hover align-middle text-center">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Order Code</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Original Price</th>
                                    <th>Discounted Price</th>
                                    <th>Size</th>
                                    <th>Quantity</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $item)
                                    <tr>
                                        <td class="text-primary align-middle">{{ $item->order_code }}</td>
                                        {{-- <td class="align-middle">{{ $item->created_at->format('j-F-y') }}</td> --}}
                                        <td class="align-middle">
                                            <img src="{{ asset('productImages/' . $item->image) }}"
                                                 alt="{{ $item->name }}" class="img-fluid rounded-circle"
                                                 style="width: 50px; height: 50px;">
                                        </td>
                                        <td class="align-middle">{{ $item->name }}</td>
                                        <td class="align-middle">{{ number_format($item->price) }}</td>
                                        <td class="align-middle">{{ number_format($item->totalprice) }}</td>
                                        <td class="align-middle">{{ $item->size }}</td>
                                        <td class="align-middle">{{ $item->quantity }}</td>
                                        <td class="align-middle">{{ $item->notes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- Summary Section -->
                    <div class="mt-4 text-end">
                        <h5 class="fw-bold">Total: {{ number_format($orderTotal) }} MMK</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
