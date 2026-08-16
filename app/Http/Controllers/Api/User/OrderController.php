<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\ProductWarehouse;
use App\Models\WalletTransaction;
use App\Http\Resources\User\OrderResource;
use App\Services\ProductService;

class OrderController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /* ============================
       دوال الريسبونس الموحدة
       ============================ */

    private function success($message, $data = null)
    {
        $response = [
            'status'  => true,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    private function error($message, $code = 400)
    {
        return response()->json([
            'status'  => false,
            'message' => $message
        ], $code);
    }

    /* ============================
       1) إنشاء طلب جديد (checkout)
       ============================ */

    public function checkout(Request $request)
    {
        $user = auth()->user();

        $cartItems = CartItem::with('variant')->where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return $this->error('السلة فارغة');
        }

        // حساب السعر النهائي
        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $price = $item->variant->price;
            $totalPrice += ($price * $item->quantity);
        }

        // إنشاء الطلب
        $order = Order::create([
            'user_id'       => $user->id,
            'total_price'   => $totalPrice,
            'payment_method'=> 'wallet',
            'address'       => $request->shipping_address,
            'notes'         => $request->notes,
            'area_id'       => $user->area_id,
            'warehouse_id'  => $user->warehouse_id,
        ]);

        // عناصر الطلب + خصم المخزون
        foreach ($cartItems as $item) {

            $price = $item->variant->price;
            $lineTotal = $price * $item->quantity;

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity'   => $item->quantity,
                'price'      => $price,
                'total'      => $lineTotal,
            ]);

            $this->productService->decreaseStock($item->variant, $item->quantity);
        }

        CartItem::where('user_id', $user->id)->delete();

        return $this->success('تم إنشاء الطلب بنجاح', [
            'order_id' => $order->id,
            'total'    => $order->total_price
        ]);
    }

    /* ============================
       2) جلب طلبات المستخدم
       ============================ */

  public function myOrders()
{
    $orders = Order::where('user_id', auth()->id())
        ->latest()
        ->get();

    $data = $orders->map(function ($order) {
        return [
            'id'          => $order->id,
            'total'       => $order->total_price,
            'status'      => $order->status,
            'created_at'  => $order->created_at->format('Y-m-d'),
        ];
    });

    return $this->success('طلبات المستخدم', $data);
}


    /* ============================
       3) الطلبات المؤرشفة
       ============================ */

    public function archived()
    {
        $orders = Order::onlyTrashed()
            ->where('user_id', auth()->id())
            ->with(['items.product', 'items.variant', 'items.size', 'area', 'warehouse'])
            ->latest()
            ->get();

        return $this->success('الطلبات المؤرشفة', OrderResource::collection($orders));
    }

    /* ============================
       4) استعادة طلب مؤرشف
       ============================ */

    public function restore($id)
    {
        $order = Order::onlyTrashed()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('الطلب غير موجود أو غير مؤرشف');
        }

        $order->restore();

        return $this->success('تم استعادة الطلب', new OrderResource($order));
    }

    /* ============================
       5) حذف نهائي
       ============================ */

    public function forceDelete($id)
    {
        $order = Order::onlyTrashed()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('الطلب غير موجود أو غير مؤرشف');
        }

        $order->forceDelete();

        return $this->success('تم حذف الطلب نهائيًا');
    }

    /* ============================
       6) عرض تفاصيل طلب واحد
       ============================ */

   public function show($id)
{
    $order = Order::where('id', $id)
        ->where('user_id', auth()->id())
        ->with(['items.product', 'items.variant'])
        ->first();

    if (!$order) {
        return $this->error('الطلب غير موجود');
    }

    // ترتيب البيانات بشكل بسيط وواضح
    $data = [
        'id'         => $order->id,
        'total'      => $order->total_price,
        'status'     => $order->status,
        'address'    => $order->address,
        'notes'      => $order->notes,
        'created_at' => $order->created_at->format('Y-m-d'),


        // عناصر الطلب بشكل بسيط
        'items' => $order->items->map(function ($item) {
            return [
                'product_name' => $item->product->name,
                'variant_id'   => $item->variant_id,
                'quantity'     => $item->quantity,
                'price'        => $item->price,
                'total'        => $item->total,
            ];
        }),
    ];

    return $this->success('تفاصيل الطلب', $data);
}


    /* ============================
       7) تحديث طلب
       ============================ */

    public function update(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('الطلب غير موجود');
        }

        $order->update($request->only(['address', 'notes']));

        return $this->success('تم تحديث الطلب بنجاح', new OrderResource($order));
    }

    /* ============================
       8) حذف طلب (Soft Delete)
       ============================ */

    public function destroy($id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return $this->error('الطلب غير موجود');
        }

        $order->delete();

        return $this->success('تم حذف الطلب');
    }

    /* ============================
       9) استرجاع رصيد الطلب
       ============================ */

    public function refund($id)
    {
        $order = Order::findOrFail($id);
        $user = $order->user;

        $user->wallet_balance += $order->total_price;
        $user->save();

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $order->total_price,
            'type' => 'refund',
            'description' => 'استرجاع رصيد لطلب رقم ' . $order->id,
        ]);

        $order->status = Order::STATUS_RETURNED;
        $order->save();

        return $this->success('تم استرجاع الرصيد بنجاح');
    }
}
