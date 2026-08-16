<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeliveryInProgressNotification extends Notification
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
            'title' => 'مندوبك في الطريق',
            'body'  => 'المندوب الآن في الطريق لتسليم الطلب رقم ' . $this->order->id,
            'order_id' => $this->order->id,
        ];
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
