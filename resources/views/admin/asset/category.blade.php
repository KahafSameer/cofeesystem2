@extends('admin.layouts.master')
@section('content')
<div class="container mt-4">
    <h2 class="fw-bold text-end mb-3">Manage Asset Categories</h2>

    @if (session('success'))
        <div class="alert alert-success mt-2">{{ session('success') }}</div>
    @endif

    <div class="row mt-4">
        <!-- Create / Edit Form -->
        <div class="col-md-4">
            <div class="card shadow-lg">
                <div class="card-body">
                    <h4>{{ isset($category) ? 'Edit Category' : 'Add New Category' }}</h4>
                    <form action="{{ isset($category) ? route('assetCategories.update', $category->id) : route('assetCategories.store') }}" method="POST">
                        @csrf
                        @if (isset($category))
                            @method('PUT')
                        @endif
                        <div class="mb-3">
                               <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" placeholder="Category Name" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            {{ isset($category) ? 'Update' : 'Add' }}
                        </button>
                        @if (isset($category))
                            <a href="{{ route('assetCategories.index') }}" class="btn btn-secondary">Cancel</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- List Categories -->
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header">
                    <h4>Asset Categories</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>
                                        <a href="{{ route('assetCategories.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="{{ route('assetCategories.destroy', $item->id) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this asset?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
