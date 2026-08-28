<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    //Route to category list
    public function list()
    {
        $categories = Category::paginate(5);
        return view('admin.category.list', compact('categories'));
    }

    //Route to create category page
    public function create()
    {
        return view('admin.category.create');
    }

    //Route to store category data
    public function store(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'category' => ['required', 'unique:categories,name'],
        ]);

        Category::create([
            'name' => $validated['category'],
        ]);
        return redirect()->route('category.list')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Added Category',
            ]);
    }

    //Route to edit category page
    public function edit($id)
    {
        // dd($id);

        $data = Category::where('id', $id)->first();

        // dd($data);

        return view('admin.category.edit', compact('data'));

    }

    //Route to update category data by id
    public function update(Request $request)
    {
        // dd($request->all());
        $validator = $request->validate([
            'category' => ['required', 'unique:categories,name,' . $request->categoryID],
        ]);

        Category::where('id', $request->categoryID)->update([
            'name' => $validator['category'],
        ]);

        return redirect()->route('category.list')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Updated Category',
            ]);
    }

    //Route to delete category data by id by checking in products table
    public function delete($id)
    {
        $productsExist = Product::where('category_id', $id)->exists();

        if ($productsExist) {
            return back()->with('error', 'This category still has products. Remove them first to delete the category');
        }

        Category::where('id', $id)->delete();

        return back()->with('success', 'Category deleted successfully');
    }

}
