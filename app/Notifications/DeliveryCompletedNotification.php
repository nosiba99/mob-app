<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryCompletedNotification extends Notification
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
            'title' => 'تم تسليم الطلب',
            'body'  => 'تم تسليم طلبك رقم ' . $this->order->id . ' بنجاح.',
            'order_id' => $this->order->id,
        ];
    }
}
