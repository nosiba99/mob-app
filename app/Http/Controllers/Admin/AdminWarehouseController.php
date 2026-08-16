<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Area;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\ProductWarehouse;

class AdminWarehouseController extends Controller
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

    // ================================
    // عرض جميع المستودعات (مختصر)
    // ================================
    public function index()
    {
        $warehouses = Warehouse::with('areas:id,name')
            ->select('id', 'name', 'type')
            ->get()
            ->map(function ($warehouse) {
                return [
                    'id'    => $warehouse->id,
                    'name'  => $warehouse->name,
                    'type'  => $warehouse->type,
                    'areas' => $warehouse->areas->map(fn($a) => [
                        'id'   => $a->id,
                        'name' => $a->name
                    ])
                ];
            });

        return $this->success('تم جلب المستودعات بنجاح', $warehouses);
    }

    // ================================
    // عرض مستودع واحد (مختصر)
    // ================================
public function show($id)
{
    $warehouse = Warehouse::with([
        'areas:id,name',
        'products:id,name'
    ])->find($id);

    if (!$warehouse) {
        return $this->error('المستودع غير موجود', 404);
    }

    // ترتيب المناطق
    $areas = $warehouse->areas->map(fn($a) => [
        'id'   => $a->id,
        'name' => $a->name
    ]);

    // ترتيب المنتجات
    $products = $warehouse->products->map(fn($p) => [
        'id'   => $p->id,
        'name' => $p->name
    ]);

    // ترتيب الفاريانتات داخل المستودع مع حماية null
    $variants = ProductWarehouse::where('warehouse_id', $warehouse->id)
        ->with('variant.product')
        ->get()
        ->map(function ($item) {

            // إذا الفاريانت أو المنتج ناقص → ما نوقع النظام
            if (!$item->variant || !$item->variant->product) {
                return [
                    'variant_id'   => $item->variant_id,
                    'product_name' => null,
                    'variant_name' => null,
                    'stock'        => $item->stock,
                    'warning'      => 'variant_missing_or_deleted'
                ];
            }

            return [
                'variant_id'   => $item->variant_id,
                'product_name' => $item->variant->product->name,
                'variant_name' => $item->variant->name ?? '',
                'stock'        => $item->stock,
            ];
        });

    // الريسبونس النهائي
    $data = [
        'id'       => $warehouse->id,
        'name'     => $warehouse->name,
        'type'     => $warehouse->type,
        'areas'    => $areas,
        'products' => $products,
        'variants' => $variants
    ];

    return $this->success('تم جلب تفاصيل المستودع', $data);
}


    // ================================
    // إضافة مستودع جديد
    // ================================
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string',
            'type'    => 'required|in:main,area',
            'areas'   => 'array'
        ]);

        $warehouse = Warehouse::create($request->only(['name', 'type']));

        if ($request->areas) {
            $warehouse->areas()->sync($request->areas);
        }

        return $this->success('تم إنشاء المستودع بنجاح', [
            'id'    => $warehouse->id,
            'name'  => $warehouse->name,
            'type'  => $warehouse->type,
            'areas' => $warehouse->areas->map(fn($a) => [
                'id'   => $a->id,
                'name' => $a->name
            ])
        ]);
    }

    // ================================
    // تعديل مستودع
    // ================================
    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $warehouse->update($request->only(['name', 'type']));

        if ($request->areas) {
            $warehouse->areas()->sync($request->areas);
        }

        return $this->success('تم تعديل المستودع بنجاح', [
            'id'    => $warehouse->id,
            'name'  => $warehouse->name,
            'type'  => $warehouse->type,
            'areas' => $warehouse->areas->map(fn($a) => [
                'id'   => $a->id,
                'name' => $a->name
            ])
        ]);
    }

    // ================================
    // حذف مستودع
    // ================================
    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $warehouse->delete();

        return $this->success('تم حذف المستودع بنجاح');
    }

   

    
    // ================================
    // حالة المخزون داخل المستودع
    // ================================
   
    // ================================
    // عرض المناطق المرتبطة بالمستودع
    // ================================
    public function areas($id)
    {
        $warehouse = Warehouse::with('areas:id,name')->find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $areas = $warehouse->areas->map(fn($a) => [
            'id'   => $a->id,
            'name' => $a->name
        ]);

        return $this->success('تم جلب المناطق المرتبطة بالمستودع', $areas);
    }

    // ================================
    // ربط منطقة بمستودع
    // ================================
    public function attachArea(Request $request, $id)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $warehouse->areas()->syncWithoutDetaching([$request->area_id]);

        return $this->success('تم ربط المنطقة بالمستودع');
    }

    // ================================
    // إزالة منطقة من مستودع
    // ================================
    public function detachArea(Request $request, $id)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $warehouse->areas()->detach($request->area_id);

        return $this->success('تم إزالة المنطقة من المستودع');
    }

    // ================================
    // تنبيهات المخزون
    // ================================
    public function alerts()
    {
        $products = Product::whereColumn('stock', '<=', 'min_stock')
            ->select('id', 'name', 'stock', 'min_stock')
            ->get();

        return $this->success('تنبيهات المخزون', $products);
    }

    // ================================
    // ربط فاريانت بمستودع
    // ================================
    public function attachVariant(Request $request, $id)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'stock'      => 'required|integer|min:0'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        ProductWarehouse::updateOrCreate(
            [
                'variant_id'   => $request->variant_id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'stock' => $request->stock
            ]
        );

        return $this->success('تم ربط الفاريانت بالمستودع وتحديث الكمية');
    }

    // ================================
    // إزالة فاريانت من مستودع
    // ================================
    public function detachVariant(Request $request, $id)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        ProductWarehouse::where('variant_id', $request->variant_id)
            ->where('warehouse_id', $warehouse->id)
            ->delete();

        return $this->success('تم إزالة الفاريانت من المستودع');
    }

    // ================================
    // مخزون الفاريانت داخل المستودع
    // ================================
    public function stockVariants($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $variants = ProductWarehouse::where('warehouse_id', $id)
            ->with('variant.product')
            ->get()
            ->map(function ($item) {
                return [
                    'variant_id'   => $item->variant_id,
                    'product_name' => $item->variant->product->name,
                    'variant_name' => $item->variant->name ?? '',
                    'stock'        => $item->stock,
                ];
            });

        return $this->success('مخزون الفاريانت داخل المستودع', $variants);
    }
}
