<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Ingredient;
use App\Models\Purchase_Item;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PurchaseController extends Controller
{
    //Route to Supplier List Page
    public function index(){
        $suppliers = Supplier::when(request('searchKey'), function ($query) {
                        $query->where(function ($q) {
                            $q->where('suppliers.name', 'like', '%' . request('searchKey') . '%');
                        });
                    })->paginate(6);

                    // dd($suppliers);

        return view('admin.supplier.supplierList', compact('suppliers'));
    }


    //Route to add Supplier
    public function createSupplierPage(){
        return view('admin.supplier.createSupplier');
    }

    //Route to create
    public function createSupplier(Request $request){
        $validated = $request->validate([
            'name' => 'required',
            'contact' =>  'required',
            'address' => 'required',
            'status' => 'required',
        ]);

        Supplier::create($validated);
        return redirect()->route('supplier.index')->with('success', 'Supplier Added Successfully!');
    }

    //Route to edit page by id
    public function editSupplier($id){
        // dd($id);

        $totalDueAmount = Purchase::where('supplier_id',$id)->sum('due_amount');

        $supplierinfo = Supplier::findOrFail($id);

        return view ('admin.supplier.editSupplier', compact('supplierinfo','totalDueAmount'));
    }

    //update Supplier Info
    public function updateSupplier(Request $request, $id){

        // dd($request->all());
        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'name'        => 'required',
            'contact'     => 'required',
            'address'     => 'required',
            'status'      => 'required',
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->name    = $validated['name'];
        $supplier->contact = $validated['contact'];
        $supplier->address = $validated['address'];
        $supplier->status  = $validated['status'];
        $supplier->save();

        $remainingPaid = $validated['paid_amount'];

        $purchases = Purchase::where('supplier_id',$id)
                             ->where('due_amount','>',0)
                             ->orderBy('created_at')
                             ->get();

        foreach($purchases as $purchase){
            if($remainingPaid <= 0) break;

            if($purchase->due_amount <= $remainingPaid){
                $remainingPaid -= $purchase->due_amount;
                $purchase -> paid_amount += $purchase->due_amount;
                $purchase ->due_amount = 0;
            }else {
                $purchase -> paid_amount += $remainingPaid;
                $purchase ->due_amount -= $remainingPaid;
                $remainingPaid = 0;
            }

            if($purchase -> due_amount == 0){
                $purchase->payment_status = 'Paid';
            }elseif ($purchase->paid_amount == 0){
                $purchase->payment_status = 'Due';
            }else {
                $purchase->payment_status = 'Partial';
            }

            $purchase -> save();
        }

        // $supplier->update($request->all());

        return redirect()->route('supplier.index')->with('success', 'Supplier Updated Successfully!');
    }

    public function deleteSupplier($id){
        // dd($id);

            $supplier = Supplier::where('id', $id)
                                ->where('status', 'inactive')
                                ->firstOrFail();

            $supplier->delete();

        return redirect()->route('supplier.index')->with('success', 'Supplier Deleted Successfully');

    }

    //Purchase
    public function purchasePage(){
        $suppliers = Supplier::where('status','=','active')->get();

        // dd($suppliers->toArray());

        // $products = Product::get();
        return view('admin.supplier.purchase', compact('suppliers'));
    }

    public function addItem(Request $request)
    {

        $validatedItem = $request->validate([
            'cost_price'        => 'required|numeric|min:0',
            'quantity'          => 'required|numeric|min:1',
            'ingredient_name'   => 'required|string|max:255',
            'unit'              => 'required|string|max:50',
        ]);
        // Calculate total price
        $total_price = $validatedItem['cost_price'] * $validatedItem['quantity'];

        // Store in session
        $purchaseItems = session()->get('purchase_items', []);

       $purchaseItems[] = [
            'name' => $validatedItem['ingredient_name'],
            'unit' => $validatedItem['unit'],
            'quantity' => $validatedItem['quantity'],
            'cost_price' => $validatedItem['cost_price'],
            'total_price' => $total_price,
        ];

        // Update total amount
        $total_amount = array_sum(array_column($purchaseItems, 'total_price'));

        session([
            'purchase_items' => $purchaseItems,
            'total_amount' => $total_amount
        ]);

        return redirect()->back();
    }


    public function storePurchase(Request $request)
    {
        // Retrieve purchase items
        $purchaseItems = session()->get('purchase_items', []);
        if (empty($purchaseItems)) {
            return redirect()->back()->with('error', 'No items added!');
        }

        // Insert Ingredients & Get IDs
        $ingredientIds = [];

        foreach ($purchaseItems as $item) {
            $ingredient = Ingredient::create([
                'name' => $item['name'],
                'unit' => $item['unit'],
                'cost_price' => $item['cost_price']
            ]);
            $ingredientIds[] = $ingredient->id;
        }

        // Insert Purchase
        $purchase = Purchase::create([
            'supplier_id' => $request->supplier_id,
            'total_amount' => session('total_amount'),
            'paid_amount' => $request->paid_amount,
            'due_amount' => session('total_amount') - $request->paid_amount,
            'payment_status' => $request->payment_status,
        ]);

        // Insert Purchase Items
        foreach ($purchaseItems as $index => $item) {
            Purchase_Item::create([
                'purchase_id' => $purchase->id,
                'ingredient_id' => $ingredientIds[$index],
                'quantity' => $item['quantity'],
                'cost_price' => $item['cost_price'],
                'total_price' => $item['total_price'],
            ]);
        }

        // Clear session
        session()->forget(['purchase_items', 'total_amount']);

        return redirect()->route('purchasePage')->with('success', 'Purchase added successfully!');
    }


    public function removeItem($index){
        $purchaseItems = session()->get('purchase_items', []);
        if (isset($purchaseItems[$index])) {
            unset($purchaseItems[$index]);
            session(['purchase_items' => array_values($purchaseItems)]);
        }
        return redirect()->back();
    }

}
