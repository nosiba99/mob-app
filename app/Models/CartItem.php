<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ProductVariant;

class CartItem extends Model
{
    protected $fillable = [
    'user_id',
    'product_id',
    'variant_id',
    'size_id',
    'quantity'
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    

    /**
     * المنتج الخاص بالفاريانت
     */
    //public function product()
    //{
    //    return $this->variant?->product;
    //}

    /**
     * مخزون الفاريانت داخل مستودع معيّن (اختياري)
     */
    public function warehouseStock($warehouseId)
    {
        return $this->variant?->warehouseStock($warehouseId);
    }
    public function variant()
{
    return $this->belongsTo(ProductVariant::class);
}

public function product()
{
    return $this->belongsTo(Product::class, 'product_id');
}

public function size()
{
    return $this->belongsTo(Size::class, 'size_id');
}

}
