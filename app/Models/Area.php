<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Order;

class Area extends Model
{
    protected $fillable = ['name'];

    /**
     * المنطقة ممكن يخدمها عدة مستودعات (Many-to-Many)
     */
   

    /**
     * المندوبين التابعين للمنطقة
     */
    public function deliveries()
    {
        return $this->hasMany(User::class, 'area_id')->where('role', 'delivery');
    }

    /**
     * الطلبات التابعة للمنطقة
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
  public function warehouses()
{
    return $this->belongsToMany(Warehouse::class, 'warehouse_area');
}



}
