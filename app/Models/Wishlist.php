<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    protected $casts = [
        'user_id'    => 'integer',
        'product_id' => 'integer',
    ];

    // علاقة الـ Wishlist مع المنتج
    public function product()
    {
        return $this->belongsTo(Product::class)
            ->with('mainImage'); // حتى يرجع الصورة الرئيسية تلقائياً
    }

    // علاقة الـ Wishlist مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class)
            ->select('id', 'name');
    }
}
