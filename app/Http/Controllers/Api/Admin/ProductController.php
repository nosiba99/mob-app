<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Services\ProductService;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    // ============================
    // Helper للريسبونس الموحد
    // ============================
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
    // 1) عرض كل المنتجات
    // ============================
    public function index()
    {
        $products = $this->productService->getAll();
        return $this->success('تم جلب المنتجات بنجاح', $products);
    }

    // ============================
    // 2) عرض منتج واحد
    // ============================
    public function show($id)
    {
        $product = $this->productService->getById($id);

        if (!$product) {
            return $this->error('المنتج غير موجود', 404);
        }

        if ($product->deleted_at) {
            return $this->error('المنتج محذوف ولا يمكن عرضه', 404);
        }

        return $this->success('تم جلب المنتج بنجاح', $product);
    }

    // ============================
    // 3) إضافة منتج جديد
    // ============================
    public function store(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->create($request->validated());
            return $this->success('تم إنشاء المنتج بنجاح', $product);
        } catch (\Exception $e) {
            return $this->error('حدث خطأ أثناء إنشاء المنتج: ' . $e->getMessage(), 500);
        }
    }

    // ============================
    // رفع الصور
    // ============================
    public function uploadImages(Request $request, $id)
    {
        $request->validate([
            'main_image' => ['nullable', 'image', 'max:4096'],
            'images'     => ['nullable', 'array'],
            'images.*'   => ['image', 'max:4096'],
        ]);

        $product = Product::find($id);

        if (!$product) {
            return $this->error('المنتج غير موجود', 404);
        }

        if ($product->deleted_at) {
            return $this->error('لا يمكن رفع صور لمنتج محذوف', 400);
        }

        $updated = $this->productService->uploadImages($product, $request->all());

        return $this->success('تم رفع الصور بنجاح', $updated);
    }

    // ============================
    // 4) تعديل المنتج
    // ============================
    public function update(UpdateProductRequest $request, Product $product)
    {
        if ($product->deleted_at) {
            return $this->error('لا يمكن تعديل منتج محذوف', 400);
        }

        try {
            $updated = $this->productService->update($product, $request->validated());
            return $this->success('تم تعديل المنتج بنجاح', $updated);
        } catch (\Exception $e) {
            return $this->error('حدث خطأ أثناء التعديل: ' . $e->getMessage(), 500);
        }
    }

    // ============================
    // 5) حذف المنتج (Soft Delete)
    // ============================
    public function destroy(Product $product)
    {
        if ($product->deleted_at) {
            return $this->error('المنتج محذوف مسبقًا', 400);
        }

        $this->productService->delete($product);

        return $this->success('تم حذف المنتج بنجاح');
    }

    // ============================
    // 6) استعادة المنتج
    // ============================
    public function restore($id)
    {
        $product = $this->productService->restore($id);

        if (!$product) {
            return $this->error('لا يوجد منتج محذوف بهذا الرقم', 404);
        }

        return $this->success('تم استعادة المنتج بنجاح', $product);
    }
}
