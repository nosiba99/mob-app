<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class OrderRejectedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'تم رفض الطلب',
            'body'  => 'تم رفض طلبك رقم ' . $this->order->id . ' وسيتم إعادة تعيينه.',
            'order_id' => $this->order->id,
        ];
    }
}
