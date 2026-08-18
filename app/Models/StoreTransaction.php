<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'type',
        'description',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
