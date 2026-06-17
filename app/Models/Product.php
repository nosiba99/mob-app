<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
    ];

    // صور المنتج
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    // الصورة الرئيسية فقط
    public function mainImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    // الفاريانت (ألوان + مقاسات + كميات)
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // القسم
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
