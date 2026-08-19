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
use App\Models\ProductVariant;
use App\Models\OrderMessage;
use App\Events\OrderCreated;
use App\Events\DeliveryAssigned;
use Illuminate\Support\Facades\DB;

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

    $request->validate([
        'shipping_address' => 'required|string',
        'notes'             => 'nullable|string',
        'payment_method'    => 'nullable|in:wallet,cash',
    ]);

    // السلة
    $cartItems = CartItem::with('variant')->where('user_id', $user->id)->get();
    if ($cartItems->isEmpty()) {
        return $this->error(__('السلة فارغة'));
    }

    // حساب السعر النهائي
    $totalPrice = 0;
    foreach ($cartItems as $item) {
        if (!$item->variant) {
            return $this->error(__('أحد المنتجات بالسلة لم يعد متوفراً'));
        }
        if (is_null($item->variant->price)) {
            return $this->error(__('يوجد منتج بالسلة بدون سعر محدد، الرجاء التواصل مع الدعم'));
        }
        $totalPrice += ($item->variant->price * $item->quantity);
    }

    // ⭐ تحديد المنطقة من العنوان
    $addressWords = explode(' ', $request->shipping_address);

    $area = Area::where(function ($query) use ($addressWords) {
        foreach ($addressWords as $word) {
            if (trim($word) === '') {
                continue;
            }
            $query->orWhere('name', 'LIKE', '%' . $word . '%');
        }
    })->first();

    if (!$area) {
        return $this->error(__('لم يتم العثور على منطقة مناسبة لهذا العنوان'));
    }

    // ⭐ تحديد المستودع التابع للمنطقة
    $warehouse = Warehouse::find($area->warehouse_id);

    if (!$warehouse) {
        return $this->error(__('لا يوجد مستودع مرتبط بهذه المنطقة'));
    }

    $paymentMethod = $request->payment_method ?? 'wallet';

    // ⭐ التحقق من رصيد المحفظة قبل أي تعديل على الداتا
    if ($paymentMethod === 'wallet' && $user->wallet_balance < $totalPrice) {
        return $this->error(__('رصيد المحفظة غير كافٍ لإتمام الطلب'));
    }

    // ⭐ التحقق من توفر المخزون داخل هذا المستودع تحديداً (product_warehouse)
    $stockRows = [];
    foreach ($cartItems as $item) {
        $stockRow = ProductWarehouse::where('warehouse_id', $warehouse->id)
            ->where('variant_id', $item->variant_id)
            ->lockForUpdate()
            ->first();

        if (!$stockRow || $stockRow->stock < $item->quantity) {
            return $this->error(__('الكمية غير متوفرة حالياً في مستودع منطقتك لمنتج: ') .
                    optional($item->product)->name
            );
        }

        $stockRows[$item->id] = $stockRow;
    }

    $order = DB::transaction(function () use ($user, $request, $area, $warehouse, $cartItems, $stockRows, $totalPrice, $paymentMethod) {

        // ⭐ إنشاء الطلب بالمنطقة والمستودع الصحيحين
        $order = Order::create([
            'user_id'        => $user->id,
            'total_price'    => $totalPrice,
            'payment_method' => $paymentMethod,
            'address'        => $request->shipping_address,
            'notes'          => $request->notes,
            'area_id'        => $area->id,
            'warehouse_id'   => $warehouse->id,
            'status'         => Order::STATUS_PENDING,
        ]);

        // عناصر الطلب + خصم المخزون من نفس المستودع المحدد
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

            // خصم من مخزون هذا المستودع تحديداً
            $stockRows[$item->id]->decrement('stock', $item->quantity);
        }

        // ⭐ خصم من المحفظة وتسجيل الحركة
        if ($paymentMethod === 'wallet') {
            $user->decrement('wallet_balance', $totalPrice);

            WalletTransaction::create([
                'user_id'     => $user->id,
                'amount'      => $totalPrice,
                'type'        => 'purchase',
                'description' => 'دفع ثمن الطلب رقم ' . $order->id,
            ]);
        }

        return $order;
    });

    event(new OrderCreated($order));

    // تنظيف السلة
    CartItem::where('user_id', $user->id)->delete();

    // إشعار المستخدم
    (new \App\Services\OrderService())->notifyUser($user->id, 'تم إنشاء الطلب', 'طلبك قيد المعالجة الآن');

    // ⭐ إسناد الطلب للمندوب
    $this->assignDeliveryToOrder($order);

    return $this->success(__('تم إنشاء الطلب بنجاح'), [
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

    return $this->success(__('طلبات المستخدم'), $data);
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

        return $this->success(__('الطلبات المؤرشفة'), OrderResource::collection($orders));
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
            return $this->error(__('الطلب غير موجود أو غير مؤرشف'));
        }

        $order->restore();

        return $this->success(__('تم استعادة الطلب'), new OrderResource($order));
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
            return $this->error(__('الطلب غير موجود أو غير مؤرشف'));
        }

        $order->forceDelete();

        return $this->success(__('تم حذف الطلب نهائيًا'));
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
        return $this->error(__('الطلب غير موجود'));
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

    return $this->success(__('تفاصيل الطلب'), $data);
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
            return $this->error(__('الطلب غير موجود'));
        }

        $order->update($request->only(['address', 'notes']));

        return $this->success(__('تم تحديث الطلب بنجاح'), new OrderResource($order));
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
            return $this->error(__('الطلب غير موجود'));
        }

        $order->delete();

        return $this->success(__('تم حذف الطلب'));
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

        return $this->success(__('تم استرجاع الرصيد بنجاح'));
    }
   
private function assignDeliveryToOrder(Order $order)
{
    // نجيب أقل مندوب طلبات، تابع لنفس المنطقة *و* نفس المستودع معاً
    $delivery = User::where('role', User::ROLE_DELIVERY)
        ->where('area_id', $order->area_id)
        ->where('warehouse_id', $order->warehouse_id)
        ->where('is_banned', false)
        ->where('is_active', true)
        ->where('is_available', true)
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

    event(new DeliveryAssigned($order, $delivery));

    // ⚠️ مهم جدًا:
    // لا نزيد active_orders هنا
    // لا نغير is_available هنا
    // المندوب يبدأ العمل فقط عند قبول الطلب (بمرحلة accept بملف DeliveryStatusController)
}


}
