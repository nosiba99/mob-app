<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class AdminUserLoggedInNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'تسجيل دخول',
            'body'  => 'قام المستخدم ' . $this->user->name . ' بتسجيل الدخول.',
            'type'  => 'user_logged_in'
        ];
    }
}
