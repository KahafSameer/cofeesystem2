@extends('admin.layouts.master')

@section('content')
<section class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg">
                <div class="card-body">
                    <h2 class="text-lg fw-bold text-center mb-4">Update Category</h2>
                    <form action="{{ route('category.update', $data->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Category Name</label>
                            <input type="hidden" name="categoryID" value="{{ $data->id }}">
                            <input type="text" name="category" value="{{ old('category', $data->name) }}"
                                   class="form-control @error('category') is-invalid @enderror"
                                   id="exampleFormControlInput1" placeholder="Drinks...">
                            @error('category')
                                <small class="invalid-feedback">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <input type="submit" value="Update" class="btn btn-primary rounded-pill w-100">
                            </div>
                            <div class="col-6">
                                <a href="{{ route('category.list') }}" class="btn btn-secondary rounded-pill w-100 text-center">Back</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>


@endsection

