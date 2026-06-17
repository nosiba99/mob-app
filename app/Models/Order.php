<?php // app/Models/Order.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 

class Order extends Model
{ use SoftDeletes;
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'payment_method',
        'address',
        'notes'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
