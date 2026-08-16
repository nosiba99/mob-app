<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAdminNewMessageNotification
{
    public function handle(AdminNewMessage $event)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AdminNewMessageNotification($event->user));
        }
    }
}
