<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'delivery_id',
        'title',
        'message',
        'is_read'
    ];
}
