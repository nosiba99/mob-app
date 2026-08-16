<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AdminNewMessageNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'رسالة جديدة',
            'body'  => 'وصلتك رسالة جديدة من ' . $this->message->sender->name,
            'type'  => 'new_message'
        ];
    }
}
