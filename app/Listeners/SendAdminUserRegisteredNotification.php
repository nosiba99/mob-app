<?php

namespace App\Listeners;
use App\Events\UserRegistered;
use App\Models\User;
use App\Notifications\AdminUserRegisteredNotification;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
class SendAdminUserRegisteredNotification
{
    public function handle(UserRegistered $event)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AdminUserRegisteredNotification($event->user));
        }
    }
}
