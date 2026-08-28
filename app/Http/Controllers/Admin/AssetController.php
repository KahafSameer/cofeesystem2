<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Http\Request;

class AssetController extends Controller
{
//Asset Category
    public function index()
    {
        $categories = AssetCategory::all();
        return view('admin.asset.category', compact('categories'));
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        AssetCategory::create([
            'name' => $validated['name'],
        ]);

        return redirect()->route('assetCategories.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Added Asset Category',
            ]);
    }

    public function edit($id)
    {
        $category   = AssetCategory::findOrFail($id);
        $categories = AssetCategory::all();
        return view('admin.asset.category', compact('categories', 'category'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = AssetCategory::findOrFail($id);
        $category->update([
            'name' => $validated['name'],
        ]);

        return redirect()->route('assetCategories.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Asset Category updated successfully',
            ]);
    }

    public function destroy($id)
    {
        // dd($id);
        $category = AssetCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('assetCategories.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Asset Category deleted successfully',
            ]);
    }

//Asset Lists
    public function asset_index()
    {
        $assets = Asset::with(['category', 'assignedUser'])->get();

        return view('admin.asset.list', compact('assets'));
    }

    public function asset_create()
    {
        $categories = AssetCategory::all();
        $users      = User::all();

        // To generate Serial Number
        $datePrefix  = now()->format('dmY');
        $latestAsset = Asset::where('serial_number', 'LIKE', $datePrefix . '-%')
            ->orderBy('serial_number', 'desc')
            ->first();

        if ($latestAsset) {
            $lastCounter = (int) substr($latestAsset->serial_number, -6);
            $nextCounter = $lastCounter + 1;
        } else {
            $nextCounter = 1;
        }

        $counterFormatted = str_pad($nextCounter, 6, '0', STR_PAD_LEFT);
        $serialNumber     = $datePrefix . '-' . $counterFormatted;

        return view('admin.asset.create', [
            'categories'   => $categories,
            'users'        => $users,
            'serialNumber' => $serialNumber,
        ]);
    }

    public function asset_store(Request $request)
    {

        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'asset_category_id'    => 'required|exists:asset_categories,id',
            'assigned_user_id'     => 'nullable|exists:users,id',
            'purchase_date'        => 'required|date',
            'purchase_value'       => 'required|numeric',
            'depreciation_rate'    => 'nullable|numeric',
            'status'               => 'required|string|max:50',
            'unit'                 => 'required|string|max:50',
            'warranty_expiry_date' => 'nullable|date',
            'serial_number'        => 'unique:assets,serial_number',
            'notes'                => 'nullable|string',
        ]);

        Asset::create([
             ...$validated,
        ]);

        return redirect()->route('assets.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Asset added successfully.',
            ]);
    }

    public function asset_edit($id)
    {
        $asset      = Asset::findOrFail($id);
        $categories = AssetCategory::all();
        $users      = User::all();

        return view('admin.asset.edit', compact('asset', 'users', 'categories'));
    }

    public function asset_update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'asset_category_id'    => 'required|exists:asset_categories,id',
            'assigned_user_id'     => 'nullable|exists:users,id',
            'purchase_date'        => 'required|date',
            'purchase_value'       => 'required|numeric',
            'depreciation_rate'    => 'nullable|numeric',
            'status'               => 'required|string|max:50',
            'unit'                 => 'required|string|max:50',
            'warranty_expiry_date' => 'nullable|date',
            'serial_number'        => 'nullable|string|max:255',
            'notes'                => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($id);
        $asset->update($request->all());

        return redirect()->route('assets.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Asset updated successfully.',
            ]);

    }

    public function asset_destroy($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();

        return redirect()->route('assets.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Asset deleted successfully.',
            ]);
    }
}
