<?php
// app/Services/CartService.php
namespace App\Services;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;

class CartService
{
    public function getCart(User $user): array
    {
        $items = CartItem::where('user_id', $user->id)
            ->with(['product.mainImage', 'variant'])
            ->get();

        $total = $items->sum(fn($item) =>
            ($item->variant?->price ?? $item->product->price) * $item->quantity
        );

        return ['items' => $items, 'total' => round($total, 2)];
    }

    public function addOrUpdate(User $user, array $data): CartItem
    {
        // 1 — جيبي الـ variant
        $variant = ProductVariant::where('product_id', $data['product_id'])
            ->where('size',  $data['size']  ?? null)
            ->where('color', $data['color'] ?? null)
            ->first();

        if (!$variant) {
            throw new \Exception('هذا اللون أو المقاس غير متوفر.');
        }

        // 2 — تحقق من المخزون
        // لو في item موجود، اجمع الكمية القديمة مع الجديدة
        $existingItem = CartItem::where('user_id',    $user->id)
            ->where('product_id', $data['product_id'])
            ->where('size',       $data['size']  ?? null)
            ->where('color',      $data['color'] ?? null)
            ->first();

        $totalQty = $data['quantity'] + ($existingItem?->quantity ?? 0);

        if (!$variant->isAvailable($totalQty)) {
            throw new \Exception(
                "الكمية المطلوبة غير متوفرة. المتاح: {$variant->stock} قطعة."
            );
        }

        // 3 — أضف أو حدّث
        return CartItem::updateOrCreate(
            [
                'user_id'    => $user->id,
                'product_id' => $data['product_id'],
                'size'       => $data['size']  ?? null,
                'color'      => $data['color'] ?? null,
            ],
            [
                'variant_id' => $variant->id,
                'quantity'   => $totalQty,
            ]
        );
    }

    public function updateQuantity(User $user, int $cartItemId, int $quantity): CartItem
    {
        $item = CartItem::where('user_id', $user->id)
            ->findOrFail($cartItemId);

        // تحقق من المخزون
        if (!$item->variant->isAvailable($quantity)) {
            throw new \Exception(
                "الكمية المطلوبة غير متوفرة. المتاح: {$item->variant->stock} قطعة."
            );
        }

        $item->update(['quantity' => $quantity]);
        return $item->fresh('product', 'variant');
    }

    public function remove(User $user, int $cartItemId): void
    {
        CartItem::where('user_id', $user->id)
            ->where('id', $cartItemId)
            ->delete();
    }

    public function clear(User $user): void
    {
        CartItem::where('user_id', $user->id)->delete();
    }
}