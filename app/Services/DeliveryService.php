<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\StoreAccount;
use App\Events\DeliveryAssigned;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeliveryService
{
    public function login(array $data): ?array
    {
        $delivery = User::where('role', 'delivery')
                        ->where('email', $data['email'])
                        ->first();

        if (!$delivery) {
            throw new Exception('البريد الإلكتروني غير صحيح.');
        }

        if (!Hash::check($data['password'], $delivery->password)) {
            throw new Exception('كلمة المرور غير صحيحة.');
        }

        $token = $delivery->createToken('delivery_token')->plainTextToken;

        return [
            'delivery' => $delivery,
            'token'    => $token,
        ];
    }

    public function checkCoverage(User $delivery, int $areaId): bool
    {
        if (!$delivery->warehouse_id) {
            throw new Exception('هذا المندوب غير مرتبط بأي مستودع.');
        }

        $covers = $delivery->warehouse
            ->areas()
            ->where('area_id', $areaId)
            ->exists();

        if (!$covers) {
            throw new Exception('هذا المندوب لا يغطي هذه المنطقة.');
        }

        return true;
    }

    public function updateStatus(User $delivery, string $status): User
    {
        $delivery->update(['status' => $status]);
        return $delivery;
    }

    public function updateLocation(User $delivery, array $data): User
    {
        if (!isset($data['lat']) || !isset($data['lng'])) {
            throw new Exception('إحداثيات الموقع غير مكتملة.');
        }

        $delivery->update([
            'lat' => $data['lat'],
            'lng' => $data['lng'],
        ]);

        return $delivery;
    }

   public function acceptOrder(User $delivery, Order $order): Order
{
    if ($order->status !== Order::STATUS_ASSIGNED) {
        throw new Exception('لا يمكن قبول هذا الطلب.');
    }

    // حذف شرط الحد الأقصى لو بدك
    // أو خليه حسب رغبتك
    // if ($delivery->active_orders >= 5) {
    //     throw new Exception('وصلت للحد الأقصى للطلبات.');
    // }

    // ⭐ توليد الباركود هنا
    if (!$order->barcode) {
        $order->barcode = strtoupper(Str::random(10));
    }

    // تحديث حالة الطلب
    $order->status = Order::STATUS_ACCEPTED;
    $order->save();

    // تحديث حالة المندوب
    $delivery->increment('active_orders');
    $delivery->is_available = false;
    $delivery->save();

    return $order->fresh(['user', 'items.product']);
}


    public function rejectOrder(User $delivery, Order $order): Order
    {
        if ($order->delivery_id !== $delivery->id) {
            throw new Exception('هذا الطلب ليس مخصصًا لك.');
        }

        if ($order->status !== Order::STATUS_ASSIGNED) {
            throw new Exception('لا يمكن رفض هذا الطلب بعد قبوله.');
        }

        $order->update([
            'status' => Order::STATUS_REJECTED,
            'delivery_id' => null
        ]);

        return $order->fresh(['user', 'items.product']);
    }

    public function markOnTheWay(User $delivery, Order $order): Order
    {
        if ($order->delivery_id !== $delivery->id) {
            throw new Exception('هذا الطلب ليس مخصصًا لك.');
        }

        if ($order->status !== Order::STATUS_ACCEPTED) {
            throw new Exception('لا يمكن تغيير حالة هذا الطلب.');
        }

        $order->update(['status' => Order::STATUS_ON_THE_WAY]);

        return $order->fresh(['user', 'items.product']);
    }

    public function markDelivered(User $delivery, Order $order): Order
    {
        if ($order->delivery_id !== $delivery->id) {
            throw new Exception('هذا الطلب ليس مخصصًا لك.');
        }

        if ($order->status !== Order::STATUS_ON_THE_WAY) {
            throw new Exception('لا يمكن إنهاء هذا الطلب.');
        }

        $order->update([
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => now()
        ]);

        // ⭐ تحويل قيمة فاتورة الطلب لحساب المتجر — فقط بعد تأكيد التسليم بالباركود
        StoreAccount::credit(
            $order->total_price,
            $order->id,
            'تحصيل فاتورة الطلب رقم ' . $order->id
        );

        $delivery->active_orders -= 1;

        if ($delivery->active_orders <= 0) {
            $delivery->is_available = true;
        }

        $delivery->save();

        return $order->fresh(['user', 'items.product']);
    }

    public function myOrders(User $delivery)
    {
        return Order::where('delivery_id', $delivery->id)
            ->with(['items.product', 'user'])
            ->latest()
            ->get();
    }

    public function toggleAvailability(User $delivery): User
    {
        $delivery->is_available = !$delivery->is_available;
        $delivery->save();

        return $delivery;
    }

    public function assignDeliveryToOrder(Order $order, ?int $excludeDeliveryId = null)
    {
        $delivery = User::where('role', 'delivery')
            ->where('warehouse_id', $order->warehouse_id)
            ->where('area_id', $order->area_id)
            ->where('is_available', true)
            ->where('is_banned', false)
            ->when($excludeDeliveryId, fn ($q) => $q->where('id', '!=', $excludeDeliveryId))
            ->orderBy('active_orders', 'asc')
            ->first();

        if (!$delivery) {
            throw new Exception('لا يوجد مندوب متاح حالياً لهذه المنطقة والمستودع.');
        }

        $order->update([
            'delivery_id' => $delivery->id,
            'status'      => Order::STATUS_ASSIGNED,
        ]);

        event(new DeliveryAssigned($order, $delivery));

        $delivery->is_available = false;
        $delivery->active_orders += 1;
        $delivery->save();

        return $order->fresh(['delivery', 'items.product']);
    }
}
