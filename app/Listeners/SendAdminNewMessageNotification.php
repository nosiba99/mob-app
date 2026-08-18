<?php

namespace App\Listeners;

use App\Events\AdminNewMessage;
use App\Models\User;
use App\Notifications\AdminNewMessageNotification;

class SendAdminNewMessageNotification
{
    public function handle(AdminNewMessage $event)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new AdminNewMessageNotification($event->message));
        }
    }
}
