<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\User;
use App\Models\Area;
use App\Models\OrderItem;
use App\Models\Warehouse;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'delivery_id',
        'area_id',
        'warehouse_id',
        'status',
        'total_price',
        'shipping_cost',
        'discount',
        'payment_method',
        'address',
        'notes',
        'lat',
        'lng',
    ];

    const STATUS_PENDING         = 'pending';
    const STATUS_ASSIGNED        = 'assigned';
    const STATUS_ON_THE_WAY      = 'on_the_way';
    const STATUS_DELIVERED       = 'delivered';
    const STATUS_CANCELED        = 'canceled';
    const STATUS_WAITING_DELIVERY = 'waiting_delivery';
    const STATUS_WAITING_STOCK   = 'waiting_stock';
    const STATUS_RETURNED        = 'returned';

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function delivery()
    {
        return $this->belongsTo(User::class, 'delivery_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
