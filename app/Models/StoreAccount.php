<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreAccount extends Model
{
    protected $fillable = ['balance'];

    /**
     * فيه صف واحد بس دايماً بهاد الجدول (id = 1)
     */
    public static function account(): self
    {
        return self::firstOrCreate(['id' => 1], ['balance' => 0]);
    }

    /**
     * إضافة مبلغ فاتورة طلب لحساب المتجر + تسجيل الحركة
     */
    public static function credit(float $amount, ?int $orderId = null, string $description = 'تحصيل فاتورة طلب'): self
    {
        $account = self::account();
        $account->increment('balance', $amount);

        StoreTransaction::create([
            'order_id'    => $orderId,
            'amount'      => $amount,
            'type'        => 'order_invoice',
            'description' => $description,
        ]);

        return $account->fresh();
    }
}
