<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'color_id',
        'size_id',
        'stock',   // يبقى كما هو حسب نظامك الحالي
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function warehouses()
    {
        return $this->hasMany(ProductWarehouse::class, 'variant_id');
    }

    /**
     * مخزون الفاريانت داخل مستودع معيّن (اختياري)
     */
    public function warehouseStock($warehouseId)
    {
        return $this->warehouses()->where('warehouse_id', $warehouseId)->first();
    }




}
