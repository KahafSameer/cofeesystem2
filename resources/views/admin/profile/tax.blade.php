@extends('admin.layouts.master')

@section('content')

<section class="container-fluid py-5 modern-tax-section">
    <div class="row justify-content-center align-items-center">
        <!-- Add Tax Form -->
        <div class="col-md-6 mb-4">
            <div class="card p-4">
                <h3 class="text-dark fw-bold mb-4 text-center">Add Tax</h3>

                <form action="{{ route('addTaxRate') }}" method="POST">
                    @csrf
                    <div class="form-group mb-4">
                        <label for="tax_name" class="form-label">Type of Tax</label>
                        <input type="text" name="tax_name" id="tax_name"
                            class="form-control @error('tax_name') is-invalid @enderror" placeholder="Enter tax name...">
                        @error('tax_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-4">
                        <label for="tax_rate" class="form-label">Tax Percentage (%)</label>
                        <input type="number" name="tax_rate" id="tax_rate"
                            class="form-control @error('tax_rate') is-invalid @enderror" min="0"
                            max="100" placeholder="Enter tax percentage">
                        <small class="form-text text-muted">Enter the percentage value (e.g., 2 for 2%)</small>
                        @error('tax_rate')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <button type="submit" name="action" value="add"
                                class="btn btn-primary w-100 shadow-sm rounded-pill">
                                <i class="fas fa-plus-circle me-1"></i> Add Tax
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="submit" name="action" value="update"
                                class="btn btn-secondary w-100 shadow-sm rounded-pill">
                                <i class="fas fa-sync-alt me-1"></i> Update Tax
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tax Table -->
        <div class="col-md-6">

            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Tax Info</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered modern-table" width="100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Tax Rate (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tax as $item)
                                <tr>
                                    <td>{{ $item->tax_name }}</td>
                                    <td>{{ $item->tax_rate }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection
