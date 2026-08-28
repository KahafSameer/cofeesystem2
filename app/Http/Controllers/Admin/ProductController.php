<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    //
    public function prodlist()
    {
        $products = Product::select(
            'products.*',
            DB::raw('products.qty - COALESCE(SUM(orders.quantity), 0) as available_stock')
        )
            ->leftJoin('orders', function ($join) {
                $join->on('orders.product_id', '=', 'products.id')
                    ->where('orders.status', 2);
            })
            ->when(request('searchKey'), function ($query) {
                $query->where('products.name', 'like', '%' . request('searchKey') . '%');
            })
            ->groupBy(
                'products.name',
                'products.qty',
                'products.category_id',
                'products.description',
                'products.image',
                'products.created_at',
                'products.updated_at',
                'products.id'
            )
            ->orderBy('products.id')
            ->paginate(8);

        // For each product, manually load sizes
        foreach ($products as $product) {
            $product->sizes = DB::table('product_sizes')
                ->where('product_id', $product->id)
                ->get(['size', 'price']);
        }
        // dd($products->all());

        return view('admin.product.prodlist', compact('products'));
    }

    public function prodcreate()
    {
        $categories = Category::get();
        return view('admin.product.prodcreate', compact('categories'));
    }

    public function prodstore(Request $request)
    {
        // dd($request->all());

        $this->validationCheck($request, "create");

        $data = $this->requestProductData($request);

        if ($request->hasFile('image')) {
            $fileName = uniqid() . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path() . '/productImages/', $fileName);
            $data['image'] = $fileName;
        }

        try {
            $product = Product::create($data);
        } catch (\Exception $e) {
            dd('DB Error: ' . $e->getMessage());
        }

        return redirect()->route('prodsize', $product->id);

    }

    private function validationCheck($request, $action)
    {
        $rules = [
            'name'          => ['required', 'unique:products,name,' . $request->productId],
            'stock'         => ['required', 'integer', 'max:100'],
            'description'   => 'required',
            'category_name' => 'required|exists:categories,id',
        ];

        $rules['image'] = $action == 'create' ? ['required', 'mimes:png,jpg,jpeg,webp,svg,gif,bmp'] : ['mimes:png,jpg,jpeg,webp,svg,gif,bmp'];

        $validator = $request->validate($rules);
    }

    private function requestProductData($request)
    {
        return [
            'name'        => $request->name,
            'category_id' => $request->category_name,
            'description' => $request->description,
            'qty'         => $request->stock,
        ];
    }

    public function prodedit($id)
    {
        // dd($id);
        $products = Product::select('products.id',
            'products.name',
            'products.image',
            'products.qty',
            'products.description',
            'products.category_id',
            'product_sizes.size',
            'product_sizes.price',
            'categories.name as category_name')
            ->leftJoin('categories', 'products.category_id', 'categories.id')
            ->leftJoin('product_sizes', 'products.id', 'product_sizes.product_id')
            ->where('products.id', $id)
            ->get();

        $categories = Category::get();

        // dd($products->toArray());

        return view('admin.product.prodedit', compact('products', 'categories'));

    }

    //update Product
    public function produpdate(Request $request)
    {

        // dd($request->all());
        $this->validationCheck($request, "update");

        $data = $this->requestProductData($request);

        // Handle image upload
        if ($request->hasFile('image')) {
            if (file_exists(public_path('productImages/' . $request->oldImage))) {
                unlink(public_path('productImages/' . $request->oldImage));
            }

            $fileName = uniqid() . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('productImages'), $fileName);
            $image = $fileName;
        } else {
            $image = $request->oldImage;
        }

        // Prepare data for Product table
        $productData = [
            'name'        => $data['name'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'qty'         => $data['qty'],
            'image'       => $image,
        ];

        // Prepare data for ProductSize table
        $productSizeData = [
            'size'  => $request->size,
            'price' => $request->price,
        ];

        // dd($request->all());
        // Update each table
        Product::where('id', $request->productId)->update($productData);

        ProductSize::where('product_id', $request->productId)
            ->where('size', $request->oldSize)
            ->update($productSizeData);

        return redirect()->route('product.prodlist')->with('alert', [
            'type'    => 'success',
            'message' => 'Update Product Successfully',
        ]);
    }

    //delete Products
    public function proddelete($id)
    {
        // dd($id);
        Product::where('id', $id)->delete();
        return redirect()->route('product.prodlist');
    }
    //route to prodsize page
    public function prodsize($id)
    {
        // dd($id);
        $product    = Product::findOrFail($id);
        $categories = Category::all();

        // dd($product);
        return view('admin.product.productsize', compact('product', 'categories'));
    }

    //add product price and size
    public function prodsizestore(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'sizes'    => 'required|array',
            'sizes.*'  => 'required|string|in:Small,Medium,Large',
            'prices'   => 'required|array',
            'prices.*' => 'required|numeric|min:0',
        ]);

        $sizes  = $validated['sizes'];
        $prices = $validated['prices'];

        $existingSizes = ProductSize::where('product_id', $product->id)
            ->pluck('size') // get all existing sizes for this product
            ->toArray();

        $duplicates = [];
        $newSizes   = [];

        foreach ($sizes as $index => $size) {
            if (in_array($size, $existingSizes)) {
                $duplicates[] = $size;
            } else {
                $newSizes[] = [
                    'product_id' => $product->id,
                    'size'       => $size,
                    'price'      => $prices[$index],
                ];
            }
        }

        // Insert new sizes
        if (! empty($newSizes)) {
            ProductSize::insert($newSizes);
        }

        // Handle duplicates
        if (! empty($duplicates)) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'These sizes already exist for this product: ' . implode(', ', $duplicates),
            ]);
        }

        return redirect()->route('product.prodlist')->with('alert', [
            'type'    => 'success',
            'message' => 'Product sizes added successfully.',
        ]);
    }

    //Discount Products
    public function discountPage()
    {
        $product = Product::get();
        return view('admin.product.discount', compact('product'));
    }

                                                    //Add Discount table
    public function adddiscount(Request $request)
    { // dd($request->all());
                                                        // Basic validation for common fields
        $validated = $request->validate([
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'start_date'          => 'required|date',
            'end_date'            => 'required|date|after_or_equal:start_date',
        ]);

        // Extra validation if not applying to all products
        if (! $request->has('apply_to_all')) {
            $request->validate([
                'product_id' => 'required|exists:products,id',
            ]);
        }

        // Prepare data for updateOrCreate
        $discountData = [
            'discount_percentage' => $validated['discount_percentage'],
            'start_date'          => $validated['start_date'],
            'end_date'            => $validated['end_date'],
        ];

        if ($request->has('apply_to_all')) {
            // Apply to all products
            $products = Product::all();
        } else {
            // Apply to a specific product
            $products = Product::where('id', $request->input('product_id'))->get();
        }

        // Save discount for each applicable product
        foreach ($products as $product) {
            Discount::updateOrCreate(
                ['product_id' => $product->id],
                $discountData
            );
        }
        return redirect()->route('discountPage')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Discount applied successfully!',
            ]);

    }

}
