<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryReturnedNotification extends Notification
{
    use Queueable;

    public function __construct(public $order) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title'    => 'تم إرجاع الطلب',
            'body'     => 'تم إرجاع الطلب رقم ' . $this->order->id . ' من قبل المندوب.',
            'order_id' => $this->order->id,
            'type'     => 'delivery_returned',
        ];
    }
}
