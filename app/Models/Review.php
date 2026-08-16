<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'product_id', 'rating', 'comment'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }



    // علاقة المراجعة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class)
            ->select('id', 'name', 'email');
    }

   
}
