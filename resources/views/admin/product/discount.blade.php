@extends('admin.layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center mt-3">
        <div class="col-md-6">
            <div class="card shadow-lg p-2">
                <div class="card-body">
                    <h3 class="text-dark fw-bold mb-4 text-center">Add Discount</h3>

                    <!-- Form Starts Here -->
                    <form action="{{ route('adddiscount') }}" method="POST">
                        @csrf

                        <!-- Apply to All Products Checkbox -->
                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="apply_to_all" name="apply_to_all" value="1">
                            <label class="form-check-label" for="apply_to_all">Apply discount to all products</label>
                        </div>

                        <!-- Category Selector (shown only if "Apply to All" is unchecked) -->
                        <div id="category-selector" class="form-group mb-4">
                            <label for="product" class="form-label">Product Name</label>
                            <select name="product_id" id="product" class="form-control @error('product_id') is-invalid @enderror">
                                <option value="">Choose Product...</option>
                                @foreach ($product as $item)
                                    <option value="{{ $item->id }}" @if (old('product_id') == $item->id) selected @endif>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group mb-4">
                            <label for="discount_percentage" class="form-label">Discount Percentage (%)</label>
                            <input type="number" name="discount_percentage" id="discount_percentage" class="form-control @error('discount_percentage') is-invalid @enderror" min="0" max="100" placeholder="Enter discount percentage">
                            @error('discount_percentage')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col"> <div class="form-group mb-4">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror">
                                @error('start_date')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                            <div class="col">
                                <div class="form-group mb-4">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror">
                                    @error('end_date')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 shadow-sm">Create Discount</button>
                    </form>
                    <!-- Form Ends Here -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const checkbox = document.getElementById('apply_to_all');
        const categorySelector = document.getElementById('category-selector');

        if(checkbox && categorySelector){
            categorySelector.style.display = checkbox.checked ? 'none' : 'block';

            checkbox.addEventListener('change', function(){
                categorySelector.style.display = checkbox.checked ? 'none' : 'block';
            });
        }
    });
</script>
