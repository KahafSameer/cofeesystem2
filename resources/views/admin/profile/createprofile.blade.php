@extends('admin.layouts.master')

@section('content')
        <section class="container-fluid">
            <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
                <div class="card shadow col-5">
                    <div class="card-header py-3">
                        <div class="">
                            <div class="">
                                <h3 class="m-0 fw-bold text-center">Add User Account</h3>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profile.addNewUser') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="profile" class="form-label">User Profile</label>
                                <select id="profile" name="profile" class="form-select" onchange="toggleBranchField()">
                                    <option value="admin">Admin</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="chef">Chef</option>
                                    <option value="waiter">Waiter</option>
                                </select>
                            </div>
                            <div class="mb-3" id="branchField" style="display: none;">
                                <label for="branch_id" class="form-label">Branch</label>
                                <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">Choose branch...</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <small class="invalid-feedback">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" id="name">
                                @error('name')
                                    <small class="invalid-feedback">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" id="email">
                                @error('email')
                                    <small class="invalid-feedback">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password">
                                        @error('password')
                                            <small class="invalid-feedback">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <label for="confirmpassword" class="form-label">Confirm Password</label>
                                        <input type="password" name="confirmpassword" class="form-control @error('confirmpassword') is-invalid @enderror" id="confirmpassword">
                                        @error('confirmpassword')
                                            <small class="invalid-feedback">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <input type="submit" value="Create" class="btn btn-coffee w-100">
                        </form>
                    </div>
                </div>
            </div>

        </section>
@endsection

@section('scripts')
<script>
    function toggleBranchField() {
        const profile = document.getElementById('profile');
        const branchField = document.getElementById('branchField');
        const branchSelect = document.getElementById('branch_id');
        const branchRoles = ['cashier', 'chef', 'waiter'];

        if (branchRoles.includes(profile.value)) {
            branchField.style.display = 'block';
            branchSelect.setAttribute('required', 'required');
        } else {
            branchField.style.display = 'none';
            branchSelect.removeAttribute('required');
            branchSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleBranchField();
    });
</script>
@endsection
