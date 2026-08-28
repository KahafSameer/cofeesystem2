<div class="card shadow-sm p-4 rounded-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="serial_number" class="form-label">Serial Number</label>
            <input type="text" name="serial_number" id="serial_number" class="form-control" value="{{ $serialNumber ?? ($asset->serial_number ?? '') }}" readonly>
        </div>

        <div class="col-md-6">
            <label for="name" class="form-label fw-semibold">Asset Name</label>
            <input type="text" name="name" value="{{ old('name', $asset->name ?? '') }}" class="form-control" required>
        </div>

    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="assigned_user_id" class="form-label fw-semibold">Assigned User</label>
            <select name="assigned_user_id" class="form-select">
                <option value="">Not Assigned</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('assigned_user_id', $asset->assigned_user_id ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="asset_category_id" class="form-label fw-semibold">Category</label>
            <select name="asset_category_id" class="form-select" required>
                <option value="">Select Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('asset_category_id', $asset->asset_category_id ?? '') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>


    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="purchase_value" class="form-label fw-semibold">Purchase Value</label>
            <input type="number" step="0.01" name="purchase_value" value="{{ old('purchase_value', $asset->purchase_value ?? '') }}" class="form-control">
        </div>

        <div class="col-md-6">
            <label for="depreciation_rate" class="form-label fw-semibold">Depreciation Rate (%)</label>
            <input type="number" step="0.01" name="depreciation_rate" value="{{ old('depreciation_rate', $asset->depreciation_rate ?? '') }}" class="form-control">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="status" class="form-label fw-semibold">Status</label>
            <select name="status" class="form-select" required>
                <option value="active" {{ old('status', $asset->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $asset->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="retired" {{ old('status', $asset->status ?? '') == 'retired' ? 'selected' : '' }}>Retired</option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="unit" class="form-label fw-semibold">Unit</label>
            <input type="text" name="unit" value="{{ old('unit', $asset->unit ?? '') }}" class="form-control">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="purchase_date" class="form-label fw-semibold">Purchase Date</label>
            <input type="date" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date ?? '') }}" class="form-control">
        </div>

        <div class="col-md-6">
            <label for="warranty_expiry_date" class="form-label fw-semibold">Warranty Expiry Date</label>
            <input type="date" name="warranty_expiry_date" value="{{ old('warranty_expiry_date', $asset->warranty_expiry_date ?? '') }}" class="form-control">
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <label for="notes" class="form-label fw-semibold">Notes</label>
            <textarea name="notes" class="form-control" rows="1">{{ old('notes', $asset->notes ?? '') }}</textarea>
        </div>
    </div>

</div>
