<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Services\DeliveryService;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Events\OrderAccepted;
use App\Events\DeliveryStarted;
use App\Events\DeliveryCompleted;

class DeliveryStatusController extends Controller
{
    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

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

    // ⭐ عرض طلبات المندوب
    public function index(Request $request)
    {
        $delivery = $request->user();

        if ($delivery->role !== 'delivery') {
            return $this->error('Unauthorized', 403);
        }

        $orders = $this->deliveryService->myOrders($delivery);

        return $this->success('تم جلب الطلبات بنجاح', $orders);
    }

    public function show(Request $request, $orderId)
{
    $delivery = $request->user();
    $order = Order::with(['items.product', 'user'])
                  ->find($orderId);

    if (!$order || $order->delivery_id !== $delivery->id) {
        return $this->error('الطلب غير موجود', 404);
    }

    return $this->success('تم جلب تفاصيل الطلب بنجاح', $order);
}


    // ⭐ قبول الطلب
    public function acceptOrder(Request $request, $orderId)
    {
        $delivery = $request->user();
        $order = Order::find($orderId);

        if (!$order || $order->delivery_id !== $delivery->id) {
            return $this->error('الطلب غير موجود', 404);
        }

        try {
            $updatedOrder = $this->deliveryService->acceptOrder($delivery, $order);
           
            event(new AdminOrderAccepted($order));
            event(new OrderAccepted($updatedOrder));
            event(new DeliveryAccepted($updatedOrder));

            return $this->success('تم قبول الطلب بنجاح', $updatedOrder);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ⭐ رفض الطلب قبل القبول
    public function rejectOrder(Request $request, $orderId)
    {
        $delivery = $request->user();
        $order = Order::find($orderId);

        if (!$order || $order->delivery_id !== $delivery->id) {
            return $this->error('الطلب غير موجود', 404);
        }

        try {
            $updatedOrder = $this->deliveryService->rejectOrder($delivery, $order);
          
            event(new AdminOrderRejected($order));
            event(new OrderRejected($updatedOrder));
            event(new DeliveryRejected($updatedOrder));


            return $this->success('تم رفض الطلب بنجاح', $updatedOrder);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ⭐ المندوب في الطريق
    public function markOnTheWay(Request $request, $orderId)
    {
        $delivery = $request->user();
        $order = Order::find($orderId);

        if (!$order || $order->delivery_id !== $delivery->id) {
            return $this->error('الطلب غير موجود', 404);
        }

        try {
            $updatedOrder = $this->deliveryService->markOnTheWay($delivery, $order);

            event(new DeliveryStarted($updatedOrder));
            event(new DeliveryInProgress($updatedOrder));



            return $this->success('المندوب في الطريق الآن', $updatedOrder);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ⭐ التسليم الحقيقي بالباركود
    public function markDeliveredWithBarcode(Request $request, $orderId)
    {
        $delivery = $request->user();

        $request->validate([
            'barcode' => 'required|string',
        ]);

        $order = Order::find($orderId);

        if (!$order || $order->delivery_id !== $delivery->id) {
            return $this->error('الطلب غير موجود', 404);
        }

        if ($order->barcode !== $request->barcode) {
            return $this->error('الباركود غير صحيح', 400);
        }

        try {
            $updatedOrder = $this->deliveryService->markDelivered($delivery, $order);

         event(new DeliveryCompleted($updatedOrder));
         event(new AdminOrderDelivered($updatedOrder));


            return $this->success('تم تسليم الطلب بالباركود بنجاح', $updatedOrder);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

}
