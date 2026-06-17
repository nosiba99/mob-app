<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * عرض جميع الأقسام
     */
    public function index()
    {
        $categories = Category::orderBy('id', 'DESC')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'Categories fetched successfully',
            'categories' => $categories
        ], 200);
    }

    /**
     * إضافة قسم جديد
     */
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:categories,name',
        'image' => 'nullable|image|max:2048'
    ]);

    $path = null;

    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('categories', 'public');
    }

    $category = Category::create([
        'name' => $request->name,
        'image' => $path
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Category created successfully',
        'category' => $category
    ], 201);
}


    /**
     * عرض قسم واحد
     */
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'category' => $category
        ], 200);
    }

    /**
     * تعديل قسم
     */
   public function update(Request $request, $id)
{
    $category = Category::find($id);

    if (!$category) {
        return response()->json([
            'status' => false,
            'message' => 'Category not found'
        ], 404);
    }

    $request->validate([
        'name' => 'required|string|max:255|unique:categories,name,' . $id,
        'image' => 'nullable|image|max:2048'
    ]);

    // تحديث الاسم
    $category->name = $request->name;

    // تحديث الصورة
    if ($request->hasFile('image')) {

        // حذف القديمة
        if ($category->image && \Storage::disk('public')->exists($category->image)) {
            \Storage::disk('public')->delete($category->image);
        }

        // رفع الجديدة
        $category->image = $request->file('image')->store('categories', 'public');
    }

    $category->save();

    return response()->json([
        'status' => true,
        'message' => 'Category updated successfully',
        'category' => $category
    ], 200);
}


    /**
     * حذف قسم
     */
public function destroy($id)
{
    $category = Category::find($id);

    if (!$category) {
        return response()->json([
            'status' => false,
            'message' => 'Category not found'
        ], 404);
    }

    $category->delete(); // أرشفة وليس حذف نهائي

    return response()->json([
        'status' => true,
        'message' => 'Category archived successfully'
    ], 200);
}
public function archived()
{
    $categories = Category::onlyTrashed()->paginate(20);

    return response()->json([
        'status' => true,
        'message' => 'Archived categories fetched successfully',
        'categories' => $categories
    ]);
}
public function restore($id)
{
    $category = Category::onlyTrashed()->find($id);

    if (!$category) {
        return response()->json([
            'status' => false,
            'message' => 'Archived category not found'
        ], 404);
    }

    $category->restore();

    return response()->json([
        'status' => true,
        'message' => 'Category restored successfully',
        'category' => $category
    ]);
}


}
