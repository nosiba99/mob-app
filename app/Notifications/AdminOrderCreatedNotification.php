<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AdminOrderCreatedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'طلب جديد',
            'body'  => 'قام المستخدم ' . $this->order->user->name . ' بإنشاء طلب رقم ' . $this->order->id,
            'type'  => 'order_created'
        ];
    }
}
