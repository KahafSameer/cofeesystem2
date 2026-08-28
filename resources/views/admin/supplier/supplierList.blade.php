@extends('admin.layouts.master')

@section('content')
<div class="container mt-3">
    <div class="card shadow-lg p-4 supplier ">
        <h2 class="fw-bold text-center">Supplier List</h2>

        <!-- Add Supplier Button -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="{{ route('createSupplierPage') }}" class="btn btn-primary ">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle me-2" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                </svg> Add Supplier
            </a>

            <!-- Search & Filter Section -->
                <form action="" method="get" class="d-flex">
                    <input type="text" name="searchKey" class="form-control me-1" placeholder="Search Supplier..." value="{{ request('searchKey') }}">
                    <button class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                        </svg>
                    </button>
                </form>
        </div>

        <!-- Card Layout for Suppliers -->
        <div class="row" >
            @forelse ($suppliers as $item)
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100" >
                        <div class="card-body" style="background-color:#ded6aa">
                            <h5 class="fw-bold text-primary">{{ $item->name }}</h5>
                            <p class="mb-1"><i class="bi bi-telephone me-1"></i> {{ $item->contact }}</p>
                            <p class="mb-1"><i class="bi bi-geo-alt me-1"></i> {{ $item->address }}</p>
                            <span class="badge bg-{{ $item->status == 'Active' ? 'success' : 'danger' }}">
                                {{ $item->status }}
                            </span>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-between">
                            <a href="{{ route('editSupplier', $item->id ) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <form action="{{ route('deleteSupplier', $item->id ) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center">No suppliers found.</p>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>
@endsection
