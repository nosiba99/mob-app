<?php
namespace App\Http\Controllers\Api\Admin;



use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Area;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\ProductWarehouse;


class AdminOrderController extends Controller
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

    // عرض جميع المستودعات
    public function index()
    {
        $warehouses = Warehouse::with(['areas', 'products'])->get();
        return $this->success('تم جلب المستودعات بنجاح', $warehouses);
    }

    // عرض تفاصيل مستودع
    public function show($id)
    {
        $warehouse = Warehouse::with(['areas', 'products'])->find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        return $this->success('تم جلب تفاصيل المستودع', $warehouse);
    }

    // إضافة مستودع جديد
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string',
            'type'    => 'required|in:main,area',
            'areas'   => 'array'
        ]);

        $warehouse = Warehouse::create($request->only(['name', 'type']));

        // ربط المناطق بالمستودع
        if ($request->areas) {
            foreach ($request->areas as $areaId) {
                Area::where('id', $areaId)->update(['warehouse_id' => $warehouse->id]);
            }
        }

        return $this->success('تم إنشاء المستودع بنجاح', $warehouse);
    }

    // تعديل مستودع
    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $warehouse->update($request->only(['name', 'address', 'phone', 'manager_name']));

        // تحديث المناطق
        if ($request->areas) {
            foreach ($request->areas as $areaId) {
                Area::where('id', $areaId)->update(['warehouse_id' => $warehouse->id]);
            }
        }

        return $this->success('تم تعديل المستودع بنجاح', $warehouse);
    }

    // حذف مستودع
    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $warehouse->delete();

        return $this->success('تم حذف المستودع بنجاح');
    }

    // عرض المنتجات داخل المستودع
    public function products($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $products = $warehouse->products()->get();

        return $this->success('تم جلب منتجات المستودع', $products);
    }

    // ربط منتج بمستودع
    public function attachProduct(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stock' => 'required|integer|min:0'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        ProductWarehouse::updateOrCreate(
            [
                'product_id' => $request->product_id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'stock' => $request->stock
            ]
        );

        return $this->success('تم ربط المنتج بالمستودع وتحديث الكمية');
    }

    // إزالة منتج من مستودع
    public function detachProduct(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        ProductWarehouse::where('product_id', $request->product_id)
            ->where('warehouse_id', $warehouse->id)
            ->delete();

        return $this->success('تم إزالة المنتج من المستودع');
    }

    // حالة المخزون داخل مستودع
    public function stock($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $products = ProductWarehouse::where('warehouse_id', $id)
            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->select('products.id', 'products.name', 'products.min_stock', 'product_warehouse.stock')
            ->get()
            ->map(function ($product) {
                $stock = (int) ($product->stock ?? 0);

                return [
                    'id'        => $product->id,
                    'name'      => $product->name,
                    'stock'     => $stock,
                    'min_stock' => $product->min_stock,
                    'status'    => $stock <= 0
                        ? 'out_of_stock'
                        : ($stock <= $product->min_stock
                            ? 'low_stock'
                            : 'available')
                ];
            });

        return $this->success('تم جلب حالة المخزون', $products);
    }

    // عرض المناطق المرتبطة بالمستودع
    public function areas($id)
    {
        $warehouse = Warehouse::with('areas')->find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        return $this->success('تم جلب المناطق المرتبطة بالمستودع', $warehouse->areas);
    }

    // ربط منطقة بالمستودع
    public function attachArea(Request $request, $id)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id'
        ]);

        $warehouse = Warehouse::find($id);

        $area = Area::find($request->area_id);
        $area->warehouse_id = $warehouse->id;
        $area->save();

        return $this->success('تم ربط المنطقة بالمستودع');
    }

    // إزالة منطقة من المستودع
    public function detachArea(Request $request, $id)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id'
        ]);

        $area = Area::find($request->area_id);
        $area->warehouse_id = null;
        $area->save();

        return $this->success('تم إزالة المنطقة من المستودع');
    }
}
