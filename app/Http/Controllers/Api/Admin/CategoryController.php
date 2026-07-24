<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CategoryService;
use App\Models\Category;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    // عرض جميع الأقسام
    public function index()
    {
        $categories = $this->categoryService->getAll();

        return response()->json([
            'status' => true,
            'message' => 'Categories fetched successfully',
            'categories' => $categories
        ]);
    }

    // إضافة قسم جديد
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name',
            'image' => 'nullable|image|max:2048'
        ]);

        $category = $this->categoryService->create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    // عرض قسم واحد
    public function show($id)
    {
        $category = $this->categoryService->getById($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'category' => $category
        ]);
    }

    // تعديل قسم
    public function update(Request $request, $id)
    {
        $category = $this->categoryService->getById($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $request->validate([
            'name'  => 'required|string|max:255|unique:categories,name,' . $id,
            'image' => 'nullable|image|max:2048'
        ]);

        $updated = $this->categoryService->update($category, $request->all());

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully',
            'category' => $updated
        ]);
    }

    // حذف (أرشفة) قسم
    public function destroy($id)
    {
        $done = $this->categoryService->archive($id);

        if (!$done) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Category archived successfully'
        ]);
    }

    // عرض المؤرشفين
    public function archived()
    {
        $categories = $this->categoryService->getArchived();

        return response()->json([
            'status' => true,
            'message' => 'Archived categories fetched successfully',
            'categories' => $categories
        ]);
    }

    // استعادة قسم
    public function restore($id)
    {
        $category = $this->categoryService->restore($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Archived category not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Category restored successfully',
            'category' => $category
        ]);
    }
}
