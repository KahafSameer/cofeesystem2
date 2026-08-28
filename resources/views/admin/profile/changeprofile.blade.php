@extends('admin.layouts.master')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid py-4 px-4 px-md-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
            <h3 class="text-dark fw-bold mb-4 text-center">Change User Profile</h3>
                <div class="d-flex justify-content-between">
                    <div class="row">
                        <div class="col-md-10">
                            <form action="{{ route('changeProfilePage') }}" method="get">
                                <div class="input-group mb-3">
                                    <input type="text" name="searchKey" class="form-control " placeholder="Search..."
                                        value="">
                                    <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr class="text-center text-white">
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Actions</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $item)
                                <tr class="text-center text-white">
                                    <td class="font-weight-bold">{{ $item->name }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td>{{ $item->phone }}</td>
                                    <td>
                                    <form action="{{ route('updateField', $item->id ) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="field" value="status">
                                        <select name="value" onchange="this.form.submit()" class="form-select">
                                            <option value="active"
                                                {{ strtolower($item->status) === 'active' ? 'selected' : '' }}>
                                                Active
                                            </option>
                                            <option value="inactive"
                                                {{ strtolower($item->status) === 'inactive' ? 'selected' : '' }}>
                                                Inactive
                                            </option>
                                        </select>
                                    </form>

                                    </td>
                                    <td>
                                        <form action="{{ route('updateField', $item->id ) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="field" value="role">
                                            <select name="value" id="role" onchange="this.form.submit()" class="form-select">
                                                @foreach($roles as $role)
                                                    <option value="{{ $role }}" {{ $item->role === $role ? 'selected' : '' }}>
                                                        {{ ucfirst($role) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                    <span class="d-flex justify-content-end"></span>
                </div>
            </div>
        </div>
    </div>
@endsection
