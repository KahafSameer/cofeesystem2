<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // dd($request->all());
        if(!empty(Auth::user())){//before login


            if(Auth::user()->role == 'admin' || Auth::user()->role == 'chef' || Auth::user()->role == 'cashier'){
                if($request->route()->getName() == 'userRegister' || $request->route()->getName() == 'userLogin'){
                    abort(404);
                }

                return $next($request);
            }

            return back();
        }
            return $next($request);


    }
}
