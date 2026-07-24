<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OrderService;

class AdminOrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    // ─── جلب كل الطلبات ───────────────────────────────
    public function index()
    {
        $orders = $this->orderService->getAllOrders();

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب الطلبات بنجاح',
            'data'    => $orders
        ]);
    }

    // ─── جلب تفاصيل طلب واحد ───────────────────────────
    public function show($id)
    {
        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return response()->json([
                'status'  => false,
                'message' => 'الطلب غير موجود'
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب بيانات الطلب',
            'data'    => $order
        ]);
    }

    // ─── تحديث حالة الطلب ─────────────────────────────
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,canceled'
        ]);

        $updated = $this->orderService->updateOrderStatus($id, $request->status);

        if (!$updated) {
            return response()->json([
                'status'  => false,
                'message' => 'الطلب غير موجود'
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث حالة الطلب بنجاح'
        ]);
    }
    // ─── جلب الطلبات حسب الحالة ───────────────────────────────
public function getByStatus($status)
{
    $orders = $this->orderService->getOrdersByStatus($status);

    return response()->json([
        'status'  => true,
        'message' => "تم جلب الطلبات بحالة {$status} بنجاح",
        'data'    => $orders
    ]);
}
// ─── البحث عن طلب ───────────────────────────────

public function search(Request $request)
{
    $orders = $this->orderService->searchOrders(
        $request->query('order_id'),
        $request->query('user')
    );

    return response()->json([
        'status' => true,
        'message' => 'نتائج البحث',
        'data' => $orders
    ]);
}


}


