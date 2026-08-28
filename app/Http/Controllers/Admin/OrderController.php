<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\TaxSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
//List
    public function orderlist()
    {

        $today         = Carbon::today();
        $summaryOrders = Order::select(
            'orders.order_code',
            'orders.product_id',
            'orders.status',
            'orders.created_at'
        )
        // ->whereDate('created_at', $today)
            ->where('orders.status', 1)
            ->get();

        $groupedOrders = $summaryOrders->groupBy('order_code');

        $currentPage          = request()->get('page', 1);
        $perPage              = 5;
        $groupedOrdersForPage = $groupedOrders->forPage($currentPage, $perPage);

        $paginatedGroupedOrders = new LengthAwarePaginator(
            $groupedOrdersForPage,
            $groupedOrders->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // dd($groupedOrders);

        return view('admin.order.orderlist', [
            'groupedOrders' => $paginatedGroupedOrders,
            'summaryOrders' => $summaryOrders,
        ]);

        // dd($groupedOrders->all());

    }

//View Order(order detail)
    public function viewOrder($orderCode)
    {
        // dd($orderCode);
        $details = Order::select('orders.user_id',
            'orders.product_id',
            'orders.status',
            'orders.size',
            'orders.notes',
            'orders.order_code',
            'orders.quantity',
            'orders.created_at',
            'orders.order_type',
            'products.image',
            'products.name',
            'product_sizes.price')
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('orders.size', '=', 'product_sizes.size');
            })
            ->where('orders.order_code', $orderCode)
            ->get();

        // dd($details->all());

        return view('admin.order.orderdetail', compact('details'));
    }

