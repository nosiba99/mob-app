<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    // هل الرمز صالح؟
    public function isValid(): bool
    {
        return is_null($this->used_at)
            && $this->expires_at->isFuture();
    }

    // تعليم الرمز كمستخدم
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    // علاقة الـ OTP مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
