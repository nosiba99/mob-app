<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductWarehouse extends Model
{
    protected $table = 'product_warehouse';

    protected $fillable = [
        'variant_id',
        'warehouse_id',
        'stock',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * المنتج الخاص بالفاريانت (اختياري – لا يغيّر النظام)
     */
    public function product()
    {
        return $this->variant?->product;
    }
}
