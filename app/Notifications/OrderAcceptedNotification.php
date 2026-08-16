<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderAcceptedNotification extends Notification
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
            'title' => 'تم قبول الطلب',
            'body'  => 'تم قبول طلبك رقم ' . $this->order->id . ' من قبل المندوب.',
            'order_id' => $this->order->id,
        ];
    }
}
