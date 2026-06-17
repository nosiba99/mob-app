<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'rating'     => ['required', 'integer', 'min:1', 'max:5'],
            'comment'    => ['nullable', 'string', 'max:500'],
        ]);

        // 🔺 شرط: لازم يكون اشترى المنتج
        $hasPurchased = Order::where('user_id', $request->user()->id)
            ->where('status', 'delivered')
            ->whereHas('items', fn($q) =>
                $q->where('product_id', $request->product_id)
            )->exists();

        if (!$hasPurchased) {
            return response()->json([
                'message' => 'يمكنك التقييم فقط بعد استلام المنتج.'
            ], 403);
        }

        $review = Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'product_id' => $request->product_id],
            ['rating' => $request->rating, 'comment' => $request->comment]
        );

        return response()->json($review, 201);
    }

    public function index(int $productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->with('user')
            ->latest()
            ->paginate(10); // 🔺 Pagination

        return response()->json($reviews);
    }
}