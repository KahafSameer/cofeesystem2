@extends('admin.layouts.master')

@section('content')
    <section class="container-fluid">
        <div class="row justify-content-center align-items-center mt-3">
            <div class="col-lg-12">
                <div class="card shadow-lg mb-4">
                    <div class="card-header py-3 justify-content-between">
                        <h3 class="fw-bold text-center mb-3">Manage Branches</h3>
                        <a href="{{ route('branch.create') }}" class="btn btn-primary">Add New Branch</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            @if (session('alert'))
                                <div class="alert alert-{{ session('alert')['type'] == 'success' ? 'success' : 'danger' }}">
                                    {{ session('alert')['message'] }}
                                </div>
                            @endif
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center">No.</th>
                                        <th class="text-center">Branch Name</th>
                                        <th class="text-center">Address</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($branches as $branch)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="text-center">{{ $branch->name }}</td>
                                            <td class="text-center">{{ $branch->address ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $branch->status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $branch->status }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="justify-content-center d-flex">
                                                    <div class="col-auto">
                                                        <a href="{{ route('branch.edit', $branch->id) }}"
                                                            class="btn btn-outline-secondary rounded-pill btn-sm me-1">Edit</a>
                                                    </div>
                                                    <div class="col-auto">
                                                        <form action="{{ route('branch.status', $branch->id) }}" method="get"
                                                            onsubmit="return confirm('Are you sure you want to {{ $branch->status === 'Active' ? 'deactivate' : 'activate' }} this branch?');">
                                                            <button type="submit"
                                                                class="btn btn-outline-{{ $branch->status === 'Active' ? 'danger' : 'success' }} rounded-pill btn-sm me-1">
                                                                {{ $branch->status === 'Active' ? 'Deactivate' : 'Activate' }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No branches found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end">
                                {{ $branches->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
