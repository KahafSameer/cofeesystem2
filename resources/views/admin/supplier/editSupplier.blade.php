@extends('admin.layouts.master')

@section('content')
<div class="container-fluid mt-2">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-6">
            <form action="{{ route('updateSupplier', $supplierinfo->id) }}" method="POST" class="p-4 rounded shadow-lg" style="background-color: #4a2e18; color: #fdf4e3;">
                @csrf

                <h3 class="text-center fw-bold mb-3 text-white">Update Supplier Info</h3>

                <div class="mb-3">
                    <label class="form-label fw-bold">Supplier Name</label>
                    <input type="text" name="name" value="{{ old('name', $supplierinfo->name) }}"
                        class="form-control rounded-3 @error('name') is-invalid @enderror"
                        style="background-color: #fdf4e3; color: #2e1c0c;">
                    @error('name')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="row">
                        <div class="col">
                            <label class="form-label fw-bold">Due Amount</label>
                            <input type="number" step="100" name="due_amount"
                                value="{{ old('due_amount', $totalDueAmount) }}"
                                class="form-control rounded-3 @error('due_amount') is-invalid @enderror"
                                style="background-color: #fdf4e3; color: #2e1c0c;">
                            @error('due_amount')
                                <small class="invalid-feedback">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col">
                            <label class="form-label fw-bold">Paid Amount</label>
                            <input type="number" name="paid_amount" step="100" class="form-control" required>
                            @error('paid_amount')
                                <small class="invalid-feedback">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Contact</label>
                    <input type="text" name="contact" value="{{ old('contact', $supplierinfo->contact) }}"
                        class="form-control rounded-3 @error('contact') is-invalid @enderror"
                        style="background-color: #fdf4e3; color: #2e1c0c;">
                    @error('contact')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Address</label>
                    <textarea name="address" rows="2"
                            class="form-control rounded-3 @error('address') is-invalid @enderror"
                            style="background-color: #fdf4e3; color: #2e1c0c;">{{ old('address', $supplierinfo->address) }}</textarea>
                    @error('address')
                        <small class="invalid-feedback">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold">Status</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="status" value="Active"
                            {{ old('status', $supplierinfo->status) == 'Active' ? 'checked' : '' }} required>
                        <label class="form-check-label text-white">Active</label>
                    </div>

                    <div class="form-check form-check-inline">
                        @if($totalDueAmount > 0)
                            {{-- Fake Inactive radio when due exists --}}
                            <input type="radio" class="form-check-input" disabled>
                            <label class="form-check-label text-white" onclick="alertDue()">Inactive</label>
                        @else
                            {{-- Real Inactive radio --}}
                            <input class="form-check-input" type="radio" name="status" value="Inactive"
                                {{ old('status', $supplierinfo->status) == 'Inactive' ? 'checked' : '' }} required>
                            <label class="form-check-label text-white">Inactive</label>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <button type="submit" class="btn btn-outline-warning w-100 text-white">Update Info</button>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('supplier.index') }}" class="btn btn-outline-primary w-100 text-white">Back</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
    <script>
        function alertDue() {
                alert("Supplier still has a due amount. You can't set this supplier to Inactive.");
            }
    </script>

@endsection
