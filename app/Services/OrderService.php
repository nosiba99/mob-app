<?php
// app/Services/OrderService.php
namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private CartService $cartService) {}

    public function createFromCart(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $cart = $this->cartService->getCart($user);

            if ($cart['items']->isEmpty()) {
                throw new \Exception('السلة فارغة.');
            }

            // تحقق من المخزون لكل item قبل الطلب
            foreach ($cart['items'] as $item) {
                if (!$item->variant->isAvailable($item->quantity)) {
                    throw new \Exception(
                        "المنتج ({$item->product->name} - {$item->color} - {$item->size}) ".
                        "غير متوفر. المتاح: {$item->variant->stock} قطعة."
                    );
                }
            }

            // إنشاء الطلب
            $order = Order::create([
                'user_id'     => $user->id,
                'total_price' => $cart['total'],
                'address'     => $data['address'],
                'notes'       => $data['notes'] ?? null,
                'status'      => 'pending',
            ]);

            // إضافة items + تخفيض المخزون
            foreach ($cart['items'] as $item) {
                $price = $item->variant->price ?? $item->product->price;

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'size'       => $item->size,
                    'color'      => $item->color,
                    'quantity'   => $item->quantity,
                    'price'      => $price,
                ]);

                // تخفيض مخزون الـ variant تحديداً
                $item->variant->decrement('stock', $item->quantity);
            }

            $this->cartService->clear($user);

            return $order->load('items.product', 'items.variant');
        });
    }
}