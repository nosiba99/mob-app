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

        // نبحث عن المستخدم حسب رقم الهاتف
        $delivery = User::where('phone', $request->phone)->first();

        if (!$delivery) {
            return response()->json(['message' => 'رقم الهاتف غير موجود'], 404);
        }

        // 🔥 التحقق من أن الحساب هو حساب مندوب
        if ($delivery->role !== 'delivery') {
            return response()->json(['message' => 'هذا الحساب ليس حساب مندوب'], 403);
        }

        // التحقق من كلمة المرور
        if (!Hash::check($request->password, $delivery->password)) {
            return response()->json(['message' => 'كلمة المرور غير صحيحة'], 401);
        }

        // التحقق من أن الحساب مفعل
        if (!$delivery->is_active) {
            return response()->json(['message' => 'حساب المندوب غير مفعل'], 403);
        }

        // إنشاء توكن
        $token = $delivery->createToken('delivery_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'delivery' => $delivery,
            'token' => $token
        ]);
    }
}
