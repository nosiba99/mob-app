<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Services\DeliveryService;
use App\Models\Order;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function __construct(private DeliveryService $deliveryService) {}

    private function success($message, $data = null)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data
        ]);
    }

    private function error($message, $code = 400)
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => null
        ], $code);
    }

    // عرض الطلبات الخاصة بالمندوب
    public function index(Request $request)
    {
        $delivery = $request->user();

        if ($delivery->role !== 'delivery') {
            return $this->error('Unauthorized', 403);
        }

        $orders = $this->deliveryService->myOrders($delivery);

        return $this->success('Orders fetched successfully', $orders);
    }

    // قبول الطلب
    public function acceptOrder(Request $request, $orderId)
    {
        $delivery = $request->user();

        if ($delivery->role !== 'delivery') {
            return $this->error('Unauthorized', 403);
        }

        $order = Order::find($orderId);

        if (!$order || $order->delivery_id !== $delivery->id) {
            return $this->error('Order not found', 404);
        }

        try {
            $updatedOrder = $this->deliveryService->acceptOrder($delivery, $order);

            // تحديث حالة المندوب
            $delivery->active_orders += 1;
            $delivery->is_available = false;
            $delivery->save();

            return $this->success('Order accepted successfully', $updatedOrder);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // رفض الطلب
    public function rejectOrder(Request $request, $orderId)
    {
        $delivery = $request->user();

        if ($delivery->role !== 'delivery') {
            return $this->error('Unauthorized', 403);
        }

        $order = Order::find($orderId);

        if (!$order || $order->delivery_id !== $delivery->id) {
            return $this->error('Order not found', 404);
        }

        // إلغاء تعيين المندوب
        $order->update([
            'delivery_id' => null,
            'status'      => 'pending_assignment'
        ]);

        return $this->success('Order rejected successfully', $order);
    }
}
