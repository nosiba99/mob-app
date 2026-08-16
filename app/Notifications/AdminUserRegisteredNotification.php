<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AdminUserRegisteredNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'مستخدم جديد',
            'body'  => 'تم تسجيل مستخدم جديد: ' . $this->user->name,
            'type'  => 'user_registered'
        ];
    }
}
