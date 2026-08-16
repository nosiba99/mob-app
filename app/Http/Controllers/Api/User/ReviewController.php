<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'items'  => $reviews
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'rating'     => 'required|integer|min:1|max:5',
        'comment'    => 'nullable|string'
    ]);

    $user = $request->user();

    // ⭐ تحقق إن المستخدم اشترى المنتج
    $hasPurchased = \App\Models\OrderItem::whereHas('order', function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->where('status', 'delivered'); // الطلب لازم يكون مُسلّم
        })
        ->where('product_id', $request->product_id)
        ->exists();

    if (!$hasPurchased) {
        return response()->json([
            'status'  => false,
            'message' => 'لا يمكنك تقييم منتج لم تقم بشرائه.'
        ], 403);
    }

    // ⭐ إضافة أو تحديث التقييم
    $review = Review::updateOrCreate(
        [
            'user_id'    => $user->id,
            'product_id' => $request->product_id,
        ],
        [
            'rating'  => $request->rating,
            'comment' => $request->comment,
        ]
    );

    return response()->json([
        'status'  => true,
        'message' => 'تم إضافة التقييم بنجاح',
        'data'    => $review
    ]);
}

}
