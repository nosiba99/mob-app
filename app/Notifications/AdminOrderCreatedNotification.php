<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminOrderCreatedNotification extends Notification
{
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
            'title' => 'طلب جديد',
            'body'  => 'تم إنشاء طلب جديد برقم #' . $this->order->id,
            'type'  => 'order_created'
        ];
    }
}
