<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ChefMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! empty(Auth::user())) {
            if (Auth::user()->role === 'chef') {
                return $next($request);
            }
            return back();
        }

        return $next($request);
    }
}
