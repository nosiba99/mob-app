<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->cartService->getCart($request->user())
        );
    }

    public function addOrUpdate(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:100'],
            'size'       => ['nullable', 'string'],
            'color'      => ['nullable', 'string'],
        ]);

        try {
            $item = $this->cartService->addOrUpdate(
                $request->user(),
                $request->only('product_id', 'quantity', 'size', 'color')
            );
            return response()->json($item->load('product', 'variant'), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateQuantity(Request $request, int $cartItemId)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $item = $this->cartService->updateQuantity(
                $request->user(),
                $cartItemId,
                $request->quantity
            );
            return response()->json($item);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function remove(Request $request, int $cartItemId)
    {
        $this->cartService->remove($request->user(), $cartItemId);
        return response()->json(['message' => 'تم الحذف من السلة.']);
    }

    public function clear(Request $request)
    {
        $this->cartService->clear($request->user());
        return response()->json(['message' => 'تم تفريغ السلة.']);
    }
}