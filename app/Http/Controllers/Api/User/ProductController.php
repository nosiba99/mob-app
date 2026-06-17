<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Color;
use App\Models\Size;


class ProductController extends Controller
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
    // 1) عرض كل المنتجات + الفلترة
    // ============================
 public function index(Request $request)
{
    $query = Product::with([
        'images',
        'variants.color',
        'variants.size',
        'category'
    ])->whereNull('deleted_at');

    // فلترة حسب القسم
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // فلترة حسب اللون
    if ($request->filled('color')) {

        $color = Color::where('name', $request->color)->first();

        if ($color) {
            $query->whereHas('variants', function ($q) use ($color) {
                $q->where('color_id', $color->id);
            });
        }
    }
    // فلترة حسب المقاس
   
   
    if ($request->filled('size')) {

        $size = Size::where('name', $request->size)->first();

        if ($size) {
            $query->whereHas('variants', function ($q) use ($size) {
                $q->where('size_id', $size->id);
            });
        }
    }
   

    // فلترة حسب السعر
    if ($request->filled('min_price')) {
        $query->where('price', '>=', $request->min_price);
    }

    if ($request->filled('max_price')) {
        $query->where('price', '<=', $request->max_price);
    }

    // فلترة حسب توفر المنتج
    if ($request->filled('in_stock')) {
        if ($request->in_stock == 1) {
            // المنتجات المتوفرة
            $query->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0);
            });
        } elseif ($request->in_stock == 0) {
            // المنتجات غير المتوفرة
            $query->whereHas('variants', function ($q) {
                $q->where('stock', '=', 0);
            });
        }
    }

    // البحث
    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->search . '%')
              ->orWhere('description', 'LIKE', '%' . $request->search . '%');
        });
    }

    // الترتيب
    switch ($request->sort) {
        case 'price_asc':
            $query->orderBy('price', 'asc');
            break;

        case 'price_desc':
            $query->orderBy('price', 'desc');
            break;

        case 'newest':
            $query->orderBy('created_at', 'desc');
            break;

        default:
            $query->orderBy('id', 'desc');
    }

    // Pagination
    $products = $query->paginate(12);

    return $this->success('تم جلب المنتجات بنجاح', [
        'products'   => $products->items(),
        'pagination' => [
            'current_page' => $products->currentPage(),
            'total_pages'  => $products->lastPage(),
            'total_items'  => $products->total(),
        ]
    ]);
}

     

    // ============================
    // 2) عرض تفاصيل منتج
    // ============================
    public function show($id)
    {
        $product = Product::with([
            'images',
            'variants.color',
            'variants.size',
            'category'
        ])->find($id);

        if (!$product) {
            return $this->error('المنتج غير موجود', 404);
        }

        if ($product->deleted_at) {
            return $this->error('المنتج محذوف ولا يمكن عرضه', 404);
        }

        return $this->success('تم جلب تفاصيل المنتج بنجاح', $product);
    }
}
