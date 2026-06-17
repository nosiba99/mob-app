<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        $delivery = $request->user();

        // تأكد أنه دليفري
        if ($delivery->role !== 'delivery') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // جلب الطلبات حسب المنطقة
        $orders = Order::where('area_id', $delivery->area_id)
                        ->where('status', 'pending') // حسب نظامك
                        ->with(['items', 'user'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json([
            'message' => 'Orders fetched successfully',
            'orders' => $orders
        ]);
    }
}
