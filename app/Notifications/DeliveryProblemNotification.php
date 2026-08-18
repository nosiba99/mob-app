<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryProblemNotification extends Notification
{
    use Queueable;

    public function __construct(public $order, public $message) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title'    => 'مشكلة أثناء التوصيل',
            'body'     => 'حدثت مشكلة أثناء توصيل الطلب رقم ' . $this->order->id . ': ' . $this->message,
            'order_id' => $this->order->id,
            'type'     => 'delivery_problem',
        ];
    }
}
