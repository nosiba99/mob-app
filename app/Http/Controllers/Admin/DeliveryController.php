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
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'area_id' => 'required|exists:areas,id',
            'address' => 'required|string',
            'building_number' => 'nullable|string',
            'floor_number' => 'nullable|string',
            'apartment_number' => 'nullable|string',
            'delivery_notes' => 'nullable|string',
        ]);

        $delivery = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'delivery', // مهم جداً
            'area_id' => $validated['area_id'],
            'address' => $validated['address'],
            'building_number' => $validated['building_number'] ?? null,
            'floor_number' => $validated['floor_number'] ?? null,
            'apartment_number' => $validated['apartment_number'] ?? null,
            'delivery_notes' => $validated['delivery_notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Delivery employee created successfully',
            'data' => $delivery
        ], 201);
    }
}

