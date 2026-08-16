<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryStartedNotification extends Notification
{
    use Queueable;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'المندوب في الطريق',
            'body'  => 'المندوب الآن في الطريق لتسليم طلبك رقم ' . $this->order->id,
            'order_id' => $this->order->id,
        ];
    }
}
