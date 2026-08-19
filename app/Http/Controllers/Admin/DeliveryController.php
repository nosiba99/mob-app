<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDeliveryService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\DeliveryShortResource;

class DeliveryController extends Controller
{
    public function __construct(private AdminDeliveryService $deliveryService) {}

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

    // إنشاء مندوب جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|string|max:20|unique:users,phone',
            'email'      => 'nullable|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'area_id'    => 'required|exists:areas,id',
            'address'    => 'required|string',
            'building_number'   => 'nullable|string',
            'floor_number'      => 'nullable|string',
            'apartment_number'  => 'nullable|string',
            'delivery_notes'    => 'nullable|string',
        ]);

        $delivery = $this->deliveryService->create($validated);

        return $this->success(__('Delivery employee created successfully'), $delivery);
    }

    // عرض كل المندوبين
  public function index()
{
    $deliveries = $this->deliveryService->getAll();
    return $this->success(__('Delivery employees retrieved successfully'),
        DeliveryShortResource::collection($deliveries)
    );
}

    // عرض مندوب واحد
    public function show($id)
    {
        $delivery = $this->deliveryService->getById($id);

        if (!$delivery) {
            return $this->error(__('Delivery employee not found'), 404);
        }

        return $this->success(__('Delivery employee retrieved successfully'), $delivery);
    }

  

public function update(Request $request, $id)
{
    $delivery = User::where('role', 'delivery')->find($id);

    if (!$delivery) {
        return $this->error(__('Delivery employee not found'), 404);
    }

    $request->validate([
        'first_name' => 'sometimes|string',
        'last_name'  => 'sometimes|string',

        'phone'      => [
            'sometimes',
            'string',
            Rule::unique('users', 'phone')->ignore($delivery->id),
        ],

        'email'      => [
            'sometimes',
            'email',
            Rule::unique('users', 'email')->ignore($delivery->id),
        ],

        'area_id'    => 'sometimes|exists:areas,id',
        'is_available' => 'sometimes|boolean'
    ], [
        'phone.unique' => 'رقم الهاتف مستخدم مسبقًا، يرجى إدخال رقم آخر.',
        'email.unique' => 'هذا البريد الإلكتروني مستخدم مسبقًا.',
    ]);

    $updated = $this->deliveryService->update($delivery, $request->all());

    return $this->success(__('Delivery employee updated successfully'), $updated);
}


    // حذف مندوب
    public function destroy($id)
    {
        $delivery = User::where('role', 'delivery')->find($id);

        if (!$delivery) {
            return $this->error(__('Delivery employee not found'), 404);
        }

        $deleted = $this->deliveryService->delete($delivery);

        if (!$deleted) {
            return $this->error(__('Cannot delete delivery employee because they have active orders'), 400);
        }

        return $this->success(__('Delivery employee deleted successfully'));
    }

    // جلب مندوبين حسب المنطقة
    public function byArea($areaId)
    {
        $deliveries = $this->deliveryService->getByArea($areaId);

        return $this->success(__('Delivery employees retrieved successfully'), $deliveries);
    }
}
