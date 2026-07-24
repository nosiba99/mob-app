<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

class DeliveryService
{
    // تسجيل دخول المندوب
    public function login(array $data): ?array
    {
        $delivery = User::where('role', 'delivery')
                        ->where('email', $data['email'])
                        ->first();

        if (!$delivery) {
            throw new \Exception('البريد الإلكتروني غير صحيح.');
        }

        if (!Hash::check($data['password'], $delivery->password)) {
            throw new \Exception('كلمة المرور غير صحيحة.');
        }

        $token = $delivery->createToken('delivery_token')->plainTextToken;

        return [
            'delivery' => $delivery,
            'token'    => $token,
        ];
    }

    // تحديث حالة المندوب (online/offline)
    public function updateStatus(User $delivery, string $status): User
    {
        $delivery->update(['status' => $status]);
        return $delivery;
    }

    // تحديث موقع المندوب
    public function updateLocation(User $delivery, array $data): User
    {
        if (!isset($data['lat']) || !isset($data['lng'])) {
            throw new \Exception('إحداثيات الموقع غير مكتملة.');
        }

        $delivery->update([
            'lat' => $data['lat'],
            'lng' => $data['lng'],
        ]);

        return $delivery;
    }

    // قبول الطلب
    public function acceptOrder(User $delivery, Order $order): Order
    {
        if ($order->status !== 'pending') {
            throw new \Exception('لا يمكن قبول هذا الطلب.');
        }

        if ($delivery->active_orders >= 1) {
            throw new \Exception('لا يمكنك قبول أكثر من طلب في نفس الوقت.');
        }

        $order->update([
            'delivery_id' => $delivery->id,
            'status'      => 'accepted',
        ]);

        $delivery->increment('active_orders');

        return $order->fresh(['user', 'items.product']);
    }

    // استلام الطلب من المتجر
    public function pickupOrder(User $delivery, Order $order): Order
    {
        if ($order->delivery_id !== $delivery->id) {
            throw new \Exception('هذا الطلب ليس مخصصًا لك.');
        }

        $order->update(['status' => 'picked_up']);

        return $order->fresh(['user', 'items.product']);
    }

    // إنهاء الطلب (تم التوصيل)
    public function completeOrder(User $delivery, Order $order): Order
    {
        if ($order->delivery_id !== $delivery->id) {
            throw new \Exception('هذا الطلب ليس مخصصًا لك.');
        }

        $order->update(['status' => 'delivered']);

        $delivery->decrement('active_orders');

        return $order->fresh(['user', 'items.product']);
    }

    // عرض طلبات المندوب
    public function myOrders(User $delivery)
    {
        return Order::where('delivery_id', $delivery->id)
            ->with(['items.product', 'user'])
            ->latest()
            ->get();
    }

    // تبديل حالة التوفر
    public function toggleAvailability(User $delivery): User
    {
        $delivery->is_available = !$delivery->is_available;
        $delivery->save();

        return $delivery;
    }
}
