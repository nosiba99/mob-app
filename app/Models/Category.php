<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'image',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // لإرجاع رابط الصورة كامل
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
}
