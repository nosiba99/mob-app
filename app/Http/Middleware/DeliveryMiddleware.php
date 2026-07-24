<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DeliveryMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'delivery') {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بالدخول (Delivery Only)'
            ], 403);
        }

        return $next($request);
    }
}
