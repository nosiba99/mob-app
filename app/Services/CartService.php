<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class CartService
{
    public function addOrUpdate(array $data)
    {
        $user = Auth::user();

        // نجيب الفاريانت
        $variant = ProductVariant::findOrFail($data['variant_id']);

        // هل الفاريانت موجود بالسلة؟
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('variant_id', $variant->id)
            ->first();

        if ($cartItem) {
            // تحديث الكمية
            $cartItem->quantity += $data['quantity'];
            $cartItem->save();
        } else {
            // إنشاء عنصر جديد بالسلة
            $cartItem = CartItem::create([
                'user_id'    => $user->id,
                'variant_id' => $variant->id,
                'quantity'   => $data['quantity'],
            ]);
        }

        return $cartItem;
    }
}
