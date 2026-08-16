<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AdminOrderDeliveredNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'تم التسليم',
            'body'  => 'تم تسليم طلب رقم ' . $this->order->id,
            'type'  => 'order_delivered'
        ];
    }
}
