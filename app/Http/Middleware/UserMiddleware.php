<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== 'user') {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بالدخول (User Only)'
            ], 403);
        }

        return $next($request);
    }
}
