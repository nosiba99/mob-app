<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    // ─── جلب كل الطلبات (للأدمن) ───────────────────────────────
   public function getAllOrders()
{
    return Order::select('id', 'user_id', 'total_price as total', 'status', 'created_at')


        ->with(['user:id,first_name,last_name'])
        ->orderBy('id', 'desc')
        ->paginate(20);
}


    // ─── جلب طلب واحد بالتفاصيل ───────────────────────────────
    public function getOrderById($id)
    {
        return Order::with([
            'user',
            'items',
            'items.product',
            'items.variant'
        ])->find($id);
    }

    // ─── تحديث حالة الطلب ─────────────────────────────────────
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


public function searchOrders($orderId = null, $keyword = null)
{
    $query = Order::with('user');

    // بحث برقم الطلب
    if ($orderId) {
        $query->where('id', $orderId);
    }

    // بحث باسم المستخدم أو الإيميل أو الهاتف
    if ($keyword) {
        $query->whereHas('user', function ($q) use ($keyword) {
            $q->where('first_name', 'like', "%{$keyword}%")
              ->orWhere('last_name', 'like', "%{$keyword}%")
              ->orWhere('email', 'like', "%{$keyword}%")
              ->orWhere('phone', 'like', "%{$keyword}%");
        });
    }

    return $query->paginate(20);
}




    // ─── إنشاء طلب من السلة (للمستخدم) ─────────────────────────
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
                'total'   => $cartItems->sum(fn($item) => $item->quantity * $item->price),
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

                // تخفيض المخزون
                $item->variantSize->decrement('stock', $item->quantity);
            }

            // تفريغ السلة
            $user->cart()->delete();

            return $order->load('items');
        });
    }

    // ─── جلب طلبات مستخدم معيّن ───────────────────────────────
    public function getUserOrders($user)
    {
        return Order::where('user_id', $user->id)
            ->with('items.product')
            ->orderBy('id', 'desc')
            ->get();
    }

    // ─── جلب طلبات المندوب ─────────────────────────────────────
    public function getDeliveryOrders($delivery)
    {
        return Order::where('delivery_id', $delivery->id)
            ->with('items.product', 'user')
            ->orderBy('id', 'desc')
            ->get();
    }
}
