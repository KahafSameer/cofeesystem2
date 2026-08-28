@extends('admin.layouts.master')

@section('content')
<section class="container-fluid py-4">
    <h4 class="mb-3">Add Sizes & Prices for <strong>{{ $product->name }}</strong></h4>

    <form action="{{ route('prodsizestore', $product->id) }}" method="POST">
        @csrf
        <div id="sizePriceContainer">
            <div class="row mb-2 size-price-row">
                <div class="col-md-5">
                    <input type="text" name="sizes[]" class="form-control" placeholder="Size (e.g. Small)" required>
                </div>
                <div class="col-md-5">
                    <input type="number" name="prices[]" class="form-control" step="10" placeholder="Price (MMK)" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger w-100 remove-size-price">Remove</button>
                </div>
            </div>
        </div>

        <button type="button" id="addSizePrice" class="btn btn-primary btn-sm mt-2">+ Add Size & Price</button>

        <div class="mt-4">
            <button type="submit" class="btn btn-success">Save Sizes</button>
            <a href="{{ route('product.prodlist') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </form>
</section>

@endsection

@section('scripts')
<script>
    document.getElementById('addSizePrice').addEventListener('click', function () {
        const container = document.getElementById('sizePriceContainer');
        const row = document.createElement('div');
        row.classList.add('row', 'mb-2', 'size-price-row');

        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" name="sizes[]" class="form-control" placeholder="Size (e.g. Medium)" required>
            </div>
            <div class="col-md-5">
                <input type="number" name="prices[]" class="form-control" step="0.01" placeholder="Price (MMK)" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger w-100 remove-size-price">Remove</button>
            </div>
        `;
        container.appendChild(row);
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-size-price')) {
            e.target.closest('.size-price-row').remove();
        }
    });
</script>
@endsection

