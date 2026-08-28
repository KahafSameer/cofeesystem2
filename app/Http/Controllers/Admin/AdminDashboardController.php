<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryFees;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\TaxSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    //Home
    public function index()
    {
        $user = Auth::user();
        $data = [];

        if ($user->role !== 'user') {
            $data['dailySales']   = $this->getDailySales();
            $data['monthlySales'] = $this->getMonthlyTotalSales();
            $data['stock']        = $this->getProductStock();
            $data['outofstock']   = $data['stock']->filter(fn($item) => $item->stock < 5);

            $data['topProducts']    = $this->getTopProducts();
            $data['salesOverview']  = $this->getSalesOverview();
            $data['orderType']      = $this->getOrderTypes();
            $data['purchaseCost']   = $this->getPurchaseCost();
            $data['paymentMethods'] = $this->getPaymentMethod();
            $data['orderPending']   = $this->getOrderPending();

        } else {
            abort(403, 'Unauthorized');
        }

        return view('admin.home', $data);
    }

    private function getDailySales()
    {
        return Order::whereDate('created_at', now())->sum('totalprice');
    }

    private function getMonthlyTotalSales()
    {
        return Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('totalprice');
    }

    private function getProductStock()
    {
        return DB::table('products')
            ->leftJoin('orders', 'products.id', '=', 'orders.product_id')
            ->select('products.name', DB::raw('products.qty - COALESCE(SUM(orders.quantity), 0) AS stock'))
            ->groupBy('products.id', 'products.name', 'products.qty')
            ->get();
    }

    private function getTopProducts()
    {
        return DB::table('orders')
            ->select('products.name', DB::raw('SUM(orders.quantity) as total_quantity_sold'))
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->where('orders.status', 2) // status 2 indicates completed orders(1 for pending, 2 for success, 3 for reject)
            ->groupBy('orders.product_id', 'products.name')
            ->orderByDesc('total_quantity_sold')
            ->take(3) // Get top 3 products
            ->get();
    }

    private function getSalesOverview()
    {
        return Order::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(totalprice) as total'))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    private function getPurchaseCost()
    {
        return Purchase::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_amount');
    }
    private function getPaymentMethod()
    {
        return Order::select('payment_method', DB::raw('count(*) as count'))
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('payment_method')
            ->get();
    }
    private function getOrderPending(){
        return Order::where('status', '1')->distinct('order_code')->count('order_code');
    }

    private function getOrderTypes()
    {
        $types = [
            1 => 'Take Away',
            2 => 'Eat In',
            3 => 'Delivery',
        ];

        return Order::select('order_type', DB::raw('COUNT(*) as count'))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->groupBy('order_type')
            ->get()
            ->map(function ($item) use ($types) {
                $item->type = $types[$item->type] ?? 'Unknown';
                return $item;
            });
    }

    //Tax
    public function taxPage()
    {
        $tax = TaxSetting::get();

        // dd($tax);
        return view('admin.profile.tax', compact('tax'));
    }

    public function addTaxRate(Request $request)
    {
        $validated = $request->validate([
            'tax_name' => 'required|string|max:100',
            'tax_rate' => 'required|numeric|min:0|max:100',
        ]);

        if ($request->action === 'add') {
            // Check if the tax already exists to avoid duplicates
            $existingTax = TaxSetting::where('tax_name', $validated['tax_name'])->first();
            if ($existingTax) {
                return back()->with('alert', [
                    'type'    => 'warning',
                    'message' => 'Tax name already exists. Try updating instead.',
                ]);
            }

            TaxSetting::create($validated);
            return redirect()->route('taxPage')->with('alert', [
                'type'    => 'success',
                'message' => 'Tax added successfully!',
            ]);
        }

        if ($request->action === 'update') {
            $taxSetting = TaxSetting::where('tax_name', $validated['tax_name'])->first();
            if ($taxSetting) {
                $taxSetting->update(['tax_rate' => $validated['tax_rate']]);
                return redirect()->route('taxPage')->with('alert', [
                    'type'    => 'success',
                    'message' => 'Tax updated successfully!',
                ]);
            }

            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'Tax name not found to update.',
            ]);
        }
    }

    public function deliveryInfoPage()
    {
        $locations = DeliveryFees::orderBy('township')->get();

        // dd($locations->toArray());
        return view('admin.Profile.adddelivery', compact('locations'));
    }

    public function addDeliFees(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'city'      => 'required|string|max:100',
            'township'  => 'required|string|max:100',
            'deli_fees' => 'required|numeric',
        ]);

        if ($request->action === 'add') {
            DeliveryFees::create([
                'city'      => $validated['city'],
                'township'  => $validated['township'],
                'fees'      => $validated['deli_fees'],
            ]);

            return redirect()->route('deliveryInfoPage')->with('alert', [
                'type'    => 'success',
                'message' => 'Deli Fees Added Successfully',
            ]);
        } else {
            $deliveryfees = DeliveryFees::where('township', $validated['township'])->first();

            // dd($deliveryfees);

            if ($deliveryfees) {
                $deliveryfees->update([
                    'fees' => $validated['deli_fees'],
                ]);
                return redirect()->route('deliveryInfoPage')->with('alert', [
                    'type'    => 'success',
                    'message' => 'Deli Fees Updated Successfully',
                ]);
            } else {
                return redirect()->route('deliveryInfoPage')->with('alert', [
                    'type'    => 'error',
                    'message' => 'Error!',
                ]);
            }

        }

    }

}
