<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Color;
use App\Models\Size;
use App\Models\Review; // ⭐ إضافة الموديل
use App\Http\Resources\User\ProductResource;
use App\Http\Resources\User\ProductDetailsResource;
use App\Http\Resources\User\ProductCollection;

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
        'category',
        'mainImage'
    ])->whereNull('deleted_at');

    // فلترة حسب القسم
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // فلترة حسب اللون
    if ($request->filled('color')) {
        $color = Color::whereRaw('LOWER(name) = ?', strtolower($request->color))->first();
        if ($color) {
            $query->whereHas('variants', fn($q) => $q->where('color_id', $color->id));
        }
    }

    // فلترة حسب المقاس
    if ($request->filled('size')) {
        $size = Size::whereRaw('LOWER(name) = ?', strtolower($request->size))->first();
        if ($size) {
            $query->whereHas('variants', fn($q) => $q->where('size_id', $size->id));
        }
    }

    // فلترة حسب السعر (يدعم simple + variant)
    // فلترة حسب السعر الثابت (price=500)
if ($request->filled('price')) {
    $query->where(function ($q) use ($request) {
        $q->where('price', $request->price) // للمنتجات simple
          ->orWhereHas('variants', fn($v) =>
              $v->where('price', $request->price) // للمنتجات variant
          );
    });
}

    if ($request->filled('min_price')) {
        $query->where(function ($q) use ($request) {
            $q->where('price', '>=', $request->min_price) // للمنتجات simple
              ->orWhereHas('variants', fn($v) =>
                  $v->where('price', '>=', $request->min_price) // للمنتجات variant
              );
        });
    }

    if ($request->filled('max_price')) {
        $query->where(function ($q) use ($request) {
            $q->where('price', '<=', $request->max_price)
              ->orWhereHas('variants', fn($v) =>
                  $v->where('price', '<=', $request->max_price)
              );
        });
    }

    // فلترة حسب توفر المنتج
    if ($request->filled('in_stock')) {
        if ($request->in_stock == 1) {
            $query->where(function ($q) {
                $q->where('stock', '>', 0) // simple
                  ->orWhereHas('variants', fn($v) => $v->where('stock', '>', 0)); // variant
            });
        } elseif ($request->in_stock == 0) {
            $query->where(function ($q) {
                $q->where('stock', '=', 0)
                  ->orWhereHas('variants', fn($v) => $v->where('stock', '=', 0));
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

    // ترتيب حسب السعر (يدعم simple + variant)
    if ($request->sort === 'price_asc' || $request->sort === 'price_desc') {

        // نجلب أقل سعر variant لكل منتج
        $query->withMin('variants', 'price');

        if ($request->sort === 'price_asc') {
            $query->orderByRaw('COALESCE(products.price, variants_min_price) ASC');
        } else {
            $query->orderByRaw('COALESCE(products.price, variants_min_price) DESC');
        }

    } else {
        // ترتيب عادي
        switch ($request->sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('id', 'desc');
        }
    }

    // Pagination
    $products = $query->paginate(30);

    return new ProductCollection($products);
}



    // ============================
    // 2) عرض تفاصيل منتج + التقييمات
    // ============================
    public function show($id)
    {
        $product = Product::with([
            'images',
            'variants.color',
            'variants.size',
            'category',
            'mainImage',
            'reviews.user' // ⭐ جلب التقييمات مع المستخدم
        ])->find($id);

        if (!$product) {
            return $this->error('المنتج غير موجود', 404);
        }

        if ($product->deleted_at) {
            return $this->error('المنتج محذوف ولا يمكن عرضه', 404);
        }

        // ⭐ متوسط التقييم وعدد التقييمات
        $averageRating = round($product->averageRating(), 2);
        $reviewsCount  = $product->reviewsCount();

        // ⭐ هل المستخدم الحالي قيّم المنتج؟
        $userHasReviewed = false;
        if (auth()->check()) {
            $userHasReviewed = Review::where('user_id', auth()->id())
                ->where('product_id', $product->id)
                ->exists();
        }

        return $this->success('تم جلب تفاصيل المنتج بنجاح', [
            'product'          => new ProductDetailsResource($product),
            'average_rating'   => $averageRating,
            'reviews_count'    => $reviewsCount,
            'user_has_reviewed'=> $userHasReviewed,
            'reviews'          => $product->reviews()->latest()->get()
        ]);
    }
}
