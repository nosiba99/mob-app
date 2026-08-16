<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    // ─────────────────────────────
    // جلب كل المنتجات
    // ─────────────────────────────
    public function getAll()
    {
        return Product::with(['images', 'variants.color', 'variants.size'])
            ->latest()
            ->get();
    }

    // ─────────────────────────────
    // جلب منتج واحد
    // ─────────────────────────────
    public function getById($id)
    {
        return Product::with([
            'variants.color',
            'variants.size',
            'images',
            'category'
        ])->find($id);
    }

    // ─────────────────────────────
    // إنشاء منتج جديد
    // ─────────────────────────────
public function create(array $data)
{
    return DB::transaction(function () use ($data) {

        // إنشاء المنتج
        $product = Product::create([
            'name'        => $data['name'],
            'description' => $data['description'],
            'price'       => $data['price'],
            'category_id' => $data['category_id'],
            'stock'       => 0, // مؤقت
        ]);

        // إنشاء الفاريانتات
        foreach ($data['variants'] as $variant) {
            foreach ($variant['sizes'] as $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'color_id'   => $variant['color_id'],
                    'size_id'    => $size['size_id'],
                    'stock'      => $size['stock'],
                    'price'      => $size['price'],
                ]);
            }
        }

        // حساب المخزون الفعلي للمنتج
        $totalStock = ProductVariant::where('product_id', $product->id)->sum('stock');

        // تخزينه داخل جدول المنتجات
        $product->update([
            'stock' => $totalStock
        ]);

        return $product;
    });
}



    // ─────────────────────────────
    // رفع الصور
    // ─────────────────────────────
    public function uploadImages(Product $product, array $data)
    {
        // الصورة الرئيسية
        if (isset($data['main_image'])) {

            ProductImage::where('product_id', $product->id)
                ->where('is_main', true)
                ->delete();

            $path = $data['main_image']->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'path'       => $path,
                'is_main'    => true,
            ]);
        }

        // الصور الإضافية
        if (isset($data['images'])) {
            foreach ($data['images'] as $img) {
                $path = $img->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $path,
                    'is_main'    => false,
                ]);
            }
        }

        return $product->load('images');
    }

    // ─────────────────────────────
    // تعديل المنتج
    // ─────────────────────────────
    public function update(Product $product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {

            $product->update([
                'name'        => $data['name'],
                'description' => $data['description'],
                'price'       => $data['price'],
                'category_id' => $data['category_id'],
            ]);

            // تعديل الصور
            if (isset($data['main_image'])) {
                ProductImage::where('product_id', $product->id)
                    ->where('is_main', true)
                    ->delete();

                $path = $data['main_image']->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $path,
                    'is_main'    => true,
                ]);
            }

            if (isset($data['images'])) {
                foreach ($data['images'] as $img) {
                    $path = $img->store('products', 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'path'       => $path,
                        'is_main'    => false,
                    ]);
                }
            }

            // تعديل الفاريانت
            if (isset($data['variants'])) {

                ProductVariant::where('product_id', $product->id)->delete();

                foreach ($data['variants'] as $variant) {
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

            return $product->fresh(['images', 'variants.color', 'variants.size']);
        });
    }

    // ─────────────────────────────
    // حذف منتج (Soft Delete)
    // ─────────────────────────────
    public function delete(Product $product)
    {
        $product->delete();
        return true;
    }

    // ─────────────────────────────
    // استعادة منتج
    // ─────────────────────────────
    public function restore($id)
    {
        $product = Product::onlyTrashed()->find($id);

        if (!$product) {
            return false;
        }

        $product->restore();
        return $product;
    }
   public function decreaseStock(ProductVariant $variant, int $qty)
{
    // نقص من الفاريانت فقط
    $variant->decrement('stock', $qty);

    // ممنوع تنقصي من المنتج الأساسي
    // لأنه يسبب قيم سالبة ويخرب النظام
}

public function increaseStock(ProductVariant $variant, int $qty)
{
    // زيدي مخزون الفاريانت فقط
    $variant->increment('stock', $qty);

    // لا تزيدي مخزون المنتج الأساسي
}


}
