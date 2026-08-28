<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    //Route to branch list
    public function index()
    {
        $branches = Branch::paginate(10);
        return view('admin.branch.list', compact('branches'));
    }

    //Route to create branch page
    public function create()
    {
        return view('admin.branch.create');
    }

    //Route to store branch data
    public function store(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255', 'unique:branches,name'],
            'address' => ['nullable', 'string', 'max:255'],
            'status'  => ['required', 'in:Active,Inactive'],
        ]);

        Branch::create([
            'name'    => $validated['name'],
            'address' => $validated['address'],
            'status'  => $validated['status'],
        ]);

        return redirect()->route('branch.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Branch created successfully.',
            ]);
    }

    //Route to edit branch page
    public function edit($id)
    {
        $branch = Branch::findOrFail($id);

        return view('admin.branch.edit', compact('branch'));
    }

    //Route to update branch data by id
    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255', 'unique:branches,name,' . $branch->id],
            'address' => ['nullable', 'string', 'max:255'],
            'status'  => ['required', 'in:Active,Inactive'],
        ]);

        $branch->update([
            'name'    => $validated['name'],
            'address' => $validated['address'],
            'status'  => $validated['status'],
        ]);

        return redirect()->route('branch.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Branch updated successfully.',
            ]);
    }

    //Route to toggle branch active/inactive status
    public function toggleStatus($id)
    {
        $branch = Branch::findOrFail($id);

        $branch->update([
            'status' => $branch->status === 'Active' ? 'Inactive' : 'Active',
        ]);

        return redirect()->route('branch.index')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Branch status updated successfully.',
            ]);
    }

}
