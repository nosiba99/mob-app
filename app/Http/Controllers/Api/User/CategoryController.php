<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->paginate(20); // 🔺 Pagination

        return response()->json($categories);
    }

    public function products($id)
    {
        $category = Category::findOrFail($id);

        $products = $category->products()
            ->with('mainImage')
            ->paginate(12); // 🔺 Pagination

        return response()->json([
            'category' => $category->name,
            'products' => $products,
        ]);
    }
}