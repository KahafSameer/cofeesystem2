<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CashierDraft;
use App\Models\Category;
use App\Models\CustomerSession;
use App\Models\DeliveryFees;
use App\Models\Order;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\TaxSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    private const SMALLEST_UNIT = 10;

    private const ORDER_TYPE_MAP = [
        'eat_in'    => CashierDraft::ORDER_TYPE_EAT_IN,
        'take_away' => CashierDraft::ORDER_TYPE_TAKE_AWAY,
        'delivery'  => CashierDraft::ORDER_TYPE_DELIVERY,
    ];

    /**
     * Branches a cashier may see/operate sessions for.
     * - Admin (full-access till operator): every active branch.
     * - Cashier: strictly the authenticated branch.
     * Branch isolation for cashiers must never be relaxed.
     */
    private function allowedSessionBranchIds(): array
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return Branch::query()->pluck('id')->all();
        }

        return [$user->branch_id];
    }

    /**
     * POS main page with the Active Tickets strip and the current ticket.
     */
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $cashierId = auth()->id();

        // Every unfinished ticket belonging to THIS cashier + THIS branch.
        $drafts = CashierDraft::where('cashier_id', $cashierId)
            ->where('branch_id', $branchId)
            ->whereIn('status', [CashierDraft::STATUS_ACTIVE, CashierDraft::STATUS_SUSPENDED])
            ->orderByDesc('updated_at')
            ->get();

        // Current ticket comes from the session - validated against ownership.
        $orderCode = session('cashierOrderCode');
        $current = null;
        if ($orderCode) {
            $current = $drafts->firstWhere('order_code', $orderCode);
            if (! $current) {
                session()->forget('cashierOrderCode');
                $orderCode = null;
            }
        }

        $categories = Category::all();

        // Product grid (optional category filter), mirroring the admin POS.
        $productbyCategory = collect();
        $selectedCategoryId = null;
        if ($request->filled('categoryId')) {
            $selectedCategoryId = $request->query('categoryId');
            $productbyCategory = Product::with('sizes')
                ->where('category_id', $selectedCategoryId)
                ->get();
        }

        // Cart + totals for the current ticket.
        $cartItems = collect();
        $itemCount = 0;
        $subTotal = 0.0;
        $taxAmount = 0.0;
        $deliveryFee = 0.0;
        $total = 0.0;

        if ($current) {
            $cartItems = $this->cartItems($orderCode);
            $itemCount = (int) $cartItems->sum('cart_qty');

            $subTotal = (float) $cartItems->sum(fn ($i) => (float) $i->discountPrice * (int) $i->cart_qty);
            $taxAmount = $this->roundUp($subTotal * ($this->taxRate() / 100));
            $deliveryFee = $this->deliveryFeeFor($current->delivery_location_id);
            $total = $this->roundUp($subTotal + $taxAmount + $deliveryFee);
        }

        // Per-ticket summaries for the Active Tickets strip.
        $summary = $this->draftSummaries($drafts);

        // Surfaced on the Running Bills button so the cashier knows a waiter
        // has requested a bill without leaving the POS screen.
        $pendingBillCount = CustomerSession::whereIn('branch_id', $this->allowedSessionBranchIds())
            ->where('status', CustomerSession::STATUS_BILL_REQUESTED)
            ->count();

        $deliveryLocations = DeliveryFees::all();

        return view('cashier.pos.index', [
            'drafts'            => $drafts,
            'summary'           => $summary,
            'current'           => $current,
            'orderCode'         => $orderCode,
            'orderType'         => $current ? $current->orderTypeString() : 'eat_in',
            'categories'        => $categories,
            'selectedCategoryId'=> $selectedCategoryId,
            'productbyCategory' => $productbyCategory,
            'cartItems'         => $cartItems,
            'itemCount'         => $itemCount,
            'subTotal'          => $subTotal,
            'taxRate'           => $this->taxRate(),
            'taxAmount'         => $taxAmount,
            'deliveryFee'       => $deliveryFee,
            'total'             => $total,
            'deliveryLocations' => $deliveryLocations,
            'pendingBillCount'  => $pendingBillCount,
        ]);
    }

    /**
     * NEW ORDER - create an independent, empty ticket and make it current.
     */
    public function createDraft(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        $orderCode = $this->uniqueOrderCode();

        $draft = CashierDraft::create([
            'cashier_id' => auth()->id(),
            'branch_id'  => $branchId,
            'order_code' => $orderCode,
            'label'      => 'Ticket ' . strtoupper(substr(uniqid(), -4)),
            'status'     => CashierDraft::STATUS_ACTIVE,
            'order_type' => CashierDraft::ORDER_TYPE_EAT_IN,
        ]);

        $request->session()->put('cashierOrderCode', $draft->order_code);

        return response()->json(['status' => 'success', 'orderCode' => $draft->order_code]);
    }

    /**
     * HOLD / SUSPEND - save the unfinished ticket, never create an order/payment.
     */
    public function suspendDraft(Request $request, $orderCode)
    {
        $draft = $this->ownedOpenDraft($orderCode);

        if (! $draft) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        $draft->status = CashierDraft::STATUS_SUSPENDED;
        $draft->save();

        if (session('cashierOrderCode') === $draft->order_code) {
            $request->session()->forget('cashierOrderCode');
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * CONTINUE - reactivate a suspended ticket and open it as current.
     */
    public function resumeDraft(Request $request, $orderCode)
    {
        $draft = $this->ownedOpenDraft($orderCode);

        if (! $draft) {
            abort(404);
        }

        $draft->status = CashierDraft::STATUS_ACTIVE;
        $draft->save();

        $request->session()->put('cashierOrderCode', $draft->order_code);

        return redirect()->route('cashier.index');
    }

    /**
     * DISCARD - archive the draft (kept for history) and drop its cart rows.
     * Never creates an order or payment.
     */
    public function discardDraft(Request $request, $orderCode)
    {
        $draft = $this->ownedOpenDraft($orderCode);

        if (! $draft) {
            abort(404);
        }

        $draft->status = CashierDraft::STATUS_DISCARDED;
        $draft->save();

        Cart::where('orderCode', $draft->order_code)->delete();

        if (session('cashierOrderCode') === $draft->order_code) {
            $request->session()->forget('cashierOrderCode');
        }

        return redirect()->route('cashier.index');
    }

    /**
     * Add a product to the CURRENT ticket only (server-derived, not client).
     */
    public function addItem(Request $request)
    {
        $orderCode = session('cashierOrderCode');

        $draft = $orderCode ? $this->ownedOpenDraft($orderCode) : null;

        if (! $draft) {
            return redirect()->route('cashier.index')->with('alert', [
                'type' => 'error',
                'message' => 'Start a new order before adding items.',
            ]);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
            'size'       => ['nullable', 'string'],
            'notes'      => ['nullable', 'string'],
        ]);

        $size = $validated['size'] ?? '';
        if (empty($size)) {
            $size = optional(ProductSize::where('product_id', $validated['product_id'])->first())->size ?? 'Standard';
        }

        $cartItem = Cart::firstOrNew([
            'user_id'    => auth()->id(),
            'product_id' => $validated['product_id'],
            'orderCode'  => $draft->order_code,
            'size'       => $size,
        ]);

        if ($cartItem->exists) {
            $cartItem->qty += $validated['quantity'];
            if (empty($cartItem->notes) && ! empty($validated['notes'])) {
                $cartItem->notes = $validated['notes'];
            }
        } else {
            $cartItem->qty = $validated['quantity'];
            $cartItem->notes = $validated['notes'];
        }

        $cartItem->save();

        $draft->touch();

        return back()->with('success', 'Item added to ticket.');
    }

    /**
     * Change quantity/notes of a cart row on the current ticket.
     */
    public function updateCart(Request $request)
    {
        $orderCode = session('cashierOrderCode');

        $draft = $orderCode ? $this->ownedOpenDraft($orderCode) : null;

        if (! $draft) {
            abort(404);
        }

        $validated = $request->validate([
            'cart_id' => ['required', 'integer'],
            'qty'     => ['required', 'integer', 'min:1'],
            'notes'   => ['nullable', 'string'],
        ]);

        $cartItem = Cart::where('id', $validated['cart_id'])
            ->where('orderCode', $draft->order_code)
            ->where('user_id', auth()->id())
            ->first();

        if (! $cartItem) {
            abort(404);
        }

        $cartItem->qty = $validated['qty'];
        $cartItem->notes = $validated['notes'] ?? $cartItem->notes;
        $cartItem->save();

        $draft->touch();

        return back()->with('success', 'Ticket updated.');
    }

    /**
     * Remove a cart row from the current ticket.
     */
    public function removeCart(Request $request)
    {
        $orderCode = session('cashierOrderCode');

        $draft = $orderCode ? $this->ownedOpenDraft($orderCode) : null;

        if (! $draft) {
            abort(404);
        }

        $validated = $request->validate(['cart_id' => ['required', 'integer']]);

        $cartItem = Cart::where('id', $validated['cart_id'])
            ->where('orderCode', $draft->order_code)
            ->where('user_id', auth()->id())
            ->first();

        if (! $cartItem) {
            abort(404);
        }

        $cartItem->delete();

        $draft->touch();

        return back()->with('success', 'Item removed from ticket.');
    }

    /**
     * Persist the order type (and delivery location) on the current ticket.
     */
    public function setOrderType(Request $request)
    {
        $orderCode = session('cashierOrderCode');

        $draft = $orderCode ? $this->ownedOpenDraft($orderCode) : null;

        if (! $draft) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }

        $validated = $request->validate([
            'orderType'        => ['required', 'string', 'in:eat_in,take_away,delivery'],
            'deliveryLocation' => ['nullable', 'exists:delivery_fees,id'],
        ]);

        $draft->order_type = self::ORDER_TYPE_MAP[$validated['orderType']];
        $draft->delivery_location_id = $validated['deliveryLocation'] ?? null;
        $draft->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * CONFIRM & PAY - reuse the existing Order + PaymentRecord engine.
     * Authoritative totals are computed server-side from the persisted cart.
     */
    public function charge(Request $request, $orderCode)
    {
        $validated = $request->validate([
            'paymentMethod' => ['required', 'string', 'in:cash,card,mobile'],
            'cashReceived'  => ['required', 'numeric', 'min:0'],
            'changeDue'     => ['nullable', 'numeric'],
            'orderType'     => ['nullable', 'string', 'in:eat_in,take_away,delivery'],
            'deliveryLocation' => ['nullable', 'exists:delivery_fees,id'],
            'totalAmount'   => ['nullable', 'numeric'],
        ]);

        try {
            $result = DB::transaction(function () use ($validated, $orderCode) {
                // Lock the draft row so parallel duplicate charges serialize.
                $draft = CashierDraft::where('order_code', $orderCode)
                    ->where('cashier_id', auth()->id())
                    ->where('branch_id', auth()->user()->branch_id)
                    ->lockForUpdate()
                    ->first();

                if (! $draft) {
                    return ['error' => 'Ticket not found.', 'status' => 404];
                }

                if ($draft->isPaid()) {
                    return ['error' => 'This ticket has already been paid.', 'status' => 422];
                }

                if ($draft->isDiscarded() || $draft->carts()->count() === 0) {
                    return ['error' => 'This ticket has no items.', 'status' => 422];
                }

                $orderTypeInt = (int) $draft->order_type;
                if (! in_array($orderTypeInt, self::ORDER_TYPE_MAP, true)) {
                    $orderTypeInt = CashierDraft::ORDER_TYPE_EAT_IN;
                }
                $orderTypeString = array_search($orderTypeInt, self::ORDER_TYPE_MAP, true);

                $deliveryLocationId = $draft->delivery_location_id ?: ($validated['deliveryLocation'] ?? null);

                // Authoritative cart snapshot (discount prices resolved server-side).
                $carts = $this->cartRowsForCharge($draft->order_code);

                foreach ($carts as $cart) {
                    Order::create([
                        'user_id'              => auth()->id(),
                        'waiter_id'            => null,
                        'branch_id'            => auth()->user()->branch_id,
                        'session_id'           => null,
                        'product_id'           => $cart->product_id,
                        'order_code'           => $cart->orderCode,
                        'quantity'             => $cart->qty,
                        'totalprice'           => $cart->discount_price,
                        'status'               => 1,
                        'payment_method'       => $validated['paymentMethod'],
                        'order_type'           => $orderTypeInt,
                        'size'                 => $cart->size,
                        'notes'                => $cart->notes,
                        'delivery_location_id' => $deliveryLocationId,
                    ]);
                }

                // Authoritative totals - never trust the client's totalAmount.
                $subTotal = (float) $carts->sum('item_total');
                $taxAmount = $this->roundUp($subTotal * ($this->taxRate() / 100));
                $deliveryFee = $this->deliveryFeeFor($deliveryLocationId);
                $total = $this->roundUp($subTotal + $taxAmount + $deliveryFee);

                $paymentMethod = strtolower($validated['paymentMethod']);
                $paidAmount = $paymentMethod === 'cash' ? (float) $validated['cashReceived'] : $total;
                $changeDue = max(0.0, $paidAmount - $total);

                if ($paymentMethod === 'cash' && $paidAmount < $total) {
                    return ['error' => 'Insufficient cash received.', 'status' => 422];
                }

                PaymentRecord::create([
                    'order_code'     => $draft->order_code,
                    'user_id'        => auth()->id(),
                    'net_amount'     => $total,
                    'paid_amount'    => $paidAmount,
                    'change_amount'  => round($changeDue, 2),
                    'payment_method' => ucfirst($paymentMethod),
                    'status'         => 1,
                ]);

                // Finalize: remove the cart and archive the ticket.
                Cart::where('orderCode', $draft->order_code)->delete();

                $draft->status = CashierDraft::STATUS_PAID;
                $draft->order_type = $orderTypeInt;
                $draft->delivery_location_id = $deliveryLocationId;
                $draft->save();

                if (session('cashierOrderCode') === $draft->order_code) {
                    session()->forget('cashierOrderCode');
                }

                return [
                    'status'    => 200,
                    'message'   => 'Order confirmed successfully.',
                    'orderCode' => $draft->order_code,
                ];
            });

            return response()->json([
                'message'   => $result['message'] ?? $result['error'] ?? 'Unknown error',
                'orderCode' => $result['orderCode'] ?? null,
            ], $result['status'] ?? 500);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not finalize the ticket.'], 500);
        }
    }

    // ---------- private helpers ----------

    private function ownedOpenDraft(string $orderCode): ?CashierDraft
    {
        return CashierDraft::where('cashier_id', auth()->id())
            ->where('branch_id', auth()->user()->branch_id)
            ->where('order_code', $orderCode)
            ->whereIn('status', [CashierDraft::STATUS_ACTIVE, CashierDraft::STATUS_SUSPENDED])
            ->first();
    }

    private function uniqueOrderCode(): string
    {
        do {
            $code = 'CSR-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
        } while (CashierDraft::where('order_code', $code)->exists());

        return $code;
    }

    private function taxRate(): float
    {
        return (float) (optional(TaxSetting::first())->tax_rate ?? 0);
    }

    private function roundUp(float $value): float
    {
        return (float) ceil($value / self::SMALLEST_UNIT) * self::SMALLEST_UNIT;
    }

    private function deliveryFeeFor($deliveryLocationId): float
    {
        if (! $deliveryLocationId) {
            return 0.0;
        }

        return (float) (optional(DeliveryFees::find($deliveryLocationId))->fees ?? 0);
    }

    /**
     * Cart rows of one ticket, with discount price + line total resolved.
     */
    private function cartRowsForCharge(string $orderCode)
    {
        return Cart::selectRaw('
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
            ->where('carts.orderCode', $orderCode)
            ->get();
    }

    /**
     * Items shown on the ticket (same price resolution as charge).
     */
    private function cartItems(string $orderCode)
    {
        return Product::selectRaw('
                            IF(discounts.product_id IS NOT NULL,
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
                            carts.orderCode,
                            carts.notes
                        ')
            ->leftJoin('carts', 'products.id', '=', 'carts.product_id')
            ->leftJoin('discounts', 'products.id', '=', 'discounts.product_id')
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->where('carts.user_id', auth()->id())
            ->where('carts.orderCode', $orderCode)
            ->get();
    }

    /**
     * item_count + PKR total per open draft for the Active Tickets strip.
     */
    private function draftSummaries($drafts)
    {
        $codes = $drafts->pluck('order_code');

        if ($codes->isEmpty()) {
            return collect();
        }

        $rows = DB::table('carts')
            ->join('products', 'carts.product_id', '=', 'products.id')
            ->leftJoin('discounts', function ($join) {
                $join->on('products.id', '=', 'discounts.product_id')
                    ->whereDate('discounts.start_date', '<=', now())
                    ->whereDate('discounts.end_date', '>=', now());
            })
            ->leftJoin('product_sizes', function ($join) {
                $join->on('products.id', '=', 'product_sizes.product_id')
                    ->on('carts.size', '=', 'product_sizes.size');
            })
            ->whereIn('carts.orderCode', $codes)
            ->selectRaw('
                carts.orderCode,
                SUM(carts.qty) as item_count,
                SUM(
                    IF(discounts.product_id IS NOT NULL,
                        product_sizes.price - (product_sizes.price * discounts.discount_percentage / 100),
                        product_sizes.price
                    ) * carts.qty
                ) as subtotal
            ')
            ->groupBy('carts.orderCode')
            ->get()
            ->keyBy('orderCode');

        $taxRate = $this->taxRate();

        $summary = collect();
        foreach ($drafts as $draft) {
            $row = $rows->get($draft->order_code);
            $itemCount = (int) ($row->item_count ?? 0);
            $subTotal = (float) ($row->subtotal ?? 0);
            $taxAmount = $this->roundUp($subTotal * ($taxRate / 100));
            $deliveryFee = $this->deliveryFeeFor($draft->delivery_location_id);
            $total = $this->roundUp($subTotal + $taxAmount + $deliveryFee);

            $summary[$draft->order_code] = [
                'item_count' => $itemCount,
                'total'      => $total,
            ];
        }

        return $summary;
    }

    /**
     * Running bills: all live waiter sessions of this cashier's branch.
     * Sessions awaiting a bill are payable; open ones are displayed for tracking.
     */
    public function sessions()
    {
        $sessions = CustomerSession::with(['waiter', 'branch'])
            ->whereIn('branch_id', $this->allowedSessionBranchIds())
            ->whereIn('status', [CustomerSession::STATUS_OPEN, CustomerSession::STATUS_BILL_REQUESTED])
            ->orderByDesc('opened_at')
            ->get();

        $summary = $sessions->mapWithKeys(function ($session) {
            $subTotal = $session->subtotal();
            $taxAmount = $this->roundUp($subTotal * ($this->taxRate() / 100));
            $total = $this->roundUp($subTotal + $taxAmount);

            return [
                $session->id => [
                    'orders'   => $session->ordersCount(),
                    'subtotal' => $subTotal,
                    'tax'      => $taxAmount,
                    'total'    => $total,
                ],
            ];
        });

        return view('cashier.sessions.index', [
            'sessions' => $sessions,
            'summary'  => $summary,
            'isAdmin'  => auth()->user()->role === 'admin',
        ]);
    }

    /**
     * Session detail: grouped ticket lines + settle form (only when bill requested).
     */
    public function sessionDetails($sessionId)
    {
        $session = CustomerSession::whereIn('branch_id', $this->allowedSessionBranchIds())
            ->where('id', $sessionId)
            ->firstOrFail();

        $orders = Order::with(['product'])
            ->where('session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        $groupedOrders = $orders->groupBy('order_code');

        $subTotal = $session->subtotal();
        $taxAmount = $this->roundUp($subTotal * ($this->taxRate() / 100));
        $total = $this->roundUp($subTotal + $taxAmount);

        return view('cashier.sessions.show', [
            'session'       => $session,
            'groupedOrders' => $groupedOrders,
            'subTotal'      => $subTotal,
            'taxRate'       => $this->taxRate(),
            'taxAmount'     => $taxAmount,
            'total'         => $total,
            'settlement'    => $session->settlementRecord(),
        ]);
    }

    /**
     * Settle a bill-requested session of this branch: create ONE PaymentRecord
     * (order_code = SET-<session_code>), close the session and mark all of its
     * non-rejected orders as completed.
     */
    public function settleSession(Request $request, $sessionId)
    {
        $validated = $request->validate([
            'paymentMethod' => ['required', 'string', 'in:cash,card,mobile'],
            'cashReceived'  => ['required', 'numeric', 'min:0'],
        ]);

        $branchId = auth()->user()->branch_id;

        $pending = CustomerSession::whereIn('branch_id', $this->allowedSessionBranchIds())
            ->where('id', $sessionId)
            ->first();

        if (! $pending) {
            abort(404);
        }

        $outcome = DB::transaction(function () use ($validated, $sessionId, $branchId) {
            $session = CustomerSession::whereIn('branch_id', $this->allowedSessionBranchIds())
                ->where('id', $sessionId)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                return 'missing';
            }
            if (! $session->isBillRequested()) {
                return $session->isClosed() ? 'closed' : 'open';
            }
            if ($session->ordersCount() === 0) {
                return 'empty';
            }

            $method = strtolower($validated['paymentMethod']);
            $subTotal = $session->subtotal();
            $taxAmount = $this->roundUp($subTotal * ($this->taxRate() / 100));
            $total = $this->roundUp($subTotal + $taxAmount);

            if ($method === 'cash' && (float) $validated['cashReceived'] < $total) {
                return 'insufficient';
            }

            $paidAmount = $method === 'cash' ? (float) $validated['cashReceived'] : $total;
            $change = max(0.0, $paidAmount - $total);

            PaymentRecord::create([
                'order_code'     => $session->settlementCode(),
                'user_id'        => auth()->id(),
                'net_amount'     => $total,
                'paid_amount'    => round($paidAmount, 2),
                'change_amount'  => round($change, 2),
                'payment_method' => ucfirst($method),
                'status'         => 1,
            ]);

            $session->orders()
                ->where('status', '!=', 3)
                ->update(['status' => 2]);

            $session->status = CustomerSession::STATUS_CLOSED;
            $session->closed_at = now();
            $session->save();

            return 'ok';
        });

        if ($outcome !== 'ok') {
            $messages = [
                'missing'      => 'Session not found.',
                'closed'       => 'This session has already been settled.',
                'open'         => 'This session is still open. The waiter must request the bill first.',
                'empty'        => 'This session has no payable items to settle.',
                'insufficient' => 'Insufficient cash received. Please check the amount.',
            ];

            return redirect()->route('cashier.sessionDetails', $sessionId)->with('alert', [
                'type'    => 'error',
                'message' => $messages[$outcome] ?? 'Could not settle this session.',
            ]);
        }

        return redirect()->route('cashier.sessionDetails', $sessionId)->with('alert', [
            'type'    => 'success',
            'message' => 'Session settled. Bill #' . $pending->settlementCode(),
        ]);
    }

    /**
     * Printable settlement bill for a settled session of this branch.
     */
    public function sessionBill($sessionId)
    {
        $session = CustomerSession::with(['waiter'])
            ->whereIn('branch_id', $this->allowedSessionBranchIds())
            ->where('id', $sessionId)
            ->firstOrFail();

        // A bill can only be printed once the waiter has requested it
        // (or after the session has been settled). Still-open sessions cannot
        // be printed so the bill-requested lifecycle is respected.
        if ($session->isOpen()) {
            return redirect()->route('cashier.sessionDetails', $session->id)->with('alert', [
                'type'    => 'error',
                'message' => 'This bill can only be printed after the waiter has requested it.',
            ]);
        }

        $orders = Order::with(['product'])
            ->where('session_id', $session->id)
            ->where('status', '!=', 3)
            ->orderBy('created_at')
            ->get();

        $groupedOrders = $orders->groupBy('order_code');

        $subTotal = $session->subtotal();
        $taxAmount = $this->roundUp($subTotal * ($this->taxRate() / 100));
        $total = $this->roundUp($subTotal + $taxAmount);

        return view('cashier.sessions.bill', [
            'session'       => $session,
            'groupedOrders' => $groupedOrders,
            'subTotal'      => $subTotal,
            'taxRate'       => $this->taxRate(),
            'taxAmount'     => $taxAmount,
            'total'         => $total,
            'settlement'    => $session->settlementRecord(),
            'branchName'    => auth()->user()->branch?->name,
        ]);
    }
}