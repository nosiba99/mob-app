<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Area;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Models\Notification;   // 🔥 مهم جداً
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    // ─────────────────────────────────────────────
    // قسم الطلبات العامة (للأدمن)
    // ─────────────────────────────────────────────

    public function getAllOrders()
    {
        return Order::select('id', 'user_id', 'total_price as total', 'status', 'created_at')
            ->with(['user:id,first_name,last_name'])
            ->orderBy('id', 'desc')
            ->paginate(20);
    }

    public function getOrderById($id)
    {
        return Order::with([
            'user',
            'items',
            'items.product',
            'items.variant'
        ])->find($id);
    }

    public function updateOrderStatus($id, $status)
    {
        $order = Order::find($id);

        if (!$order) {
            return false;
        }

        $order->update(['status' => $status]);
        return true;
    }

    public function getOrdersByStatus($status)
    {
        return Order::select('id', 'user_id', 'total_price as total', 'status', 'created_at')
            ->with(['user:id,first_name,last_name'])
            ->where('status', $status)
            ->orderBy('id', 'desc')
            ->paginate(20);
    }


    // ─────────────────────────────────────────────
    // قسم طلبات المستخدم
    // ─────────────────────────────────────────────

    public function createOrderFromCart($user)
    {
        return DB::transaction(function () use ($user) {

            $cartItems = $user->cart()->with('variantSize')->get();

            if ($cartItems->isEmpty()) {
                return null;
            }

            $order = Order::create([
                'user_id' => $user->id,
                'status'  => 'pending',
                'total_price' => $cartItems->sum(fn($item) => $item->quantity * $item->price),
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'product_variant_id'=> $item->variant_id,
                    'size_id'           => $item->size_id,
                    'quantity'          => $item->quantity,
                    'price'             => $item->price,
                ]);

                $item->variantSize->decrement('stock', $item->quantity);
            }

            $user->cart()->delete();

            return $order->load('items');
        });
    }

    public function getUserOrders($user)
    {
        return Order::where('user_id', $user->id)
            ->with('items.product')
            ->orderBy('id', 'desc')
            ->get();
    }

    // ─────────────────────────────────────────────
    // قسم طلبات المندوب
    // ─────────────────────────────────────────────

    public function getDeliveryOrders($delivery)
    {
        return Order::where('delivery_id', $delivery->id)
            ->with('items.product', 'user')
            ->orderBy('id', 'desc')
            ->get();
    }

    // ─────────────────────────────────────────────
    // قسم الباركود
    // ─────────────────────────────────────────────

    public function generateBarcode(Order $order)
    {
        $order->barcode = 'ORD-' . strtoupper(Str::random(10));
        $order->save();
    }

    // ملاحظة: تم حذف createOrderSmart() القديمة لأنها كانت تستخدم
    // عمود warehouses.area_id (محذوف من قاعدة البيانات) وموديل Delivery
    // وموديل WarehouseStock غير الموجودين أصلاً بالمشروع.
    // منطق إنشاء الطلب الفعلي والصحيح موجود الآن بـ
    // OrderController::checkout() و OrderController::assignDeliveryToOrder()
    // واللي بيستخدما ProductWarehouse (المخزون الحقيقي حسب كل مستودع).

    // ─────────────────────────────────────────────
    // نظام تتبع حالة الطلب
    // ─────────────────────────────────────────────

    public function updateOrderStatusFlow(Order $order, $status)
    {
        $validStatuses = [
            'pending',
            'assigned',
            'on_the_way',
            'delivered',
            'canceled',
            'waiting_delivery',
            'waiting_stock',
            'returned'
        ];

        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $order->update(['status' => $status]);

        return true;
    }

    // ─────────────────────────────────────────────
    // الإشعارات
    // ─────────────────────────────────────────────

    public function notifyUser($userId, $title, $message)
    {
        Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message
        ]);
    }

    public function notifyDelivery($deliveryId, $title, $message)
    {
        Notification::create([
            'delivery_id' => $deliveryId,
            'title'       => $title,
            'message'     => $message
        ]);
    }


}
