<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;

class OrderController extends Controller
{
   public function checkout(Request $request)
{
    $request->validate([
        'items' => 'required|array',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.variant_id' => 'nullable|exists:product_variants,id',
        'items.*.quantity' => 'required|integer|min:1',
        'address' => 'required|string',
        'payment_method' => 'required|in:cash,card'
    ]);

    $total = 0;

    foreach ($request->items as $item) {
        $product = Product::find($item['product_id']);
        $price = $product->price;

        $total += $price * $item['quantity'];
    }

    $order = Order::create([
        'user_id' => auth()->id(),
        'total_price' => $total,
        'status' => 'pending',
        'payment_method' => $request->payment_method,
        'address' => $request->address,
        'notes' => $request->notes
    ]);

    foreach ($request->items as $item) {
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'] ?? null,
            'quantity' => $item['quantity'],
            'price' => Product::find($item['product_id'])->price
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'تم إنشاء الطلب بنجاح',
        'order_id' => $order->id
    ]);
}
public function myOrders()
{
    $orders = Order::where('user_id', auth()->id())
        ->orderBy('id', 'desc')
        ->paginate(20);

    return response()->json([
        'status' => true,
        'message' => 'تم جلب طلبات المستخدم',
        'data' => $orders
    ]);
}
public function show($id)
{
    $order = Order::with([
        'items',
        'items.product',
        'items.variant'
    ])
    ->where('user_id', auth()->id())
    ->find($id);

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'الطلب غير موجود'
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'تم جلب تفاصيل الطلب',
        'data' => $order
    ]);
}
public function cancel($id)
{
    $order = Order::where('user_id', auth()->id())->find($id);

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'الطلب غير موجود'
        ], 404);
    }

    if (!in_array($order->status, ['pending', 'processing'])) {
        return response()->json([
            'status' => false,
            'message' => 'لا يمكن إلغاء هذا الطلب'
        ], 400);
    }

    $order->status = 'canceled';
    $order->save();

    return response()->json([
        'status' => true,
        'message' => 'تم إلغاء الطلب بنجاح'
    ]);
}

public function destroy($id)
{
    $order = Order::where('user_id', auth()->id())->find($id);

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'الطلب غير موجود'
        ], 404);
    }

    $order->delete(); // Soft Delete

    return response()->json([
        'status' => true,
        'message' => 'تم أرشفة الطلب بنجاح'
    ]);
}

public function update(Request $request, $id)
{
    $order = Order::where('user_id', auth()->id())->find($id);

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'الطلب غير موجود'
        ], 404);
    }

    $order->update([
        'address' => $request->address,
        'notes' => $request->notes,
        'payment_method' => $request->payment_method,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'تم تعديل الطلب بنجاح'
    ]);
}

public function forceDelete($id)
{
    $order = Order::withTrashed()
        ->where('user_id', auth()->id())
        ->find($id);

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'الطلب غير موجود'
        ], 404);
    }

    $order->forceDelete(); // حذف نهائي

    return response()->json([
        'status' => true,
        'message' => 'تم حذف الطلب نهائياً'
    ]);
}
public function archived()
{
    $orders = Order::onlyTrashed()
        ->where('user_id', auth()->id())
        ->get();

    return response()->json([
        'status' => true,
        'orders' => $orders
    ]);
}
public function restore($id)
{
    $order = Order::onlyTrashed()
        ->where('user_id', auth()->id())
        ->find($id);

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'الطلب غير موجود في الأرشيف'
        ], 404);
    }

    $order->restore();

    return response()->json([
        'status' => true,
        'message' => 'تم استرجاع الطلب بنجاح'
    ]);
}

}