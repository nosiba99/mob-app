<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Area;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\Delivery;
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

    // ─────────────────────────────────────────────
    // قسم إنشاء الطلب الذكي (المستودع + المندوب)
    // ─────────────────────────────────────────────

    public function createOrderSmart($user, $cartItems)
    {
        // 1) تحديد المستودع الأقرب
        $warehouse = Warehouse::where('area_id', $user->area_id)->first();

        if (!$warehouse) {
            return [
                'status' => false,
                'message' => 'لا يوجد مستودع يخدم منطقتك حالياً'
            ];
        }

        // 2) التحقق من توفر المنتجات
        $missingProducts = [];

        foreach ($cartItems as $item) {
            $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
                                   ->where('product_id', $item['product_id'])
                                   ->first();

            if (!$stock || $stock->quantity < $item['quantity']) {
                $missingProducts[] = $item;
            }
        }

        // 3) محاولة مستودع آخر
        $deliveryDelay = false;

        if (!empty($missingProducts)) {

            $alternativeWarehouse = null;

            foreach ($missingProducts as $item) {

                $alternativeWarehouse = WarehouseStock::where('product_id', $item['product_id'])
                    ->where('quantity', '>=', $item['quantity'])
                    ->first();

                if (!$alternativeWarehouse) {
                    return [
                        'status' => false,
                        'message' => "المنتج {$item['name']} غير متوفر حالياً في أي مستودع",
                        'order_status' => 'waiting_stock'
                    ];
                }
            }

            // تحويل المستودع
            $warehouse = Warehouse::find($alternativeWarehouse->warehouse_id);
            $deliveryDelay = true;
        }

        // 4) إنشاء الطلب
        $order = Order::create([
            'user_id'      => $user->id,
            'warehouse_id' => $warehouse->id,
            'status'       => 'pending',
            'barcode'      => Str::random(10),
            'delivery_delay' => $deliveryDelay
        ]);

        // 🔥 إشعار المستخدم
        $this->notifyUser($user->id, 'تم إنشاء الطلب', 'طلبك قيد المعالجة الآن');

        // 4.1) خصم المخزون
        foreach ($cartItems as $item) {
            $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
                                   ->where('product_id', $item['product_id'])
                                   ->first();

            if ($stock && $stock->quantity >= $item['quantity']) {
                $stock->decrement('quantity', $item['quantity']);
            }
        }

        // 5) اختيار مندوب ذكي
        $delivery = Delivery::where('area_id', $warehouse->area_id)
                        ->where('is_online', true)
                        ->where('is_available', true)
                        ->orderBy('active_orders', 'asc')
                        ->first();

        if (!$delivery) {
            $order->update(['status' => 'waiting_delivery']);

            return [
                'status' => true,
                'message' => 'تم إنشاء الطلب، سيتم تعيين مندوب فور توفره',
                'data' => $order
            ];
        }

        // 6) تعيين المندوب
        $order->update([
            'delivery_id' => $delivery->id,
            'status'      => 'assigned'
        ]);

        // 7) تحديث حالة المندوب
        $maxOrders = 5;
        $newActiveOrders = $delivery->active_orders + 1;

        $delivery->update([
            'active_orders'    => $newActiveOrders,
            'is_available'     => $newActiveOrders < $maxOrders,
            'last_assigned_at' => now()
        ]);

        return [
            'status' => true,
            'message' => $deliveryDelay
                ? 'تم إنشاء الطلب وتحويله لمستودع آخر (قد يتأخر التوصيل)'
                : 'تم إنشاء الطلب وتعيينه تلقائياً للمندوب',
            'data' => $order
        ];
    }

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
