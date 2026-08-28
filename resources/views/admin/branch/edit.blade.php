@extends('admin.layouts.master')

@section('content')
    <section class="container-fluid">
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h2 class="text-lg fw-bold text-center mb-4">Update Branch</h2>
                        <form action="{{ route('branch.update', $branch->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label">Branch Name</label>
                                <input type="text" name="name" value="{{ old('name', $branch->name) }}"
                                    class="form-control @error('name') is-invalid @enderror" id="name">
                                @error('name')
                                    <small class="invalid-feedback">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" name="address" value="{{ old('address', $branch->address) }}"
                                    class="form-control @error('address') is-invalid @enderror" id="address">
                                @error('address')
                                    <small class="invalid-feedback">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="Active" {{ old('status', $branch->status) === 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ old('status', $branch->status) === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <small class="invalid-feedback">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <input type="submit" value="Update Branch" class="btn btn-primary rounded-pill w-100">
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('branch.index') }}" class="btn btn-secondary rounded-pill w-100 text-center">Back</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
