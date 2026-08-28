@extends('admin.layouts.master')
@section('content')
<div class="container mt-3">
        <h2 class="fw-bold text-center">Asset Lists</h2>
         <a href="{{ route('assets.create') }}" class="btn btn-primary mb-3">Add Asset</a>
         @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                    <th>Serial Number</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>User</th>
                    <th>Purchase Date</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Unit</th>
                    <th>Warranty Expiry</th>
                    <th>Actions</th>
            </tr>
        </thead>
        <tbody>
             @foreach($assets as $asset)
                    <tr>
                        <td>{{ $asset->serial_number }}</td>
                        <td>{{ $asset->name }}</td>
                        <td>{{ $asset->category->name ?? '-' }}</td>
                        <td>{{ $asset->assignedUser->name ?? '-' }}</td>
                        <td>{{ $asset->purchase_date }}</td>
                        <td>{{ number_format($asset->purchase_value, 0) }}</td>
                        <td>{{ $asset->status }}</td>
                        <td>{{ $asset->unit }}</td>
                        <td>{{ $asset->warranty_expiry_date }}</td>
                        <td>
                            <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-outline-secondary rounded-pill btn-sm me-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                </svg>
                            </a>
                            <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger rounded-pill btn-sm" onclick="return confirm('Are you sure to delete this record?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                            <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                        </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
        </tbody>
    </table>
</div>
@endsection
