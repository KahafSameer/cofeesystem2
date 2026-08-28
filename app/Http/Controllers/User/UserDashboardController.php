<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserContactRequest;
use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliveryFees;
use App\Models\Discount;
use App\Models\Order;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\Review;
use App\Models\TaxSetting;
use App\Models\User;
use App\Models\UserContact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    //
    public function index()
    {
        $user             = auth()->user();
        $showAddressModal = false;

        // dd($showAddressModal);

        if ($user->role === 'user' && ! $user->address) {
            $showAddressModal = true;
        }

        $discountPercentage = Discount::whereDate('start_date', '<=', Carbon::now())
            ->whereDate('end_date', '>=', Carbon::now())
            ->orderBy('start_date', 'desc')
            ->first();

        $showReviews = Review::where('rating', '>=', 3)
            ->select('name', 'rating', 'subject')
            ->get();

        // dd($showReviews->toArray());
        return view('user.home', compact('discountPercentage', 'showReviews', 'showAddressModal'));
    }

    public function updateProfile(Request $request)
    {
        // dd($request->all());
        $this->validationCheck($request);
        $customerData = $this->requestCustomerData($request);

        if ($request->hasFile('image')) {
            if ($request->oldImage != null) {
                if (file_exists(public_path('/public/customerProfile/' . $request->oldImage))) {
                    unlink(public_path('/public/customerProfile/' . $request->oldImage));
                }
            }
            //upload new image
            $fileName = uniqid() . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path() . '/customerProfile/', $fileName);
            $customerData['profile'] = $fileName;
        } else {
            $customerData['profile'] = $request->oldImage;
        }

        // dd($customerData);
        User::where('id', Auth::user()->id)->update($customerData);

        return back()->with('alert',
            [
                'type'    => 'success',
                'message' => 'Profile Updated successfully!',
            ]);
    }

    private function validationCheck($request)
    {
        $rules = [
            'image'   => ['mimes:png,jpeg,svg,gif,bmp,webp'],
            'phone'   => ['required', 'unique:users,phone,' . Auth::user()->id],
            'address' => ['required', 'string', 'max:255'],

        ];
        if (Auth::user()->provider == 'simple') {
            $validator['name'] = 'required';
        }

        $validator = $request->validate($rules);
    }

    private function requestCustomerData($request)
    {
        $data            = [];
        $data['name']    = Auth::user()->provider == 'simple' ? $request->name : Auth::user()->name;
        $data['phone']   = $request->phone;
        $data['address'] = $request->address;
        $data['role']    = Auth::user()->role;

        return $data;
    }

    public function saveAddress(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'address' => 'required|string|max:255',
        ]);

        $user          = auth()->user();
        $user->address = $validated['address'];
        $user->save();

        return redirect()->route('userDashboard')->with('alert',
            [
                'type' => 'success', 'message' => 'Address saved successfully',
            ]);
    }

    public function about()
    {
        return view('user.about');
    }

    public function customerProfile()
    {
        return view('user.customerProfile');
    }

    //Menu Page
    public function climenu($category_id = null)
    {
        // dd(request('minPrice'));

        $discount = Discount::whereDate('start_date', '<=', Carbon::now())
            ->whereDate('end_date', '>=', Carbon::now())
            ->orderBy('start_date', 'desc')
            ->get();

        // dd($discount->toArray());

        $query = Product::selectRaw('
                                    products.id,
                                    products.name,
                                    products.image,
                                    categories.name as category_name,
                                    discounts.discount_percentage,
                                    MIN(
                                        IF(discounts.product_id IS NOT NULL,
                                            product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                            product_sizes.price
                                        )
                                    ) as discountPrice')
            ->leftJoin('discounts', function ($join) use ($discount) {
                $join->on('products.id', '=', 'discounts.product_id')
                    ->whereDate('discounts.start_date', '<=', now())
                    ->whereDate('discounts.end_date', '>=', now());
            })
            ->leftJoin('product_sizes', 'products.id', '=', 'product_sizes.product_id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->when(request('searchKey'), fn($q) =>
                $q->where('products.name', 'like', '%' . request('searchKey') . '%')
            )
            ->when($category_id, fn($q) =>
                $q->where('products.category_id', $category_id)
            )
            ->groupBy('products.id', 'products.name', 'products.image', 'categories.name', 'discounts.discount_percentage')
            ->orderBy('products.id');

        // dd($query);

        $products = $query->paginate(6)->appends(request()->only(['searchKey', 'minPrice', 'maxPrice']));

// Attach all sizes for each product
        $products->getCollection()->transform(function ($product) {
            $product->sizes = DB::table('product_sizes')
                ->where('product_id', $product->id)
                ->get(['size', 'price']);
            return $product;
        });

// dd($products->toArray());

        $categories = Category::all();

        return view('user.menu', compact('categories', 'products', 'discount'));
    }

//Show the Cart
    public function cartPage()
    {
        $userId = Auth::user()->id;

        $today     = Carbon::today();
        $discounts = Discount::select('discount_percentage', 'product_id')
            ->whereDate('start_date', $today)
            ->get();

        $smallestUnit = 10;
        $products     = Product::all();
        $cartInfo     = Cart::where('user_id', Auth::id())->get()->keyBy('product_id');

        $cartItems = Product::selectRaw('IF(discounts.product_id IS NOT NULL,
                                product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                 product_sizes.price
                                 ) as discountPrice,
                                product_sizes.size,
                                products.id,
                                products.name,
                                products.image,
                                product_sizes.price,
                                carts.qty as cart_qty,
                                carts.id as cartId,
                                carts.orderCode')
            ->leftJoin('carts', 'products.id', '=', 'carts.product_id')
            ->leftJoin('discounts', function ($join) use ($discounts) {
                $join->on('products.id', '=', 'discounts.product_id')
                    ->whereDate('discounts.start_date', '<=', now())
                    ->whereDate('discounts.end_date', '>=', now());
            })
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->where('carts.user_id', $userId)
            ->get();

        // Use private function
        $totals = $this->calculateCartTotals($userId);

        $cartCount = Cart::where('user_id', $userId)->count();

        return view('user.cart', compact(
            'cartItems', 'cartInfo', 'products', 'cartCount'
        ))->with([
            'subtotal'    => $totals['subtotal'],
            'taxRate'     => $totals['taxRate'],
            'taxAmount'   => $totals['taxAmount'],
            'deliveryFee' => $totals['deliveryFee'],
            'total'       => $totals['total'],
        ]);
    }

//add the Cart to table
    public function addToCart(Request $request)
    {
        // dd($request->all());
        $userId    = Auth::user()->id;
        $productId = $request->input('product_id');
        $quantity  = (int) $request->input('quantity');
        $size      = $request->input('size');
        $notes     = $request->input('notes');

        $user = auth()->user();

        if ($user->role === 'user' && ! $user->address) {
            return redirect()->route('customerProfile')->with('alert', [
                'type'    => 'warning',
                'message' => 'You have not added your delivery address. Please update it in your profile.',
            ]);
        }

        $existingCart = Cart::where('user_id', $userId)->first();

        if ($existingCart) {
            $orderCode = $existingCart->orderCode;
        } else {
            $orderCode = 'ORD-' . strtoupper(uniqid());
        }

        // dd($size);

        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('orderCode', $orderCode)
            ->where('size', $size)
            ->first();
        // dd($cartItem);

        if ($cartItem) {

            $cartItem->orderCode = $orderCode;
            $cartItem->qty += $quantity;

            // Update notes only if new note is provided
            if (empty($cartItem->notes) && ! empty($notes)) {
                $cartItem->notes = $notes;
            }

            $cartItem->save();
        } else {
            Cart::create([
                'user_id'    => $userId,
                'product_id' => $productId,
                'qty'        => $quantity,
                'orderCode'  => $orderCode,
                'size'       => $size,
                'notes'      => $notes,
            ]);
        }

        // Log::info('Cart item created successfully.');

        return back()->with('success', 'Item added to cart successfully.');

    }

//Update Cart item from the Cart Page
    public function updateCart(Request $request)
    {
        $userId = Auth::id();

        // Update cart item qty
        Cart::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->where('size', $request->size)
            ->update([
                'qty' => $request->quantity,
            ]);

        // Recalculate totals using private function
        $totals = $this->calculateCartTotals($userId);

        return response()->json([
            'success'     => true,
            'subtotal'    => $totals['subtotal'],
            'taxAmount'   => $totals['taxAmount'],
            'deliveryFee' => $totals['deliveryFee'],
            'total'       => $totals['total'],
        ]);
    }

//Remove the cart item from the Cart Page
    public function removeCart($cartId)
    {

        // dd($cartId);
        Cart::where('id', $cartId)->delete();

        $today     = Carbon::today();
        $cartCount = Cart::where('user_id', auth()->id())
            ->whereDate('created_at', $today)
            ->count();

        // dd($cartCount);
        if ($cartCount === 0) {
            return redirect()->route('climenu');
        }
        return redirect()->route('cartPage');

    }

    private function calculateCartTotals($userId)
    {
        $smallestUnit = 10;

        $cartItems = Product::selectRaw('IF(discounts.product_id IS NOT NULL,
                                        product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                                        product_sizes.price
                                        ) as discountPrice,
                                        carts.qty as cart_qty')
            ->leftJoin('carts', 'products.id', '=', 'carts.product_id')
            ->leftJoin('discounts', function ($join) {
                $join->on('products.id', '=', 'discounts.product_id')
                    ->whereDate('discounts.start_date', '<=', now())
                    ->whereDate('discounts.end_date', '>=', now());
            })
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->where('carts.user_id', $userId)
            ->get();

        $subtotal = $cartItems->sum(fn($item) => $item->discountPrice * $item->cart_qty);

        $taxSetting = TaxSetting::first();
        $taxRate    = $taxSetting->tax_rate;
        $taxAmount  = ceil((($subtotal * $taxRate) / 100) / $smallestUnit) * $smallestUnit;

        $fullAddress  = auth()->user()->address;
        $addressParts = array_map('trim', explode(',', $fullAddress));

        $location = null;
        foreach ($addressParts as $part) {
            $location = DeliveryFees::select('fees')->where('township', 'LIKE', "%$part%")->first();
            if ($location) {
                break;
            }
        }

        $deliveryFee = $location ? $location->fees : 0;
        $total       = ceil(($subtotal + $taxAmount + $deliveryFee) / $smallestUnit) * $smallestUnit;

        return [
            'subtotal'    => $subtotal,
            'taxRate'     => $taxRate,
            'taxAmount'   => $taxAmount,
            'deliveryFee' => $deliveryFee,
            'total'       => $total,
        ];
    }

//When user click Payment Confirm
    public function paymentConfirm(Request $request)
    {
        // dd($request->all());

        $carts = Cart::selectRaw('
                IF(discounts.product_id IS NOT NULL,
                    product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                    product_sizes.price
                ) * SUM(carts.qty) as item_total,

                IF(discounts.product_id IS NOT NULL,
                    product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                    product_sizes.price
                ) as totalPrice,

                carts.user_id as cartid,
                carts.orderCode,
                carts.product_id,
                carts.size,
                SUM(carts.qty) as quantity
            ')
            ->leftJoin('products', 'carts.product_id', '=', 'products.id')
            ->leftJoin('discounts', function ($join) {
                $join->on('products.id', '=', 'discounts.product_id')
                    ->whereDate('discounts.start_date', '<=', Carbon::now())
                    ->whereDate('discounts.end_date', '>=', Carbon::now());
            })
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->where('carts.orderCode', $request->orderCode)
            ->groupBy('carts.orderCode', 'carts.user_id', 'carts.product_id',
                'discounts.product_id', 'product_sizes.price',
                'discounts.discount_percentage', 'carts.size')
            ->get();

        // dd($carts->toArray());
        foreach ($carts as $cart) {
            Order::create([
                'user_id'        => $cart->cartid,
                'product_id'     => $cart->product_id,
                'order_code'     => $cart->orderCode,
                'quantity'       => $cart->quantity,
                'totalprice'     => $cart->totalPrice,
                'status'         => 1,
                'payment_method' => 'card',
                'order_type'     => 3,
                'size'           => $cart->size,
            ]);
        }

        Cart::where('carts.orderCode', $request->orderCode)->delete();

        $order = Order::where('order_code', $request->orderCode)->first();

        // dd($order);

        if (! $order) {
            return response()->json(['error' => 'Order not found after processing carts.'], 404);
        }

        $smallestUnit = 10;
        $subTotal     = $carts->sum('item_total');

        // dd($subTotal);
        $taxSetting = TaxSetting::first();
        $taxRate    = $taxSetting->tax_rate;
        $taxAmount  = ceil((($subTotal * $taxRate) / 100) / $smallestUnit) * $smallestUnit;

        $fullAddress  = auth()->user()->address;
        $addressParts = array_map('trim', explode(',', $fullAddress));

        $location = null;

        foreach ($addressParts as $part) {
            $location = DeliveryFees::select('fees')->where('township', 'LIKE', "%$part%")->first();
            if ($location) {
                break;
            }
        }

        $deliveryFee = $location ? $location->fees : 0;

        // dd($deliveryFee);

        $total = ceil(($subTotal + $taxAmount + $deliveryFee) / $smallestUnit) * $smallestUnit;

        // dd($total);
        $paymentRecord = PaymentRecord::create([
            'order_code'     => $order->order_code,
            'user_id'        => auth()->id(),
            'net_amount'     => $total,
            'paid_amount'    => $total,
            'change_amount'  => '0.00',
            'payment_method' => 'card',
            'status'         => '1',
        ]);

        // dd($paymentRecord);
        $order->update(['status' => 1]);

        return redirect()->route('climenu')->with('alert', ['type' => 'success', 'message' => 'Payment successful']);

    }

//Reivew Page
    public function reviewPage()
    {
        return view('user.review');
    }

//Review the Order list
    public function reviewOrder(Request $request)
    {
        // dd($request);
        $userId = Auth::user()->id;

        $today = Carbon::today();

        $latestOrderCode = Order::where('user_id', $userId)
            ->where('status', 1)
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->value('order_code');

        if ($latestOrderCode) {
            //Retrieve all transactions with latest order code
            $orders = Order::select('products.id as prodID', 'products.name', 'products.image',
                'product_sizes.price', 'orders.quantity', 'orders.totalprice', 'orders.notes',
                'orders.order_code', 'orders.created_at', 'orders.size')
                ->leftJoin('products', 'products.id', '=', 'orders.product_id')
                ->leftJoin('product_sizes', function ($join) {
                    $join->on('products.id', '=', 'product_sizes.product_id')
                        ->on('orders.size', '=', 'product_sizes.size');
                })
                ->where('orders.user_id', $userId)
                ->where('orders.status', 1)
                ->where('orders.order_code', $latestOrderCode)
                ->get();
        } else {
            // No orders found for today, return an empty collection
            $orders = collect();
        }

        // dd($latestOrderCode);

        //for the View order button
        $orderCount = Order::where('user_id', $userId)->count();

        $smallestUnit = 10;

        $orderTotal = PaymentRecord::where('order_code', $latestOrderCode)->value('paid_amount');

        // dd($orderTotal);

        $taxSetting = TaxSetting::first();
        $taxRate    = $taxSetting->tax_rate;
        $taxAmount  = ceil((($orderTotal * $taxRate) / 100) / $smallestUnit) * $smallestUnit;

        // dd($orderTotal);

        $fullAddress  = auth()->user()->address;
        $addressParts = array_map('trim', explode(',', $fullAddress));

        $location = null;

        foreach ($addressParts as $part) {
            $location = DeliveryFees::select('fees')->where('township', 'LIKE', "%$part%")->first();
            if ($location) {
                break;
            }
        }

        $deliveryFee = $location ? $location->fees : 0;

        // dd($location);

        return view('user.order', compact('orders', 'orderCount', 'orderTotal', 'deliveryFee'));
    }

    public function addReview(Request $request)
    {

        $userId = Auth::user()->id;

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'rating'  => 'required|string|min:1|max:5',
            'subject' => 'required|string|max:1000',
        ]);

        // dd($request);

        Review::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        // dd($userId);
        return redirect()->route('userDashboard')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Your REVIEW has been sent',
            ]);
    }

    public function contactus()
    {
        return view('user.contact');
    }

    public function addContact(StoreUserContactRequest $request)
    {
        // $userId = Auth::user()->id;

        // $validated = $request->validate([
        //     'name'         => 'required|string|max:255',
        //     'phone'        => 'required|regex:/^[0-9]{7,12}$/',
        //     'inquiry_type' => 'required',
        //     'message'      => 'required',
        // ]);

        //Style1
        // $contact = UserContact::create([
        //     'user_id'       => $userId,
        //     'name'          => $validated['name'],
        //     'phone'         => $validated['phone'],
        //     'inquiry_type'  => $validated['inquiry_type'],
        //     'message'       => $validated['message'],

        // ]);

        // Style 2
        // UserContact::create([
        //     'user_id'      => Auth::id(),
        //     ...$validated
        // ]);

        // Style 3
        // tap($request->validate([
        //     'name'         => 'required|string|max:255',
        //     'phone'        => 'required|regex:/^[0-9]{7,12}$/',
        //     'inquiry_type' => 'required',
        //     'message'      => 'required',
        // ]), function ($validated) {
        //     UserContact::create([
        //         'user_id' => Auth::id(),
        //         ...$validated,
        //     ]);
        // });

        // Style 4
        UserContact::create([
            'user_id' => Auth::id(),
            ...$request->validated(),
        ]);

        return redirect()->route('userDashboard')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Your contact has been sent',
            ]);

    }

}
