<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AddToCartRequest;
use App\Models\ProductVariant;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // ============================
    // إضافة أو تحديث عنصر بالسلة
    // ============================
    public function addOrUpdate(Request $request)
{
    $user = auth()->user();

    $request->validate([
        'variant_id' => 'required|exists:product_variants,id',
        'quantity'   => 'required|integer|min:1',
    ]);

    // جلب الفاريانت
    $variant = ProductVariant::with('product')->findOrFail($request->variant_id);

    // جلب المنتج من علاقة الفاريانت
    $product = $variant->product;

    // جلب العنصر من السلة إذا موجود
    $cartItem = CartItem::where('user_id', $user->id)
        ->where('variant_id', $variant->id)
        ->first();

    if ($cartItem) {
        // تحديث الكمية
        $cartItem->quantity = $request->quantity;
        $cartItem->price    = $variant->price; // السعر الصحيح
        $cartItem->save();
    } else {
        // إنشاء عنصر جديد
        CartItem::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'size_id'    => $variant->size_id,
            'quantity'   => $request->quantity,
            'price'      => $variant->price, // أهم سطر
        ]);
    }

    return response()->json([
        'status'  => true,
        'message' => 'تمت إضافة المنتج للسلة بنجاح',
    ]);
}


    // ============================
    // عرض السلة
    // ============================
    public function index()
    {
        $items = CartItem::with(['variant', 'variant.product', 'size'])
            ->where('user_id', auth()->id())
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'السلة فارغة',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'سلة المستخدم',
            'data' => $items
        ]);
    }

    // ============================
    // تحديث كمية عنصر بالسلة
    // ============================
    public function updateQuantity(Request $request, $cartItemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::where('user_id', auth()->id())
            ->where('id', $cartItemId)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => false,
                'message' => 'العنصر غير موجود داخل السلة',
                'data' => null
            ], 404);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث الكمية',
            'data' => $cartItem
        ]);
    }

    // ============================
    // حذف عنصر من السلة
    // ============================
    public function remove($cartItemId)
    {
        $cartItem = CartItem::where('user_id', auth()->id())
            ->where('id', $cartItemId)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => false,
                'message' => 'العنصر المطلوب غير موجود داخل السلة',
                'data' => null
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف العنصر من السلة',
            'data' => null
        ]);
    }

    // ============================
    // تفريغ السلة بالكامل
    // ============================
    public function clear()
    {
        $items = CartItem::where('user_id', auth()->id());

        if (!$items->exists()) {
            return response()->json([
                'status' => true,
                'message' => 'السلة فارغة بالفعل',
                'data' => null
            ]);
        }

        $items->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم تفريغ السلة بالكامل',
            'data' => null
        ]);
    }
}
