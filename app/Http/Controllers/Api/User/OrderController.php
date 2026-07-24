<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Http\Resources\User\OrderResource;
use App\Http\Resources\User\OrderCollection;

class OrderController extends Controller
{
    private function success($message, $data = null)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data
        ]);
    }

    private function error($message, $code = 400)
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => null
        ], $code);
    }

    // ============================
    // 1) إنشاء طلب (Checkout)
    // ============================
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
            $total += $product->price * $item['quantity'];
        }

        $order = Order::create([
            'user_id'        => auth()->id(),
            'total_price'    => $total,
            'status'         => 'pending',
            'payment_method' => $request->payment_method,
            'address'        => $request->address,
            'notes'          => $request->notes
        ]);

        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity'   => $item['quantity'],
                'price'      => Product::find($item['product_id'])->price
            ]);
        }

        return $this->success('تم إنشاء الطلب بنجاح', [
            'order_id' => $order->id,
            'total'    => $total
        ]);
    }

    // ============================
    // 2) عرض طلباتي
    // ============================
    public function myOrders()
    {
        $orders = Order::where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->paginate(20);

        return new OrderCollection($orders);
    }

    // ============================
    // 3) عرض تفاصيل طلب
    // ============================
    public function show($id)
    {
        $order = Order::with(['items.product.mainImage', 'items.variant.color', 'items.variant.size'])
            ->where('user_id', auth()->id())
            ->find($id);

        if (!$order) {
            return $this->error('الطلب غير موجود', 404);
        }

        return $this->success('تم جلب تفاصيل الطلب', new OrderResource($order));
    }

    // ============================
    // 4) إلغاء طلب
    // ============================
    public function cancel($id)
    {
        $order = Order::where('user_id', auth()->id())->find($id);

        if (!$order) {
            return $this->error('الطلب غير موجود', 404);
        }

        if (!in_array($order->status, ['pending', 'processing'])) {
            return $this->error('لا يمكن إلغاء هذا الطلب', 400);
        }

        $order->update(['status' => 'canceled']);

        return $this->success('تم إلغاء الطلب بنجاح');
    }

    // ============================
    // 5) أرشفة طلب
    // ============================
    public function destroy($id)
    {
        $order = Order::where('user_id', auth()->id())->find($id);

        if (!$order) {
            return $this->error('الطلب غير موجود', 404);
        }

        $order->delete();

        return $this->success('تم أرشفة الطلب بنجاح');
    }

    // ============================
    // 6) تعديل طلب
    // ============================
    public function update(Request $request, $id)
    {
        $order = Order::where('user_id', auth()->id())->find($id);

        if (!$order) {
            return $this->error('الطلب غير موجود', 404);
        }

        $order->update([
            'address'        => $request->address,
            'notes'          => $request->notes,
            'payment_method' => $request->payment_method,
        ]);

        return $this->success('تم تعديل الطلب بنجاح');
    }

    // ============================
    // 7) حذف نهائي
    // ============================
    public function forceDelete($id)
    {
        $order = Order::withTrashed()
            ->where('user_id', auth()->id())
            ->find($id);

        if (!$order) {
            return $this->error('الطلب غير موجود', 404);
        }

        $order->forceDelete();

        return $this->success('تم حذف الطلب نهائياً');
    }

    // ============================
    // 8) عرض الطلبات المؤرشفة
    // ============================
    public function archived()
    {
        $orders = Order::onlyTrashed()
            ->where('user_id', auth()->id())
            ->get();

        return $this->success('تم جلب الطلبات المؤرشفة', [
            'items' => OrderResource::collection($orders)
        ]);
    }

    // ============================
    // 9) استرجاع طلب
    // ============================
    public function restore($id)
    {
        $order = Order::onlyTrashed()
            ->where('user_id', auth()->id())
            ->find($id);

        if (!$order) {
            return $this->error('الطلب غير موجود في الأرشيف', 404);
        }

        $order->restore();

        return $this->success('تم استرجاع الطلب بنجاح');
    }
}
