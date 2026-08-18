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

    public function index()
    {
        $warehouses = Warehouse::with('areas:id,name,warehouse_id')
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

    public function show($id)
    {
        $warehouse = Warehouse::with([
            'areas:id,name',
            'products:id,name'
        ])->find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $areas = $warehouse->areas->map(fn($a) => [
            'id'   => $a->id,
            'name' => $a->name
        ]);

        $products = $warehouse->products->map(fn($p) => [
            'id'   => $p->id,
            'name' => $p->name
        ]);

        $variants = ProductWarehouse::where('warehouse_id', $warehouse->id)
            ->with('variant.product')
            ->get()
            ->map(function ($item) {
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

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string',
            'type'    => 'required|in:main,area',
            'areas'   => 'array'
        ]);

        $warehouse = Warehouse::create($request->only(['name', 'type']));

        if ($request->areas) {
            Area::whereIn('id', $request->areas)->update(['warehouse_id' => $warehouse->id]);
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

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $warehouse->update($request->only(['name', 'type']));

        if ($request->areas) {
            Area::where('warehouse_id', $warehouse->id)
                ->whereNotIn('id', $request->areas)
                ->update(['warehouse_id' => null]);

            Area::whereIn('id', $request->areas)->update(['warehouse_id' => $warehouse->id]);
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

    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $warehouse->delete();

        return $this->success('تم حذف المستودع بنجاح');
    }

    public function areas($id)
    {
        $warehouse = Warehouse::with('areas:id,name,warehouse_id')->find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        $areas = $warehouse->areas->map(fn($a) => [
            'id'   => $a->id,
            'name' => $a->name
        ]);

        return $this->success('تم جلب المناطق المرتبطة بالمستودع', $areas);
    }

    public function attachArea(Request $request, $id)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        Area::where('id', $request->area_id)->update(['warehouse_id' => $warehouse->id]);

        return $this->success('تم ربط المنطقة بالمستودع');
    }

    public function detachArea(Request $request, $id)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id'
        ]);

        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->error('المستودع غير موجود', 404);
        }

        Area::where('id', $request->area_id)
            ->where('warehouse_id', $warehouse->id)
            ->update(['warehouse_id' => null]);

        return $this->success('تم إزالة المنطقة من المستودع');
    }

    public function alerts()
    {
        $products = Product::whereColumn('stock', '<=', 'min_stock')
            ->select('id', 'name', 'stock', 'min_stock')
            ->get();

        return $this->success('تنبيهات المخزون', $products);
    }

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