<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

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
    'is_active',
];
const ROLE_USER = 'user';
const ROLE_ADMIN = 'admin';
const ROLE_DELIVERY = 'delivery';



    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'deleted_at'        => 'datetime',
         'is_banned'        => 'boolean',
    ];

    // ============================
    // 🔵 علاقات المستخدم
    // ============================

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

    // ============================
    // 🔵 دوال مساعدة
    // ============================

    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }
    public function area()
{
    return $this->belongsTo(Area::class, 'area_id');
}

}
