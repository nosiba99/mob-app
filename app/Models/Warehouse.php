<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Area;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarehouse;

class Warehouse extends Model
{
    protected $fillable = ['name', 'type'];

    /**
     * المستودع يخدم عدة مناطق
     */
 public function areas()
{
    return $this->belongsToMany(Area::class, 'warehouse_area');
}


    /**
     * المندوبين التابعين للمستودع
     */
    public function deliveries()
    {
        return $this->hasMany(User::class)->where('role', 'delivery');
    }

    /**
     * المنتجات داخل المستودع (غير دقيقة لأن الجدول يحتوي variant_id وليس product_id)
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_warehouse')->withPivot('stock');
    }

    /**
     * الفاريانت داخل المستودع (العلاقة الصحيحة)
     */
    public function variants()
    {
        return $this->hasMany(ProductWarehouse::class);
    }

    /**
     * الطلبات التي يجهزها المستودع
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
