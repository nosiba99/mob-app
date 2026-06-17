<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeliveryAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // نبحث عن المندوب
        $delivery = User::where('phone', $request->phone)
                        ->where('role', 'delivery')
                        ->first();

        if (!$delivery) {
            return response()->json(['message' => 'Delivery account not found'], 404);
        }

        if (!Hash::check($request->password, $delivery->password)) {
            return response()->json(['message' => 'Invalid password'], 401);
        }

        if (!$delivery->is_active) {
            return response()->json(['message' => 'Delivery account is inactive'], 403);
        }

        // إنشاء توكن
        $token = $delivery->createToken('delivery_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'delivery' => $delivery,
            'token' => $token
        ]);
    }
}
