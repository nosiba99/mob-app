<?php

namespace App\Services;

use App\Models\Area;
use App\Models\User;

class AreaService
{
    // إنشاء منطقة جديدة
    public function create(array $data): Area
    {
        return Area::create([
            'name' => $data['name']
        ]);
    }

    // جلب كل المناطق
    public function getAll()
    {
        return Area::all();
    }

    // حذف منطقة
    public function delete(Area $area): bool
    {
        // تحقق إذا في مندوبين مرتبطين بالمنطقة
        $hasDeliveryUsers = User::where('area_id', $area->id)
                                ->where('role', 'delivery')
                                ->exists();

        if ($hasDeliveryUsers) {
            return false;
        }

        $area->delete();
        return true;
    }

    // جلب منطقة حسب ID
    public function getById($id): ?Area
    {
        return Area::find($id);
    }
}
