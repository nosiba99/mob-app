<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;

class AdminOrderController extends Controller
{
    public function index()
{
    $orders = Order::with('user')
        ->orderBy('id', 'desc')
        ->paginate(20);

    return response()->json([
        'status' => true,
        'message' => 'تم جلب الطلبات بنجاح',
        'data' => $orders
    ]);
}
public function show($id)
{
    $order = Order::with([
        'user',
        'items',
        'items.product',
        'items.variant'
    ])->find($id);

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'الطلب غير موجود'
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'تم جلب بيانات الطلب',
        'data' => $order
    ]);
}

public function updateStatus(Request $request, $id)
{
    $order = Order::find($id);

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'الطلب غير موجود'
        ], 404);
    }

    $request->validate([
        'status' => 'required|in:pending,processing,shipped,completed,canceled'
    ]);

    $order->status = $request->status;
    $order->save();

    return response()->json([
        'status' => true,
        'message' => 'تم تحديث حالة الطلب بنجاح'
    ]);
}


}
