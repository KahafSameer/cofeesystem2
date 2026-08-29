<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CashierMiddleware
{
    /**
     * Dedicated Cashier POS access. The admin role is also allowed so an
     * administrator can operate the till; every other role is rejected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array($user->role, ['cashier', 'admin'], true)) {
            return $next($request);
        }

        abort(403);
    }
}