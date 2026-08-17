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
use App\Models\User;
use App\Models\Area;
use App\Models\Warehouse;
use App\Models\OrderMessage;

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

    // السلة
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

    // ⭐ تحديد المنطقة من العنوان
   // ⭐ تحديد المنطقة من العنوان
$addressWords = explode(' ', $request->shipping_address);

$area = Area::where(function($query) use ($addressWords) {
    foreach ($addressWords as $word) {
        $query->orWhere('name', 'LIKE', '%' . $word . '%');
    }
})->first();

if (!$area) {
    return $this->error('لم يتم العثور على منطقة مناسبة لهذا العنوان');
}



    if (!$area) {
        return $this->error('لم يتم العثور على منطقة مناسبة لهذا العنوان');
    }

    // ⭐ تحديد المستودع التابع للمنطقة
    $warehouse = Warehouse::find($area->warehouse_id);

    if (!$warehouse) {
        return $this->error('لا يوجد مستودع مرتبط بهذه المنطقة');
    }

    // ⭐ إنشاء الطلب بالمنطقة والمستودع الصحيحين
    $order = Order::create([
        'user_id'       => $user->id,
        'total_price'   => $totalPrice,
        'payment_method'=> 'wallet',
        'address'       => $request->shipping_address,
        'notes'         => $request->notes,
        'area_id'       => $area->id,
        'warehouse_id'  => $warehouse->id,
        'status'        => Order::STATUS_PENDING,
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
event(new OrderCreated($order));

    // تنظيف السلة
    CartItem::where('user_id', $user->id)->delete();

    // ⭐ إسناد الطلب للمندوب
    $this->assignDeliveryToOrder($order);

    return $this->success('تم إنشاء الطلب بنجاح', [
        'order_id'     => $order->id,
        'total'        => $order->total_price,
        'area_id'      => $order->area_id,
        'warehouse_id' => $order->warehouse_id,
        'delivery_id'  => $order->delivery_id,
        'status'       => $order->status,
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

    $data = [
        'id'         => $order->id,
        'total'      => $order->total_price,
        'status'     => $order->status,
        'address'    => $order->address,
        'notes'      => $order->notes,
        'barcode'    => $order->barcode,   // ← هذا هو المطلوب

        'created_at' => $order->created_at->format('Y-m-d'),

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
   
private function assignDeliveryToOrder(Order $order)
{
    // نجيب أقل مندوب طلبات في نفس المنطقة
    $delivery = User::where('role', User::ROLE_DELIVERY)
        ->where('area_id', $order->area_id)
        ->where('is_banned', false)
        ->orderBy('active_orders', 'asc')   // أقل عدد طلبات
        ->first();

    if (!$delivery) {
        // ما في مندوب متاح في المنطقة
        $order->update([
            'status' => Order::STATUS_WAITING_DELIVERY
        ]);
        return;
    }

    // ربط الطلب بالمندوب
    $order->update([
        'delivery_id' => $delivery->id,
        'status'      => Order::STATUS_ASSIGNED
    ]);
event(new DeliveryAssigned($order, $deliveryEmployee));

    // ⚠️ مهم جدًا:
    // لا نزيد active_orders هنا
    // لا نغير is_available هنا
    // المندوب يبدأ العمل فقط عند قبول الطلب
}


}
