<?php
namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CustomerSession;
use App\Models\Discount;
use App\Models\Order;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\TaxSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WaiterController extends Controller
{
    private function waiter()
    {
        return Auth::user();
    }

    //Waiter Dashboard
    public function dashboard()
    {
        $waiter     = $this->waiter();
        $branchId   = $waiter->branch_id;
        $branch     = $waiter->branch;

        $todayOrders = Order::where('waiter_id', $waiter->id)
            ->where('branch_id', $branchId)
            ->whereDate('created_at', Carbon::today())
            ->select('order_code')
            ->distinct()
            ->count();

        $pendingOrders = Order::where('waiter_id', $waiter->id)
            ->where('branch_id', $branchId)
            ->where('status', 1)
            ->select('order_code')
            ->distinct()
            ->count();

        $completedOrders = Order::where('waiter_id', $waiter->id)
            ->where('branch_id', $branchId)
            ->where('status', 2)
            ->select('order_code')
            ->distinct()
            ->count();

        $totalOrders = Order::where('waiter_id', $waiter->id)
            ->where('branch_id', $branchId)
            ->select('order_code')
            ->distinct()
            ->count();

        return view('waiter.dashboard', [
            'waiter'         => $waiter,
            'branch'         => $branch,
            'todayOrders'    => $todayOrders,
            'pendingOrders'  => $pendingOrders,
            'completedOrders' => $completedOrders,
            'totalOrders'    => $totalOrders,
        ]);
    }

    //New Order - menu + cart
    public function newOrder(Request $request)
    {
        $waiter   = $this->waiter();
        $orderCode = $request->session()->get('waiterOrderCode');

        if (empty($orderCode)) {
            $orderCode = 'WTR-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
            $request->session()->put('waiterOrderCode', $orderCode);
        }

        $categories = Category::all();

        $selectedCategoryId = $request->query('categoryId');

        $productQuery = Product::query();

        if ($selectedCategoryId) {
            $productQuery->where('category_id', $selectedCategoryId);
        }

        if ($request->filled('searchKey')) {
            $productQuery->where('name', 'like', '%' . $request->searchKey . '%');
        }

        $products = $productQuery->get();

        $today     = Carbon::today();
        $discounts = Discount::whereDate('start_date', '<=', $today)
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

        foreach ($products as $product) {
            $product->sizes = ProductSize::where('product_id', $product->id)
                ->get(['size', 'price']);
            $discount = $discountedProducts[$product->id] ?? $allDiscountPercentage;
            $product->discount_percentage = $discount;
        }

        $cartItems = $this->getCartItems($waiter->id, $orderCode);

        $subTotal = $cartItems->sum(fn($item) => $item->discount_price * $item->cart_qty);

        $taxSetting = TaxSetting::first();
        $taxRate    = $taxSetting->tax_rate ?? 0;
        $taxAmount  = $this->computeRounded($subTotal * $taxRate / 100);
        $total      = $this->computeRounded($subTotal + $taxAmount);

        return view('waiter.new-order', [
            'products'         => $products,
            'categories'       => $categories,
            'selectedCategoryId' => $selectedCategoryId,
            'orderCode'        => $orderCode,
            'cartItems'        => $cartItems,
            'subTotal'         => $subTotal,
            'taxAmount'        => $taxAmount,
            'total'            => $total,
            'taxRate'          => $taxRate,
        ]);
    }

    private function computeRounded($amount)
    {
        $smallestUnit = 10;
        return ceil($amount / $smallestUnit) * $smallestUnit;
    }

    private function getCartItems($userId, $orderCode)
    {
        return Product::selectRaw('IF(discounts.product_id IS NOT NULL,
                                product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                product_sizes.price
                                ) as discount_price,
                                products.id,
                                products.name,
                                products.image,
                                product_sizes.price,
                                product_sizes.size,
                                carts.qty as cart_qty,
                                carts.id as cartId,
                                carts.notes,
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
    }

    //Add item to cart
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
            'size'       => ['nullable', 'string'],
            'notes'      => ['nullable', 'string'],
        ]);

        $orderCode = $request->input('orderCode', $request->session()->get('waiterOrderCode'));

        if (empty($orderCode) || $orderCode === 'N/A') {
            $orderCode = 'WTR-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
        }
        $request->session()->put('waiterOrderCode', $orderCode);

        $userId = $this->waiter()->id;

        $size = $validated['size'] ?? '';
        if (empty($size)) {
            $firstSize = ProductSize::where('product_id', $validated['product_id'])->first();
            $size = $firstSize?->size ?? 'Standard';
        }

        $cartItem = Cart::firstOrNew([
            'user_id'    => $userId,
            'product_id' => $validated['product_id'],
            'orderCode'  => $orderCode,
            'size'       => $size,
        ]);

        if ($cartItem->exists) {
            $cartItem->qty += $validated['quantity'];
            if (empty($cartItem->notes) && ! empty($validated['notes'])) {
                $cartItem->notes = $validated['notes'];
            }
        } else {
            $cartItem->qty   = $validated['quantity'];
            $cartItem->notes = $validated['notes'];
        }

        $cartItem->save();

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Item added to cart.',
        ]);
    }

    //Cart review page
    public function cart(Request $request)
    {
        $orderCode = $request->session()->get('waiterOrderCode');

        if (empty($orderCode)) {
            return redirect()->route('waiter.newOrder')->with('alert', [
                'type'    => 'info',
                'message' => 'Your cart is empty.',
            ]);
        }

        $cartItems = $this->getCartItems($this->waiter()->id, $orderCode);

        $subTotal = $cartItems->sum(fn($item) => $item->discount_price * $item->cart_qty);

        $taxSetting = TaxSetting::first();
        $taxRate    = $taxSetting->tax_rate ?? 0;
        $taxAmount  = $this->computeRounded($subTotal * $taxRate / 100);
        $total      = $this->computeRounded($subTotal + $taxAmount);

        return view('waiter.cart', [
            'orderCode' => $orderCode,
            'cartItems' => $cartItems,
            'subTotal'  => $subTotal,
            'taxAmount' => $taxAmount,
            'taxRate'   => $taxRate,
            'total'     => $total,
        ]);
    }

    public function updateCart(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => ['required', 'integer', 'exists:carts,id'],
            'qty'     => ['required', 'integer', 'min:1'],
            'notes'   => ['nullable', 'string'],
        ]);

        $cart = Cart::findOrFail($validated['cart_id']);

        // Ensure the waiter can only update their own cart items
        if ($cart->user_id !== $this->waiter()->id) {
            abort(403);
        }

        $cart->qty   = $validated['qty'];
        $cart->notes = $validated['notes'] ?? $cart->notes;
        $cart->save();

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Cart updated.',
        ]);
    }

    public function removeCart($cartId)
    {
        $cart = Cart::findOrFail($cartId);

        if ($cart->user_id !== $this->waiter()->id) {
            abort(403);
        }

        $cart->delete();

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Item removed from cart.',
        ]);
    }

    //Place waiter order
    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'orderCode'     => 'required|string',
            'paymentMethod' => 'required|string|in:cash,card,mobile',
            'orderType'     => 'required|string|in:eat_in,take_away,delivery',
            'totalAmount'   => 'required|numeric',
        ]);

        $waiter   = $this->waiter();
        $orderCode = $validated['orderCode'];

        $carts = Cart::join('products', 'carts.product_id', '=', 'products.id')
            ->leftJoin('discounts', function ($join) {
                $join->on('products.id', '=', 'discounts.product_id')
                    ->whereDate('discounts.start_date', '<=', now())
                    ->whereDate('discounts.end_date', '>=', now());
            })
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->selectRaw('IF(discounts.product_id IS NOT NULL,
                            product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                            product_sizes.price
                            ) as discount_price,
                            carts.qty,
                            carts.orderCode,
                            carts.product_id,
                            carts.size,
                            carts.notes')
            ->where('carts.orderCode', $orderCode)
            ->where('carts.user_id', $waiter->id)
            ->get();

        if ($carts->isEmpty()) {
            return redirect()->route('waiter.newOrder')->with('alert', [
                'type'    => 'error',
                'message' => 'Your cart is empty.',
            ]);
        }

        $orderTypeMapping = ['eat_in' => 1, 'take_away' => 2, 'delivery' => 3];
        $orderType        = $orderTypeMapping[$validated['orderType']];

        foreach ($carts as $cart) {
            Order::create([
                'user_id'          => $waiter->id,
                'waiter_id'        => $waiter->id,
                'branch_id'        => $waiter->branch_id,
                'product_id'       => $cart->product_id,
                'order_code'       => $cart->orderCode,
                'quantity'         => $cart->qty,
                'totalprice'       => $cart->discount_price,
                'status'           => 1,
                'payment_method'   => $validated['paymentMethod'],
                'order_type'       => $orderType,
                'size'             => $cart->size,
                'notes'            => $cart->notes,
            ]);
        }

        Cart::where('orderCode', $orderCode)->where('user_id', $waiter->id)->delete();

        // No PaymentRecord is created here: money is only recorded when the
        // cashier settles the bill (standalone order or running-bill session).
        // Placing an order only produces kitchen orders (status 1).

        $request->session()->forget('waiterOrderCode');

        //KOT auto-print: flagged once after successful submission so the next
        //page opens the kitchen ticket exactly once (cleared automatically).
        session()->flash('kotOrderCode', $orderCode);

        return redirect()->route('waiter.currentOrders')->with('alert', [
            'type'    => 'success',
            'message' => 'Order #' . $orderCode . ' placed successfully.',
        ]);
    }

    //Current (pending) orders for this waiter
    public function currentOrders()
    {
        $waiter   = $this->waiter();
        $orders   = Order::where('waiter_id', $waiter->id)
            ->where('branch_id', $waiter->branch_id)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = $orders->groupBy('order_code');

        return view('waiter.orders.index', ['groupedOrders' => $grouped]);
    }

    //Order history (non-pending)
    public function orderHistory()
    {
        $waiter = $this->waiter();

        $orders = Order::with(['product', 'branch'])
            ->where('waiter_id', $waiter->id)
            ->where('branch_id', $waiter->branch_id)
            ->whereIn('status', [2, 3])
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = $orders->groupBy('order_code');

        return view('waiter.orders.history', ['groupedOrders' => $grouped]);
    }

    //Order details with authorization
    public function orderDetails($orderCode)
    {
        $waiter   = $this->waiter();

        $order = Order::where('order_code', $orderCode)
            ->where('waiter_id', $waiter->id)
            ->where('branch_id', $waiter->branch_id)
            ->first();

        if (! $order) {
            abort(404);
        }

        $details = Order::with(['product', 'branch'])
            ->where('order_code', $orderCode)
            ->where('waiter_id', $waiter->id)
            ->where('branch_id', $waiter->branch_id)
            ->get();

        $paymentRecord = PaymentRecord::where('order_code', $orderCode)->first();

        return view('waiter.orders.show', [
            'order'         => $order,
            'details'       => $details,
            'paymentRecord' => $paymentRecord,
        ]);
    }

    //Waiter profile (branch read-only)
    public function profile()
    {
        $waiter = $this->waiter();
        $branch = $waiter->branch;

        return view('waiter.profile', compact('waiter', 'branch'));
    }

    //=== Edit pending order (editable until fully completed) ===

    private function getOrderItems($orderCode)
    {
        return Order::selectRaw('IF(discounts.product_id IS NOT NULL,
                                product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                product_sizes.price
                                ) as discount_price,
                                orders.id,
                                orders.product_id,
                                orders.size,
                                orders.quantity,
                                orders.notes,
                                orders.status,
                                products.name,
                                products.image')
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->leftJoin('discounts', 'products.id', '=', 'discounts.product_id')
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('orders.size', '=', 'product_sizes.size');
            })
            ->where('orders.order_code', $orderCode)
            ->get();
    }

    //Recompute order total (money is recorded only at cashier settlement)
    private function recomputeOrderTotal($orderCode)
    {
        $items    = $this->getOrderItems($orderCode);
        $subTotal = $items->sum(fn($i) => $i->discount_price * $i->quantity);

        $taxRate   = optional(TaxSetting::first())->tax_rate ?? 0;
        $taxAmount = $this->computeRounded($subTotal * $taxRate / 100);
        $total     = $this->computeRounded($subTotal + $taxAmount);

        return $total;
    }

    //Show the edit screen for a pending order
    public function editOrder($orderCode)
    {
        $waiter = $this->waiter();

        $order = Order::where('order_code', $orderCode)
            ->where('waiter_id', $waiter->id)
            ->where('branch_id', $waiter->branch_id)
            ->first();

        if (! $order) {
            abort(404);
        }

        if ($order->status != 1) {
            return redirect()->route('waiter.currentOrders')->with('alert', [
                'type'    => 'error',
                'message' => 'This order is no longer editable.',
            ]);
        }

        $orderItems = $this->getOrderItems($orderCode);

        $categories = Category::all();
        $products   = Product::all();
        $today      = Carbon::today();

        $discountedProducts    = [];
        $allDiscountPercentage = null;
        $discounts = Discount::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        foreach ($discounts as $d) {
            if ($d->product_id) {
                $discountedProducts[$d->product_id] = $d->discount_percentage;
            } else {
                $allDiscountPercentage = $d->discount_percentage;
            }
        }

        foreach ($products as $p) {
            $p->sizes               = ProductSize::where('product_id', $p->id)->get(['size', 'price']);
            $p->discount_percentage = $discountedProducts[$p->id] ?? $allDiscountPercentage;
        }

        $subTotal = $orderItems->sum(fn($i) => $i->discount_price * $i->quantity);
        $taxRate  = optional(TaxSetting::first())->tax_rate ?? 0;
        $taxAmount = $this->computeRounded($subTotal * $taxRate / 100);
        $total    = $this->computeRounded($subTotal + $taxAmount);

        $paymentRecord = PaymentRecord::where('order_code', $orderCode)->first();

        return view('waiter.orders.edit', [
            'order'         => $order,
            'orderItems'    => $orderItems,
            'categories'    => $categories,
            'products'      => $products,
            'subTotal'      => $subTotal,
            'taxAmount'     => $taxAmount,
            'taxRate'       => $taxRate,
            'total'         => $total,
            'paymentRecord' => $paymentRecord,
        ]);
    }

    //Verify the order belongs to the waiter, the branch, and is still pending
    private function verifiableOrder($orderCode)
    {
        $waiter = $this->waiter();

        return Order::where('order_code', $orderCode)
            ->where('waiter_id', $waiter->id)
            ->where('branch_id', $waiter->branch_id)
            ->first();
    }

    //Update an order line item (qty / notes)
    public function updateOrderItem(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes'    => ['nullable', 'string'],
        ]);

        $orderItem = Order::findOrFail($validated['order_id']);
        $order     = $this->verifiableOrder($orderItem->order_code);

        if (! $order || $order->status != 1) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'This order is no longer editable.',
            ]);
        }

        $orderItem->quantity = $validated['quantity'];
        $orderItem->notes    = $validated['notes'] ?? $orderItem->notes;
        $orderItem->save();

        $this->recomputeOrderTotal($orderItem->order_code);

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Order item updated.',
        ]);
    }

    //Remove an order line item
    public function removeOrderItem(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $orderItem = Order::findOrFail($validated['order_id']);
        $order     = $this->verifiableOrder($orderItem->order_code);

        if (! $order || $order->status != 1) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'This order is no longer editable.',
            ]);
        }

        $orderCode = $orderItem->order_code;
        $orderItem->delete();

        // If no items remain, mark the order rejected/cancelled
        if (Order::where('order_code', $orderCode)->where('waiter_id', $this->waiter()->id)->count() === 0) {
            Order::where('order_code', $orderCode)->update(['status' => 3]);

            return redirect()->route('waiter.currentOrders')->with('alert', [
                'type'    => 'success',
                'message' => 'Order cancelled (no items remaining).',
            ]);
        }

        $this->recomputeOrderTotal($orderCode);

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Item removed from order.',
        ]);
    }

    //Add a product to an existing pending order
    public function addToOrder(Request $request, $orderCode)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
            'size'       => ['nullable', 'string'],
            'notes'      => ['nullable', 'string'],
        ]);

        $order = $this->verifiableOrder($orderCode);

        if (! $order) {
            abort(404);
        }
        if ($order->status != 1) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'This order is no longer editable.',
            ]);
        }

        $product = Product::findOrFail($validated['product_id']);

        $size = $validated['size'] ?? '';
        if (empty($size)) {
            $firstSize = ProductSize::where('product_id', $product->id)->first();
            $size = $firstSize?->size ?? 'Standard';
        }

        // Compute discounted unit price
        $today = Carbon::today();
        $disc  = Discount::where(function ($q) use ($product) {
            $q->where('product_id', $product->id)->orWhereNull('product_id');
        })
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('product_id', 'desc')
            ->first();

        $sizeRow   = ProductSize::where('product_id', $product->id)->where('size', $size)->first();
        $unitPrice = (float) ($sizeRow->price ?? 0);

        if ($disc) {
            $unitPrice = $unitPrice - ($unitPrice * $disc->discount_percentage / 100);
        }

        $waiter   = $this->waiter();
        $existing = Order::where('order_code', $orderCode)
            ->where('product_id', $product->id)
            ->where('size', $size)
            ->first();

        if ($existing) {
            $existing->quantity += $validated['quantity'];
            if (empty($existing->notes) && ! empty($validated['notes'])) {
                $existing->notes = $validated['notes'];
            }
            $existing->save();
        } else {
            Order::create([
                'user_id'    => $waiter->id,
                'waiter_id'  => $waiter->id,
                'branch_id'  => $waiter->branch_id,
                'product_id' => $product->id,
                'order_code' => $orderCode,
                'quantity'   => $validated['quantity'],
                'totalprice' => round($unitPrice, 2),
                'status'     => 1,
                'size'       => $size,
                'notes'      => $validated['notes'],
            ]);
        }

        $this->recomputeOrderTotal($orderCode);

        //KOT auto-print: flagged once after the order update is submitted.
        session()->flash('kotOrderCode', $orderCode);

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Item added to order.',
        ]);
    }

    //Update order type / payment method for a pending order
    public function updateOrderMeta(Request $request, $orderCode)
    {
        $validated = $request->validate([
            'order_type'     => ['required', 'string', 'in:eat_in,take_away,delivery'],
            'payment_method' => ['required', 'string', 'in:cash,card,mobile'],
        ]);

        $order = $this->verifiableOrder($orderCode);

        if (! $order) {
            abort(404);
        }
        if ($order->status != 1) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'This order is no longer editable.',
            ]);
        }

        $orderTypeMapping = ['eat_in' => 1, 'take_away' => 2, 'delivery' => 3];
        $orderType        = $orderTypeMapping[$validated['order_type']];

        Order::where('order_code', $orderCode)->update([
            'order_type'     => $orderType,
            'payment_method' => $validated['payment_method'],
        ]);

        $record = PaymentRecord::where('order_code', $orderCode)->first();
        if ($record) {
            $record->payment_method = ucfirst($validated['payment_method']);
            $record->save();
        }

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Order details updated.',
        ]);
    }

    //=== Customer Session / Running Bill ===

    //Verify a session belongs to the waiter + branch (server-side ownership)
    private function verifiableSession($sessionId)
    {
        $waiter = $this->waiter();

        $session = CustomerSession::where('id', $sessionId)
            ->where('waiter_id', $waiter->id)
            ->where('branch_id', $waiter->branch_id)
            ->first();

        if (! $session) {
            abort(404);
        }

        return $session;
    }

    //List the waiter's active (open + bill requested) sessions
    public function sessions()
    {
        $waiter = $this->waiter();

        $activeSessions = CustomerSession::with(['branch'])
            ->where('waiter_id', $waiter->id)
            ->where('branch_id', $waiter->branch_id)
            ->whereIn('status', [CustomerSession::STATUS_OPEN, CustomerSession::STATUS_BILL_REQUESTED])
            ->orderByDesc('opened_at')
            ->get();

        return view('waiter.sessions.index', [
            'activeSessions' => $activeSessions,
            'waiter'         => $waiter,
        ]);
    }

    //Start a new session for this waiter
    public function createSession()
    {
        $waiter = $this->waiter();

        $session = CustomerSession::create([
            'session_code' => 'SES-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -4)),
            'waiter_id'    => $waiter->id,
            'branch_id'    => $waiter->branch_id,
            'status'       => CustomerSession::STATUS_OPEN,
            'opened_at'    => now(),
        ]);

        return redirect()->route('waiter.sessionDetails', $session)->with('alert', [
            'type'    => 'success',
            'message' => 'Session #' . $session->session_code . ' started.',
        ]);
    }

    //Session detail: orders (grouped), running total, status
    public function sessionDetails($sessionId)
    {
        $session = $this->verifiableSession($sessionId);

        $orders = Order::with(['product', 'branch'])
            ->where('session_id', $session->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = $orders->groupBy('order_code');

        $subTotal = $session->subtotal();

        $taxSetting = TaxSetting::first();
        $taxRate    = $taxSetting->tax_rate ?? 0;
        $taxAmount  = $this->computeRounded($subTotal * $taxRate / 100);
        $total      = $this->computeRounded($subTotal + $taxAmount);

        return view('waiter.sessions.show', [
            'session'       => $session,
            'groupedOrders' => $grouped,
            'subTotal'      => $subTotal,
            'taxRate'       => $taxRate,
            'taxAmount'     => $taxAmount,
            'total'         => $total,
        ]);
    }

    //Get (or generate) the cart order code used for a given session
    private function sessionCartCode(CustomerSession $session)
    {
        $key  = 'waiterSessionCart_' . $session->id;
        $code = session($key);

        if (empty($code)) {
            $code = 'SES-' . $session->id . '-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
            session([$key => $code]);
        }

        return $code;
    }

    //Menu + cart for adding items to a session (reuses the new-order menu UI)
    public function sessionNewOrder(Request $request, $sessionId)
    {
        $session = $this->verifiableSession($sessionId);

        if (! $session->isOpen()) {
            return redirect()->route('waiter.sessionDetails', $session)->with('alert', [
                'type'    => 'error',
                'message' => 'This session is no longer open. The bill has been requested.',
            ]);
        }

        $waiter   = $this->waiter();
        $orderCode = $this->sessionCartCode($session);

        $categories = Category::all();
        $selectedCategoryId = $request->query('categoryId');

        $productQuery = Product::query();
        if ($selectedCategoryId) {
            $productQuery->where('category_id', $selectedCategoryId);
        }
        if ($request->filled('searchKey')) {
            $productQuery->where('name', 'like', '%' . $request->searchKey . '%');
        }
        $products = $productQuery->get();

        $today     = Carbon::today();
        $discounts = Discount::whereDate('start_date', '<=', $today)
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

        foreach ($products as $product) {
            $product->sizes = ProductSize::where('product_id', $product->id)->get(['size', 'price']);
            $discount = $discountedProducts[$product->id] ?? $allDiscountPercentage;
            $product->discount_percentage = $discount;
        }

        $cartItems = $this->getCartItems($waiter->id, $orderCode);

        $subTotal = $cartItems->sum(fn($item) => $item->discount_price * $item->cart_qty);

        $taxSetting = TaxSetting::first();
        $taxRate    = $taxSetting->tax_rate ?? 0;
        $taxAmount  = $this->computeRounded($subTotal * $taxRate / 100);
        $total      = $this->computeRounded($subTotal + $taxAmount);

        return view('waiter.sessions.menu', [
            'session'           => $session,
            'products'          => $products,
            'categories'        => $categories,
            'selectedCategoryId' => $selectedCategoryId,
            'orderCode'         => $orderCode,
            'cartItems'         => $cartItems,
            'subTotal'          => $subTotal,
            'taxAmount'         => $taxAmount,
            'taxRate'           => $taxRate,
            'total'             => $total,
        ]);
    }

    //Place the session's cart into the session as a new order group (running bill, no single payment)
    public function placeSessionOrder(Request $request, $sessionId)
    {
        $session = $this->verifiableSession($sessionId);

        if (! $session->isOpen()) {
            return redirect()->route('waiter.sessionDetails', $session)->with('alert', [
                'type'    => 'error',
                'message' => 'This session is no longer open. Additional items are not allowed.',
            ]);
        }

        $validated = $request->validate([
            'orderCode'     => 'required|string',
            'paymentMethod' => 'required|string|in:cash,card,mobile',
            'orderType'     => 'required|string|in:eat_in,take_away,delivery',
        ]);

        $orderCode = $validated['orderCode'];
        $waiter    = $this->waiter();

        $carts = Cart::join('products', 'carts.product_id', '=', 'products.id')
            ->leftJoin('discounts', function ($join) {
                $join->on('products.id', '=', 'discounts.product_id')
                    ->whereDate('discounts.start_date', '<=', now())
                    ->whereDate('discounts.end_date', '>=', now());
            })
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->selectRaw('IF(discounts.product_id IS NOT NULL,
                            product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                            product_sizes.price
                            ) as discount_price,
                            carts.qty,
                            carts.orderCode,
                            carts.product_id,
                            carts.size,
                            carts.notes')
            ->where('carts.orderCode', $orderCode)
            ->where('carts.user_id', $waiter->id)
            ->get();

        if ($carts->isEmpty()) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'Your cart is empty.',
            ]);
        }

        $orderTypeMapping = ['eat_in' => 1, 'take_away' => 2, 'delivery' => 3];
        $orderType        = $orderTypeMapping[$validated['orderType']];

        foreach ($carts as $cart) {
            Order::create([
                'user_id'          => $waiter->id,
                'waiter_id'        => $waiter->id,
                'branch_id'        => $waiter->branch_id,
                'session_id'       => $session->id,
                'product_id'       => $cart->product_id,
                'order_code'       => $cart->orderCode,
                'quantity'         => $cart->qty,
                'totalprice'       => $cart->discount_price,
                'status'           => 1,
                'payment_method'   => $validated['paymentMethod'],
                'order_type'       => $orderType,
                'size'             => $cart->size,
                'notes'            => $cart->notes,
            ]);
        }

        Cart::where('orderCode', $orderCode)->where('user_id', $waiter->id)->delete();

        session()->forget('waiterSessionCart_' . $session->id);

        //KOT auto-print: flagged once after successful submission.
        session()->flash('kotOrderCode', $orderCode);

        return redirect()->route('waiter.sessionDetails', $session)->with('alert', [
            'type'    => 'success',
            'message' => 'Order added to session #' . $session->session_code . '.',
        ]);
    }

    //Request the bill: open -> bill_requested (stops further additions server-side).
    //Idempotent: concurrent double-submit cannot flip the state twice.
    public function requestBill($sessionId)
    {
        $waiter = $this->waiter();

        $result = DB::transaction(function () use ($sessionId, $waiter) {
            $session = CustomerSession::where('id', $sessionId)
                ->where('waiter_id', $waiter->id)
                ->where('branch_id', $waiter->branch_id)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                return null;
            }

            if ($session->isBillRequested()) {
                return ['already', $session];
            }

            if (! $session->isOpen()) {
                return ['not_open', $session];
            }

            if ($session->ordersCount() === 0) {
                return ['empty', $session];
            }

            $session->status            = CustomerSession::STATUS_BILL_REQUESTED;
            $session->bill_requested_at = now();
            $session->save();

            return ['ok', $session];
        });

        if ($result === null) {
            abort(404);
        }

        [$outcome, $session] = $result;

        $messages = [
            'already'  => 'The bill for session #' . $session->session_code . ' has already been requested.',
            'not_open' => 'This session is no longer open.',
            'empty'    => 'Cannot request the bill for an empty session.',
            'ok'       => 'Bill requested for session #' . $session->session_code . '. No more items can be added.',
        ];

        return back()->with('alert', [
            'type'    => $outcome === 'ok' ? 'success' : 'error',
            'message' => $messages[$outcome] ?? 'Could not request the bill for this session.',
        ]);
    }
}
