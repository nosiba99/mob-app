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
  


public function decreaseStock(ProductVariant $variant, int $qty, int $warehouseId)
{
    // 1) خصم من مخزون المستودع
    $pw = \App\Models\ProductWarehouse::where('variant_id', $variant->id)
        ->where('warehouse_id', $warehouseId)
        ->first();

    if (!$pw) {
        throw new \Exception('هذا المنتج غير موجود في مستودع المنطقة');
    }

    if ($pw->stock < $qty) {
        throw new \Exception('الكمية المطلوبة غير متوفرة في المستودع');
    }

    $pw->stock -= $qty;
    $pw->save();

    // 2) خصم من مخزون الفاريانت العام
    if ($variant->stock < $qty) {
        throw new \Exception('مخزون الفاريانت غير كافٍ');
    }

    $variant->stock -= $qty;
    $variant->save();
}

public function increaseStock(ProductVariant $variant, int $qty, int $warehouseId)
{
    $pw = \App\Models\ProductWarehouse::where('variant_id', $variant->id)
        ->where('warehouse_id', $warehouseId)
        ->first();

    if (!$pw) {
        throw new \Exception('لا يمكن زيادة المخزون — المستودع غير موجود');
    }

    $pw->stock += $qty;
    $pw->save();

    $variant->stock += $qty;
    $variant->save();
}

}
