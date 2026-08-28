@extends('admin.layouts.master')

@section('content')
    <!-- Begin Page Content -->
    <section class="container-fluid py-4">
        <div class="row d-flex justify-content-center align-items-center">
            <div class="col-lg-10">
                <div  class="card border-1 shadow-sm p-4">
                    <h2 class=" fw-bold mb-4">Add New Product</h2>

                        <form action="{{ route('product.prodstore') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row mt-2">
                                <div class="col-md-4 text-center">
                                    <img id="output" src="{{ asset('defaultImg/default.jpg') }}" class="img-thumbnail rounded mb-3" style="width: 100%;">
                                    <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" onchange="loadFile(event)">
                                    @error('image')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <!-- Form Section -->
                                <div class="col-md-8">
                                    <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="name" class="form-label fw-bold">Product Name</label>
                                                <input type="text" name="name" value="{{ old('name') }}"
                                                    class="form-control @error('name') is-invalid @enderror" id="name">
                                                @error('name')
                                                    <small class="invalid-feedback">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="category_name"  class="form-label fw-bold">Category</label>
                                                <select name="category_name"
                                                    class="form-control @error('category_name') is-invalid @enderror"
                                                    id="category_name">
                                                    <option value="">Choose Category Name...</option>
                                                    @foreach ($categories as $item)
                                                        <option value="{{ $item->id }}"
                                                            @if (old('category_name') == $item->id) selected @endif>
                                                            {{ $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('category_name')
                                                    <small class="invalid-feedback">{{ $message }}</small>
                                                @enderror
                                            </div>


                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Stock</label>
                                            <input type="number" name="stock" class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock') }}">
                                            @error('stock') <small class="text-danger">{{ $message }}</small> @enderror
                                        </div>



                                        <div class="col-12 mb-3">
                                            <label class="form-label fw-bold">Description</label>
                                            <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                                            @error('description') <small class="text-danger">{{ $message }}</small> @enderror
                                        </div>

                                    </div>


                                    <div class="row">
                                        <div class="col-6">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-save"></i> Add Product
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <a href="{{ route('product.prodlist') }}" class="btn btn-secondary w-100">
                                                <i class="bi bi-arrow-left"></i> Back
                                            </a>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </form>

                </div>
            </div>
        </div>
    </section>
@endsection
@section('scripts')
<script>
    function loadFile(event) {
        document.getElementById('output').src = URL.createObjectURL(event.target.files[0]);
    }
</script>
@endsection
