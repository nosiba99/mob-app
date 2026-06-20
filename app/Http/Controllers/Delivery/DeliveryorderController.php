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

    if ($delivery->role !== 'delivery') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // الطلبات المعيّنة للمندوب فقط
    $orders = Order::where('delivery_id', $delivery->id)
                    ->with(['items', 'user'])
                    ->orderBy('created_at', 'desc')
                    ->get();

    return response()->json([
        'message' => 'Orders fetched successfully',
        'orders' => $orders
    ]);
}
public function acceptOrder(Request $request, $orderId)
{
    $delivery = $request->user();

    // تأكد أنه مندوب
    if ($delivery->role !== 'delivery') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // جلب الطلب
    $order = Order::where('id', $orderId)
                  ->where('delivery_id', $delivery->id) // لازم يكون معيّن له
                  ->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // تحديث حالة الطلب
    $order->status = 'driver_accepted';
    $order->save();

    // زيادة عدد الطلبات عند المندوب
    $delivery->active_orders += 1;
    $delivery->is_available = false; // صار مشغول
    $delivery->save();

    return response()->json([
        'message' => 'Order accepted successfully',
        'order' => $order
    ]);
}


public function rejectOrder(Request $request, $orderId)
{
    $delivery = $request->user();

    if ($delivery->role !== 'delivery') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // جلب الطلب
    $order = Order::where('id', $orderId)
                  ->where('delivery_id', $delivery->id)
                  ->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // إلغاء تعيين المندوب
    $order->delivery_id = null;
    $order->status = 'pending_assignment';
    $order->save();

    // النظام لاحقًا رح يدوّر على مندوب ثاني
    return response()->json([
        'message' => 'Order rejected successfully',
        'order' => $order
    ]);
}

}
