<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductController extends Controller
{
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
        $products = Product::with(['images', 'variants.color', 'variants.size'])
            ->latest()
            ->get();

        return $this->success('تم جلب المنتجات بنجاح', $products);
    }

    // ============================
    // 2) عرض منتج واحد
    // ============================
    public function show($id)
    {
        $product = Product::with([
            'variants.color',
            'variants.size',
            'images',
            'category'
        ])->find($id);

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
        DB::beginTransaction();

        try {
            $product = Product::create($request->only([
                'name', 'description', 'price', 'category_id'
            ]));

            foreach ($request->variants as $variant) {
                foreach ($variant['sizes'] as $size) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'color_id'   => $variant['color_id'],
                        'size_id'    => $size['size_id'],
                        'stock'      => $size['stock'],
                    ]);
                }
            }

            DB::commit();

            return $this->success('تم إنشاء المنتج بنجاح', $product);

        } catch (\Exception $e) {
            DB::rollBack();
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

        if ($request->hasFile('main_image')) {
            ProductImage::where('product_id', $product->id)
                ->where('is_main', true)
                ->delete();

            $path = $request->file('main_image')->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'path'       => $path,
                'is_main'    => true,
            ]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $path = $img->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $path,
                    'is_main'    => false,
                ]);
            }
        }

        return $this->success('تم رفع الصور بنجاح', $product->load('images'));
    }

    // ============================
    // 4) تعديل المنتج
    // ============================
    public function update(UpdateProductRequest $request, Product $product)
    {
        if ($product->deleted_at) {
            return $this->error('لا يمكن تعديل منتج محذوف', 400);
        }

        DB::beginTransaction();

        try {
            $product->update($request->only([
                'name', 'description', 'price', 'category_id'
            ]));

            if ($request->hasFile('main_image')) {
                ProductImage::where('product_id', $product->id)
                    ->where('is_main', true)
                    ->delete();

                $path = $request->file('main_image')->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $path,
                    'is_main'    => true,
                ]);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $img) {
                    $path = $img->store('products', 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'path'       => $path,
                        'is_main'    => false,
                    ]);
                }
            }

            if ($request->has('variants')) {
                ProductVariant::where('product_id', $product->id)->delete();

                foreach ($request->variants as $variant) {
                    foreach ($variant['sizes'] as $size) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'color_id'   => $variant['color_id'],
                            'size_id'    => $size['size_id'],
                            'stock'      => $size['stock'],
                        ]);
                    }
                }
            }

            DB::commit();
            $product->refresh();

            return $this->success('تم تعديل المنتج بنجاح', $product->load(['images', 'variants.color', 'variants.size']));

        } catch (\Exception $e) {
            DB::rollBack();
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

        $product->delete();

        return $this->success('تم حذف المنتج بنجاح');
    }

    // ============================
    // 6) استعادة المنتج
    // ============================
    public function restore($id)
    {
        $product = Product::onlyTrashed()->find($id);

        if (!$product) {
            return $this->error('لا يوجد منتج محذوف بهذا الرقم', 404);
        }

        $product->restore();

        return $this->success('تم استعادة المنتج بنجاح', $product);
    }
}
