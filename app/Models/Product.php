<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Warehouse;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductVariant;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'warehouse_id',   // غير مستخدم في نظام الفاريانت
        'stock',          // غير مستخدم لأن المخزون أصبح داخل product_warehouse
        'min_stock',
        'sku',
        'type',           // simple / variant
    ];

    /**
     * علاقة المنتج مع مستودع واحد (غير مستخدمة في نظام الفاريانت)
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * صور المنتج
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * الصورة الرئيسية
     */
    public function mainImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    /**
     * الفاريانت (ألوان + مقاسات + أسعار + مخزون)
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * القسم
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * تقييمات المنتج
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function averageRating()
    {
        return $this->reviews()->avg('rating');
    }

    public function reviewsCount()
    {
        return $this->reviews()->count();
    }

    /**
     * علاقة المنتج مع المستودعات (غير صحيحة في نظام الفاريانت)
     * لأن جدول product_warehouse يحتوي variant_id وليس product_id
     */
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse')->withPivot('stock');
    }

    /**
     * عناصر الطلب التي تحتوي هذا المنتج
     */
    public function orderItems()
    {
        return $this->hasMany(\App\Models\OrderItem::class);
    }
 public function actualStock()
{
    return ProductWarehouse::whereIn('variant_id', $this->variants->pluck('id'))
        ->sum('stock');
}

}
