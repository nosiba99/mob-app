<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AdminOrderProblemNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'مشكلة في الطلب',
            'body'  => 'هناك مشكلة في طلب رقم ' . $this->order->id,
            'type'  => 'order_problem'
        ];
    }
}
