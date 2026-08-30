<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\CustomerSession;
use App\Models\Order;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        View::composer('*', function ($view) {
            $userId = Auth::id();
            $cartCount = $userId ? Cart::where('user_id', $userId)->count() : 0;
            $view->with('cartCount', $cartCount);
        });

        View::composer('*', function ($view) {
            $userId = Auth::id();
            $orderCount = $userId ? Order::where('user_id', $userId)->count() : 0;
            $view->with('orderCount', $orderCount);
        });

        // Bill Request badge for the cashier/admin sidebar. Branch-scoped exactly
        // like CashierController::allowedSessionBranchIds(): cashier = own branch
        // only, admin = every branch. Same-branch cashiers always see requests.
        View::composer('admin.layouts.master', function ($view) {
            $user = Auth::user();
            $count = 0;

            if ($user && in_array($user->role, ['cashier', 'admin'], true)) {
                $query = CustomerSession::where('status', CustomerSession::STATUS_BILL_REQUESTED);
                $count = $user->role === 'admin'
                    ? $query->count()
                    : $query->where('branch_id', $user->branch_id)->count();
            }

            $view->with('cashierPendingBills', $count);
        });

        Paginator::useBootstrap();
    }
}