//Update status when user click Accept
    public function updateOrder(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'order_code' => 'required|string',
            'product_id' => 'required|integer',
            'action'     => 'required|string|in:accept,reject',
        ]);

        // Find the specific order by order_code and product_id
        $order = Order::where('order_code', $validated['order_code'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($order) {
            // Update the status based on the action
            if ($validated['action'] === 'accept') {
                $order->status = 2; // Accepted
            } elseif ($validated['action'] === 'reject') {
                $order->status = 3; // Rejected
            }

            $order->save();

            return redirect()->route('order.viewOrder', ['orderCode' => $validated['order_code']])
                ->with('success', 'Order updated successfully.');
        }

        // Redirect back with error if order is not found
        return redirect()->route('order.viewOrder', ['orderCode' => $validated['order_code']])
            ->with('error', 'Order not found.');
    }

//Booking
    public function bookingPage()
    {
        $categories = Category::all();

        $userId = Auth::user()->id;

        $cartOrder = DB::table('carts')->select('orderCode')->distinct()->pluck('orderCode');

        $orderCode = $cartOrder->first() ?? null;

        // dd($orderCode);
        return view('admin.order.booking', compact('categories', 'cartOrder'));
    }

//after generated new Code and store the code in session
    public function storeOrderCode(Request $request)
    {
        $request->session()->put('orderCode', $request->input('orderCode'));
        return response()->json(['status' => 'success']);
    }

    public function getProductsByCategory(Request $request)
    {
        // dd($request->all());
        $userId     = Auth::user()->id;
        $categories = Category::all();

        $selectedCategoryId = $request->query('categoryId');

        $orderCode = $request->query('orderCode', $request->session()->get('orderCode', 'N/A'));

        $productQuery = Product::query(); // Search products

        if ($selectedCategoryId) {
            $productQuery->where('category_id', $selectedCategoryId);
        }

        if ($request->filled('searchKey')) {
            $productQuery->where('name', 'like', '%' . $request->searchKey . '%');
        }

        $products = $productQuery->get();

        // Cart items count and calculation
        $cartItemsCount = Cart::where('user_id', $userId)
            ->where('orderCode', $orderCode)
            ->select('product_id', 'size', DB::raw('SUM(qty) as total_quantity'))
            ->groupBy('product_id', 'size')
            ->get();

        // dd($cartItemsCount);

        $today     = Carbon::today();
        $discounts = Discount::select('discount_percentage', 'product_id')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        $discountedProducts    = [];
        $allDiscountPercentage = null;

        foreach ($discounts as $discount) {
            if ($discount->product_id) {
                $discountedProducts[$discount->product_id] = $discount->discount_percentage;
            } else {
                $allDiscountPercentage = $discount->discount_percentage;
            }
        }

        $cartItems = Product::selectRaw('IF(discounts.product_id IS NOT NULL,
                                product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                product_sizes.price
                                ) as discountPrice,
                                discounts.discount_percentage,
                                products.id,
                                products.name,
                                products.image,
                                product_sizes.price,
                                product_sizes.size,
                                carts.qty as cart_qty,
                                carts.id as cartId,
                                carts.orderCode')
            ->leftJoin('carts', 'products.id', '=', 'carts.product_id')
            ->leftJoin('discounts', 'products.id', '=', 'discounts.product_id')
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->where('carts.user_id', $userId)
            ->where('carts.orderCode', $orderCode)
            ->get();

        // dd($cartItems);
        // Tax and total calculations
        $smallestUnit = 10;
        $subTotal     = $cartItems->sum(fn($item) => $item->discountPrice * $item->cart_qty);

        $taxSetting = TaxSetting::first();
        $taxRate    = $taxSetting->tax_rate ?? 0;
        $taxAmount  = ceil((($subTotal * $taxRate) / 100) / $smallestUnit) * $smallestUnit;

        // dd($orderType);

        // Delivery fee calculation
        $orderType   = $request->query('orderType', '');
        $deliveryFee = 0;

        if ($orderType === 'delivery') {
            $locationId  = $request->query('deliveryLocation');
            $location    = \App\Models\DeliveryFees::find($locationId);
            $deliveryFee = $location ? $location->fees : 0;
        }

        // dd($deliveryFee);

        // Final total
        $total = ceil(($subTotal + $taxAmount + $deliveryFee) / $smallestUnit) * $smallestUnit;

        // dd($total);

        // Load product sizes for display
        foreach ($products as $product) {
            $product->sizes = DB::table('product_sizes')
                ->where('product_id', $product->id)
                ->get(['size', 'price']);
        }

        // Return view with data
        return view('admin.order.booking', [
            'productbyCategory'  => $products,
            'categories'         => $categories,
            'discount'           => $discounts ?? 0,
            'selectedCategoryId' => $selectedCategoryId,
            'orderCode'          => $orderCode,
            'orderType'          => $orderType,
            'deliveryFee'        => $deliveryFee,
            'total'              => $total,
            'cartItemsCount'     => $cartItemsCount,
            'cartItems'          => $cartItems,
            'subTotal'           => $subTotal,
            'taxRate'            => $taxRate,
            'taxAmount'          => $taxAmount,
        ]);
    }

//Add the items to the Cart
    public function addItems(Request $request)
    {
        // dd($request->all());

        // Validate the incoming request data
        $validated = $request->validate([
            'orderCode'  => ['required', 'string', 'not_in:N/A'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
            'size'       => ['nullable', 'string'],
            'notes'      => ['nullable', 'string'],
        ]);

        $userId = Auth::id();

        $cartItem = Cart::firstOrNew([
            'user_id'    => $userId,
            'product_id' => $validated['product_id'],
            'orderCode'  => $validated['orderCode'],
            'size'       => $validated['size'],
        ]);

        // If item already exists, increment qty
        if ($cartItem->exists) {
            $cartItem->qty += $validated['quantity'];

            // Update notes only if existing is empty and new note is provided
            if (empty($cartItem->notes) && ! empty($validated['notes'])) {
                $cartItem->notes = $validated['notes'];
            }
        } else {
            // New cart item
            $cartItem->qty   = $validated['quantity'];
            $cartItem->notes = $validated['notes'];
        }

        $cartItem->save();
        // Log::info('Cart item created successfully.');

        return back()->with('success', 'Item added to cart successfully.');

    }

//To remove the cart when clicking Clear items
    public function clearCart(Request $request)
    {
        $orderCode = $request->input('orderCode');

        Cart::where('orderCode', $orderCode)->delete();

        return back()->with('success', 'Successfully cleared the cart items.');
    }

    public function getOrderCodes()
    {
        $cartOrder = DB::table('carts')->select('orderCode')->distinct()->pluck('orderCode');
        return response()->json($cartOrder);
    }

//Charge
    public function orderConfirm(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'orderCode'        => 'required|string',
            'paymentMethod'    => 'required|string',
            'cashReceived'     => 'required|numeric',
            'changeDue'        => 'required|numeric',
            'orderType'        => 'required|string|in:eat_in,take_away,delivery',
            'deliveryLocation' => 'required_if:orderType,delivery|exists:delivery_fees,id',
            'totalAmount'      => 'required|numeric',
        ]);

        $orderTypeMapping   = ['eat_in' => 1, 'take_away' => 2, 'delivery' => 3];
        $orderType          = $orderTypeMapping[$validated['orderType']];
        $deliveryLocationId = $validated['deliveryLocation'] ?? null;

        // dd($deliveryLocationId);

        $carts = Cart::selectRaw('
                            IF(discounts.product_id IS NOT NULL,
                                product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                product_sizes.price
                            ) * carts.qty as item_total,
                            IF(discounts.product_id IS NOT NULL,
                                product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                product_sizes.price
                            ) as discount_price,
                            carts.qty,
                            carts.user_id,
                            carts.orderCode,
                            carts.product_id,
                            carts.size,
                            carts.notes
                        ')
            ->leftJoin('products', 'carts.product_id', '=', 'products.id')
            ->leftJoin('discounts', function ($join) {
                $join->on('products.id', '=', 'discounts.product_id')
                    ->whereDate('discounts.start_date', '<=', now())
                    ->whereDate('discounts.end_date', '>=', now());
            })
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->where('carts.orderCode', $validated['orderCode'])
            ->get();

        if ($carts->isEmpty()) {
            return response()->json(['error' => 'No carts found for the provided order code.'], 404);
        }
        // dd($carts);
        foreach ($carts as $cart) {
            Order::create([
                'user_id'              => $cart->user_id,
                'product_id'           => $cart->product_id,
                'order_code'           => $cart->orderCode,
                'quantity'             => $cart->qty,
                'totalprice'           => $cart->discount_price,
                'status'               => 1,
                'payment_method'       => $validated['paymentMethod'],
                'order_type'           => $orderType,
                'size'                 => $cart->size,
                'notes'                => $cart->notes,
                'delivery_location_id' => $deliveryLocationId,
            ]);
        }

        Cart::where('orderCode', $validated['orderCode'])->delete();

        $order = Order::where('order_code', $validated['orderCode'])->firstOrFail();

        $smallestUnit = 10;
        $subTotal     = $carts->sum('item_total');
        $taxRate      = optional(TaxSetting::first())->tax_rate ?? 0;
        $taxAmount    = ceil((($subTotal * $taxRate) / 100) / $smallestUnit) * $smallestUnit;

        // Add delivery fee only after tax
        $deliveryFee = 0;
        if ($validated['orderType'] === 'delivery' && $deliveryLocationId) {
            $deliveryFee = optional(\App\Models\DeliveryFees::find($deliveryLocationId))->fees ?? 0;
        }

        $total = ceil(($subTotal + $taxAmount + $deliveryFee) / $smallestUnit) * $smallestUnit;

        // dd($order);
        PaymentRecord::create([
            'order_code'     => $order->order_code,
            'user_id'        => auth()->id(),
            'net_amount'     => $total,
            'paid_amount'    => $validated['cashReceived'],
            'change_amount'  => $validated['changeDue'],
            'payment_method' => ucfirst($validated['paymentMethod']),
            'status'         => 1,
        ]);

        // dd($paymentRecord);
        $order->update(['status' => 1]);

        $request->session()->forget('orderCode');

        return response()->json([
            'message'   => 'Order confirmed successfully.',
            'orderCode' => $validated['orderCode'],
        ]);
    }

// Once Order Confirm Generate Payment Slip
    public function generatePaymentSlip(Request $request)
    {
        $orderCode = $request->input('orderCode');

        $paymentData = $this->getPaymentRecordData($orderCode);

        if (! $paymentData) {
            return response()->json(['error' => 'Payment Record not found'], 404);
        }

        $html = view('admin.order.payment-slip', $paymentData)->render();

        return response($html);
    }

//Print Slip
    public function printPaymentSlip($orderCode)
    {
        $data = $this->getPaymentRecordData($orderCode);

        if (! $data) {
            return response('Payment Record not found', 404);
        }

        return view('admin.order.payment-slip', [
            'records'     => $data['records'],
            'subTotalAmt' => $data['subTotalAmt'],
            'deliveryFee' => $data['deliveryFee'],
            'taxAmount'   => $data['taxAmount'],
        ]);

        return view('admin.order.payment-slip', $data);
    }

//Payment Record
    public function paymentRecord()
    {
        $records = collect();

        return view('admin.order.payment-record', compact('records'));
    }

//Search Record
    public function searchRecord(Request $request)
    {
        $data = $this->getPaymentRecordData($request->searchKey);

        $html = view('admin.order.payment-record', [
            'records'      => $data['records'],
            'subTotalAmt'  => $data['subTotalAmt'],
            'delivery_fee' => $data['deliveryFee'],
            'taxAmount'    => $data['taxAmount'],
        ])->render();

        return response($html);

    }

//function for Print
    private function getPaymentRecordData($orderCode)
    {

        $order        = Order::where('order_code', $orderCode)->first();
        $smallestUnit = 10;

        $records = PaymentRecord::selectRaw('
                                        SUM(
                                            IF(discounts.product_id IS NOT NULL,
                                                product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                                product_sizes.price
                                            ) * orders.quantity
                                            ) as total_price,products.name as ProductName,
                                            payment_records.user_id as CashierID,
                                            payment_records.created_at as Date,
                                            payment_records.net_amount,
                                            payment_records.paid_amount,
                                            payment_records.change_amount,
                                            discounts.discount_percentage,
                                            payment_records.payment_method,
                                            orders.quantity,
                                            orders.totalprice,
                                            orders.order_code,
                                            product_sizes.price,
                                            product_sizes.size')
            ->leftJoin('orders', 'payment_records.order_code', '=', 'orders.order_code')
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->leftJoin('discounts', 'products.id', '=', 'discounts.product_id')
            ->leftJoin('product_sizes', function ($join) {
                $join->on('product_sizes.product_id', '=', 'products.id')
                    ->on('product_sizes.size', '=', 'orders.size');
            })
            ->where('payment_records.order_code', $orderCode)
            ->groupBy(
                'products.name',
                'payment_records.user_id',
                'payment_records.created_at',
                'payment_records.net_amount',
                'payment_records.paid_amount',
                'payment_records.change_amount',
                'discounts.discount_percentage',
                'payment_records.payment_method',
                'orders.quantity',
                'orders.totalprice',
                'orders.order_code',
                'product_sizes.price',
                'product_sizes.size'
            )
            ->get();

        $subTotalAmt = $records->sum('total_price');
        $taxSetting  = TaxSetting::first();
        $taxRate     = $taxSetting->tax_rate;
        $taxAmount   = ceil((($subTotalAmt * $taxRate) / 100) / $smallestUnit) * $smallestUnit;

        $deliveryFee = 0;
        if ($order->delivery_location_id) {
            $location    = \App\Models\DeliveryFees::find($order->delivery_location_id);
            $deliveryFee = $location ? $location->fees : 0;
        }

        return [
            'records'     => $records,
            'subTotalAmt' => $subTotalAmt,
            'deliveryFee' => $deliveryFee,
            'taxAmount'   => $taxAmount,
        ];
    }
}
