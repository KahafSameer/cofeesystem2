@extends('admin.layouts.master')

@section('content')
    <section class="container-fluid">
        <div class="row justify-content-center align-items-center mt-3">
            <div class="col-lg-12">
                <div class="card shadow-lg mb-4" >
                    <div class="card-header py-3 justify-content-between">
                        <h3 class="fw-bold text-center mb-3">Manage Categories</h3>
                        <a href="{{ route('category.create') }}" class="btn btn-primary">Add New Category</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            {{-- Add for the delete button --}}
                            @if (session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            {{-- End Session --}}
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center">No.</th>
                                        <th class="text-center">Name</th>
                                        <th class="text-center">Created Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td><!-- Auto-incremented number  -->
                                            <td class="text-center">{{ $category->name }}</td>
                                            <td class="text-center">{{ $category->created_at->format('j-F-Y') }}
                                            </td>
                                            <td class="text-center">
                                                <div class="justify-content-center d-flex">
                                                    <div class="col-auto">
                                                        <form action="{{ route('category.edit', $category->id) }}"
                                                            method="get">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-outline-secondary rounded-pill btn-sm me-1">Edit..</button>
                                                        </form>

                                                    </div>
                                                    <div class="col-auto">
                                                        <form action="{{ route('category.delete', $category->id) }}"
                                                            method="post"
                                                            onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-outline-danger rounded-pill btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end">
                                {{ $categories->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
