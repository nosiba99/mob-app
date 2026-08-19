<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with('product.mainImage')
            ->get();

        return response()->json([
            'status' => true,
            'items'  => $items
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $userId = $request->user()->id;
        $productId = $request->product_id;

        $exists = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($exists) {
            $exists->delete();
            return response()->json(['status' => true, 'message' => __('تم إزالة المنتج من المفضلة')]);
        }

        Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId
        ]);

        return response()->json(['status' => true, 'message' => __('تم إضافة المنتج إلى المفضلة')]);
    }
}
