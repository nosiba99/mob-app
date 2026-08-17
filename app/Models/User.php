<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

use App\Models\Area;
use App\Models\Warehouse;
use App\Models\Order;
use App\Models\Otp;
use App\Models\Review;
use App\Models\Wishlist;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, SoftDeletes, Notifiable;

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'phone',
        'password',
        'role',
        'area_id',
        'warehouse_id',   // مهم جدًا للمندوبين
        'is_active',
        'is_banned',
        'is_available',
        'active_orders',
        'device_token',
        'wallet_balance',


    ];

    const ROLE_USER     = 'user';
    const ROLE_ADMIN    = 'admin';
    const ROLE_DELIVERY = 'delivery';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'deleted_at'        => 'datetime',
        'is_banned'         => 'boolean',
    ];

    // علاقات المستخدم
    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * منطقة المستخدم
     */
    public function area()
{
    return $this->belongsTo(Area::class, 'area_id');
}
public function getWarehouseIdAttribute()
{
    return $this->area ? $this->area->warehouse_id : null;
}



    /**
     * المستودع التابع له المستخدم (إذا كان مندوبًا)
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * الطلبات التي أنشأها المستخدم
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * الطلبات التي يوصّلها المندوب
     */
    public function deliveryOrders()
    {
        return $this->hasMany(Order::class, 'delivery_id');
    }
}
