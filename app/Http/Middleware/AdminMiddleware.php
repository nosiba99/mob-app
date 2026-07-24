<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بالدخول (Admin Only)'
            ], 403);
        }

        return $next($request);
    }
}
