<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Models\User;
use App\Notifications\AdminUserLoggedInNotification;

class SendAdminUserLoggedInNotification
{
    public function handle(UserLoggedIn $event)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new AdminUserLoggedInNotification($event->user));
        }
    }
}
