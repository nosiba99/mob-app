<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Http\Resources\User\CategoryResource;
use App\Http\Resources\User\CategoryCollection;
use App\Http\Resources\User\ProductResource;
use App\Http\Resources\User\ProductCollection;


class CategoryController extends Controller
{
    // ============================
    // 1) عرض كل التصنيفات
    // ============================
    public function index()
    {
        $categories = Category::withCount('products')->paginate(20);

        return new CategoryCollection($categories);
    }

    // ============================
    // 2) عرض منتجات تصنيف معيّن
    // ============================
    public function products($id)
    {
        $category = Category::findOrFail($id);

        $products = $category->products()
            ->with(['mainImage', 'category'])
            ->paginate(12);

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب منتجات التصنيف بنجاح',

            'data' => [
                'category' => new CategoryResource($category),
                'items'    => ProductResource::collection($products->items()),
            ],

            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ]
        ]);
    }
}
