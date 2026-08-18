<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MessageToAdminNotification extends Notification
{
    use Queueable;

    public function __construct(public $user, public $message) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title'   => 'رسالة جديدة من مستخدم',
            'body'    => 'المستخدم ' . $this->user->first_name . ' ' . $this->user->last_name . ' أرسل رسالة: ' . $this->message,
            'user_id' => $this->user->id,
            'type'    => 'message_to_admin',
        ];
    }
}
