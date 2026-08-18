<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryArrivedNotification extends Notification
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
            'title' => 'المندوب وصل',
            'body'  => 'المندوب وصل لمكان التسليم للطلب رقم ' . $this->order->id,
            'order_id' => $this->order->id,
            'type' => 'delivery_arrived'
        ];
    }
}
