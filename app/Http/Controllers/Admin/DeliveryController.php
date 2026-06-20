<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeliveryController extends Controller
{
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

        $delivery = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'phone'      => $validated['phone'],
            'email'      => $validated['email'] ?? null,
            'password'   => Hash::make($validated['password']),
            'role'       => 'delivery',
            'area_id'    => $validated['area_id'],
            'address'    => $validated['address'],
            'building_number'  => $validated['building_number'] ?? null,
            'floor_number'     => $validated['floor_number'] ?? null,
            'apartment_number' => $validated['apartment_number'] ?? null,
            'delivery_notes'   => $validated['delivery_notes'] ?? null,

            // مهم جدًا
            'is_active'     => true,
            'is_available'  => true,
            'active_orders' => 0,
        ]);

        return response()->json([
            'message' => 'Delivery employee created successfully',
            'data' => $delivery
        ], 201);
    }

    public function index()
{
    $deliveries = \App\Models\User::where('role', 'delivery')
        ->with('area') // إذا عندك علاقة area() في الموديل
        ->get();

    return response()->json([
        'message' => 'Delivery employees retrieved successfully',
        'data' => $deliveries
    ], 200);
}
public function show($id)
{
    $delivery = \App\Models\User::where('role', 'delivery')
        ->with('area')
        ->find($id);

    if (!$delivery) {
        return response()->json([
            'message' => 'Delivery employee not found'
        ], 404);
    }

    return response()->json([
        'message' => 'Delivery employee retrieved successfully',
        'data' => $delivery
    ], 200);
}

public function update(Request $request, $id)
{
    $delivery = \App\Models\User::where('role', 'delivery')->find($id);

    if (!$delivery) {
        return response()->json([
            'message' => 'Delivery employee not found'
        ], 404);
    }

    $request->validate([
        'first_name' => 'sometimes|string',
        'last_name'  => 'sometimes|string',
        'phone'      => 'sometimes|string',
        'email'      => 'sometimes|email|unique:users,email,' . $id,
        'area_id'    => 'sometimes|exists:areas,id',
        'is_available' => 'sometimes|boolean'
    ]);

    $delivery->update($request->all());

    return response()->json([
        'message' => 'Delivery employee updated successfully',
        'data' => $delivery
    ], 200);
}
public function destroy($id)
{
    $delivery = \App\Models\User::where('role', 'delivery')->find($id);

    if (!$delivery) {
        return response()->json([
            'message' => 'Delivery employee not found'
        ], 404);
    }

    // منع الحذف إذا عنده طلبات نشطة
    $hasActiveOrders = \App\Models\Order::where('delivery_id', $id)
        ->whereIn('status', ['pending', 'accepted'])
        ->exists();

    if ($hasActiveOrders) {
        return response()->json([
            'message' => 'Cannot delete delivery employee because they have active orders'
        ], 400);
    }

    $delivery->delete();

    return response()->json([
        'message' => 'Delivery employee deleted successfully'
    ], 200);
}
public function byArea($areaId)
{
    $deliveries = \App\Models\User::where('role', 'delivery')
        ->where('area_id', $areaId)
        ->with('area')
        ->get();

    return response()->json([
        'message' => 'Delivery employees retrieved successfully',
        'data' => $deliveries
    ], 200);
}

}
