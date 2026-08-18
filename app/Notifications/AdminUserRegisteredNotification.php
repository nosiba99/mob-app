<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminUserRegisteredNotification extends Notification
{
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'مستخدم جديد',
            'body'  => 'تم تسجيل مستخدم جديد: ' . $this->user->first_name . ' ' . $this->user->last_name,
            'type'  => 'user_registered'
        ];
    }
}
