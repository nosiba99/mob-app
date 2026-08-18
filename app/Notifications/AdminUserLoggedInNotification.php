<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminUserLoggedInNotification extends Notification
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
            'title' => 'تسجيل دخول جديد',
            'body'  => 'قام المستخدم ' . $this->user->first_name . ' ' . $this->user->last_name . ' بتسجيل الدخول.',
            'type'  => 'user_logged_in'
        ];
    }
}
