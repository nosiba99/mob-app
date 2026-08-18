<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Area;
use Illuminate\Support\Facades\Hash;

class AdminDeliveryService
{
    // إنشاء مندوب جديد
    public function create(array $data): User
    {
        // لو ما تحدد warehouse_id يدوياً، منوّرثو من مستودع منطقته
        $warehouseId = $data['warehouse_id']
            ?? optional(Area::find($data['area_id']))->warehouse_id;

        return User::create([
            'first_name'       => $data['first_name'],
            'last_name'        => $data['last_name'],
            'phone'            => $data['phone'],
            'email'            => $data['email'] ?? null,
            'password'         => Hash::make($data['password']),
            'role'             => 'delivery',
            'area_id'          => $data['area_id'],
            'warehouse_id'     => $warehouseId,
            'address'          => $data['address'],
            'building_number'  => $data['building_number'] ?? null,
            'floor_number'     => $data['floor_number'] ?? null,
            'apartment_number' => $data['apartment_number'] ?? null,
            'delivery_notes'   => $data['delivery_notes'] ?? null,
            'is_active'        => true,
            'is_available'     => true,
            'active_orders'    => 0,
        ]);
    }

    // جلب كل المندوبين
    public function getAll()
    {
        return User::where('role', 'delivery')
            ->with('area')
            ->get();
    }

    // جلب مندوب واحد
    public function getById($id): ?User
    {
        return User::where('role', 'delivery')
            ->with('area')
            ->find($id);
    }

    // تعديل مندوب
    public function update(User $delivery, array $data): User
    {
        // لو تغيّرت المنطقة ومافي warehouse_id محدد صراحة، منوّرثو من مستودع المنطقة الجديدة
        if (isset($data['area_id']) && !isset($data['warehouse_id'])) {
            $data['warehouse_id'] = optional(Area::find($data['area_id']))->warehouse_id;
        }

        $delivery->update($data);
        return $delivery;
    }

    // حذف مندوب
    public function delete(User $delivery): bool
    {
        $hasActiveOrders = Order::where('delivery_id', $delivery->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($hasActiveOrders) {
            return false;
        }

        $delivery->delete();
        return true;
    }

    // جلب مندوبين حسب المنطقة
    public function getByArea($areaId)
    {
        return User::where('role', 'delivery')
            ->where('area_id', $areaId)
            ->with('area')
            ->get();
    }
}
