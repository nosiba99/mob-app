<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Services\DeliveryService;
use Illuminate\Http\Request;

class DeliveryAuthController extends Controller
{
    public function __construct(private DeliveryService $deliveryService) {}

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

    // تسجيل دخول المندوب
    public function login(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        // نبحث عن المندوب حسب رقم الهاتف
        $delivery = \App\Models\User::where('phone', $request->phone)->first();

        if (!$delivery) {
            return $this->error('رقم الهاتف غير موجود', 404);
        }

        // التحقق من أن الحساب هو حساب مندوب
        if ($delivery->role !== 'delivery') {
            return $this->error('هذا الحساب ليس حساب مندوب', 403);
        }

        // التحقق من كلمة المرور عبر الـ Service
        $result = $this->deliveryService->login([
            'email'    => $delivery->email,   // نستخدم الإيميل لأن الـ Service يعتمد عليه
            'password' => $request->password,
        ]);

        if (!$result) {
            return $this->error('كلمة المرور غير صحيحة', 401);
        }

        // التحقق من أن الحساب مفعل
        if (!$delivery->is_active) {
            return $this->error('حساب المندوب غير مفعل', 403);
        }

        return $this->success('تم تسجيل الدخول بنجاح', [
            'delivery' => $result['delivery'],
            'token'    => $result['token'],
        ]);
    }
}
