<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\ProductVariantSize;
use App\Models\User;

class CartService
{
    public function getCart(User $user): array
    {
        $items = CartItem::where('user_id', $user->id)
            ->with(['product.mainImage', 'variant', 'size'])
            ->get();

        $total = $items->sum(fn($item) =>
            ($item->variant?->price ?? $item->product->price) * $item->quantity
        );

        return ['items' => $items, 'total' => round($total, 2)];
    }

    public function addOrUpdate(User $user, array $data): CartItem
    {
        // 1 — جيبي الـ variant
        $variant = ProductVariant::find($data['variant_id']);

        if (!$variant) {
            throw new \Exception('الفاريانت غير موجود.');
        }

        // 2 — جيبي الـ size داخل الفاريانت
        $variantSize = ProductVariantSize::where('product_variant_id', $variant->id)
            ->where('size_id', $data['size_id'])
            ->first();

        if (!$variantSize) {
            throw new \Exception('هذا المقاس غير متوفر لهذا الفاريانت.');
        }

        // 3 — تحقق من المخزون
        $existingItem = CartItem::where('user_id', $user->id)
            ->where('variant_id', $variant->id)
            ->where('size_id', $data['size_id'])
            ->first();

        $totalQty = $data['quantity'] + ($existingItem?->quantity ?? 0);

        if ($variantSize->stock < $totalQty) {
            throw new \Exception("الكمية المطلوبة غير متوفرة. المتاح: {$variantSize->stock} قطعة.");
        }

        // 4 — أضف أو حدّث
        return CartItem::updateOrCreate(
            [
                'user_id'    => $user->id,
                'variant_id' => $variant->id,
                'size_id'    => $data['size_id'],
            ],
            [
                'product_id' => $variant->product_id,
                'quantity'   => $totalQty,
            ]
        );
    }

    public function updateQuantity(User $user, int $cartItemId, int $quantity): CartItem
    {
        $item = CartItem::where('user_id', $user->id)
            ->findOrFail($cartItemId);

        $variantSize = ProductVariantSize::where('product_variant_id', $item->variant_id)
            ->where('size_id', $item->size_id)
            ->first();

        if ($variantSize->stock < $quantity) {
            throw new \Exception("الكمية المطلوبة غير متوفرة. المتاح: {$variantSize->stock} قطعة.");
        }

        $item->update(['quantity' => $quantity]);
        return $item->fresh('product', 'variant', 'size');
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
