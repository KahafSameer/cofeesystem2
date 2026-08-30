<?php

namespace App\Http\Controllers\Chef;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\KitchenTicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChefController extends Controller
{
    /*
     * Status values reused from the existing `orders.status` integer field.
     * 1 = New (pending, submitted to kitchen)
     * 2 = Completed / served (waiter - cashier)
     * 3 = Rejected / cancelled
     * 4 = Preparing (chef started)  [kitchen]
     * 5 = Ready (chef finished)     [kitchen]
     */
    public const STATUS_NEW      = 1;
    public const STATUS_PREPARING = 4;
    public const STATUS_READY    = 5;

    public function __construct()
    {
        //Share the new-orders count for the navbar badge across the chef layout.
        if (Auth::check() && Auth::user()->role === 'chef') {
            \Illuminate\Support\Facades\View::share('newOrdersCount', Order::where('branch_id', Auth::user()->branch_id)
                ->where('status', self::STATUS_NEW)
                ->select('order_code')
                ->distinct()
                ->count());
        }
    }

    private function chef()
    {
        return Auth::user();
    }

    //Chef counts are always scoped to the chef's branch.
    private function branchOrders()
    {
        return Order::where('branch_id', $this->chef()->branch_id);
    }

    //Verify an order group belongs to this chef's branch (server-side ownership).
    private function verifiableOrder($orderCode)
    {
        $order = Order::where('order_code', $orderCode)
            ->where('branch_id', $this->chef()->branch_id)
            ->first();

        if (! $order) {
            abort(404);
        }

        return $order;
    }

    //Chef Dashboard
    public function dashboard()
    {
        $chef   = $this->chef();
        $branch = $chef->branch;

        $newOrders = $this->branchOrders()->where('status', self::STATUS_NEW)
            ->select('order_code')->distinct()->count();

        $preparing = $this->branchOrders()->where('status', self::STATUS_PREPARING)
            ->select('order_code')->distinct()->count();

        $ready = $this->branchOrders()->where('status', self::STATUS_READY)
            ->select('order_code')->distinct()->count();

        $today = $this->branchOrders()->whereDate('created_at', Carbon::today())
            ->select('order_code')->distinct()->count();

        return view('chef.dashboard', [
            'chef'      => $chef,
            'branch'    => $branch,
            'newOrders' => $newOrders,
            'preparing' => $preparing,
            'ready'     => $ready,
            'today'     => $today,
        ]);
    }

    //New (status 1) orders for this branch
    public function newOrders()
    {
        $orders = $this->branchOrders()->with(['product', 'branch', 'waiter', 'customerSession'])
            ->where('status', self::STATUS_NEW)
            ->orderByDesc('created_at')
            ->get();

        return view('chef.orders.new', ['groupedOrders' => $orders->groupBy('order_code')]);
    }

    //Preparing (status 4) orders for this branch
    public function preparing()
    {
        $orders = $this->branchOrders()->with(['product', 'branch', 'waiter', 'customerSession'])
            ->where('status', self::STATUS_PREPARING)
            ->orderByDesc('created_at')
            ->get();

        return view('chef.orders.preparing', ['groupedOrders' => $orders->groupBy('order_code')]);
    }

    //Ready (status 5) orders for this branch
    public function ready()
    {
        $orders = $this->branchOrders()->with(['product', 'branch', 'waiter', 'customerSession'])
            ->where('status', self::STATUS_READY)
            ->orderByDesc('created_at')
            ->get();

        return view('chef.orders.ready', ['groupedOrders' => $orders->groupBy('order_code')]);
    }

    //Kitchen history (processed orders for this branch, excluding brand-new)
    public function history()
    {
        $orders = $this->branchOrders()->with(['product', 'branch', 'waiter', 'customerSession'])
            ->whereIn('status', [self::STATUS_PREPARING, self::STATUS_READY, 2, 3])
            ->orderByDesc('created_at')
            ->get();

        return view('chef.orders.history', ['groupedOrders' => $orders->groupBy('order_code')]);
    }

    //Order detail (whole order_code group)
    public function orderDetails($orderCode)
    {
        $order  = $this->verifiableOrder($orderCode);

        $details = Order::with(['product', 'branch', 'waiter', 'customerSession'])
            ->where('order_code', $orderCode)
            ->where('branch_id', $this->chef()->branch_id)
            ->orderBy('created_at')
            ->get();

        return view('chef.orders.show', [
            'order'   => $order,
            'details' => $details,
        ]);
    }

    //Transition NEW -> PREPARING. Only valid from NEW (status 1).
    public function startPreparing($orderCode)
    {
        $order = $this->verifiableOrder($orderCode);

        if ((int) $order->status !== self::STATUS_NEW) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'Only new orders can be moved to preparing.',
            ]);
        }

        Order::where('order_code', $orderCode)->where('branch_id', $this->chef()->branch_id)
            ->update(['status' => self::STATUS_PREPARING]);

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Order #' . $orderCode . ' is now preparing.',
        ]);
    }

    //Transition PREPARING -> READY. Only valid from PREPARING (status 4).
    public function markReady($orderCode)
    {
        $order = $this->verifiableOrder($orderCode);

        if ((int) $order->status !== self::STATUS_PREPARING) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'Only preparing orders can be marked ready.',
            ]);
        }

        Order::where('order_code', $orderCode)->where('branch_id', $this->chef()->branch_id)
            ->update(['status' => self::STATUS_READY]);

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Order #' . $orderCode . ' is ready.',
        ]);
    }

    //Render the printer-friendly KOT. Used by both automatic print (after order
    //submission, opened from the waiter's browser) and manual reprint (chef).
    //Never creates a duplicate order. Must NOT show payment info.
    public function printKot($orderCode)
    {
        $order = Order::where('order_code', $orderCode)->first();

        if (! $order) {
            abort(404);
        }

        //Authorization: owning waiter OR chef of the order's branch.
        $user = $this->chef();

        $isOwningWaiter = $user->role === 'waiter' && (int) $order->waiter_id === (int) $user->id;
        $isBranchChef   = $user->role === 'chef' && (int) $order->branch_id === (int) $user->branch_id;

        if (! ($isOwningWaiter || $isBranchChef)) {
            abort(403);
        }

        $data = app(KitchenTicketService::class)->ticketData($orderCode);

        if (! $data) {
            abort(404);
        }

        return view('chef.kitchen-ticket', ['kot' => $data]);
    }
}
