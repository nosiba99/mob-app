<?php

namespace App\Listeners;

use App\Events\MessageToAdmin;
use App\Models\User;
use App\Notifications\MessageToAdminNotification;

class SendMessageToAdminNotification
{
    public function handle(MessageToAdmin $event)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(
                new MessageToAdminNotification($event->user, $event->message)
            );
        }
    }
}
