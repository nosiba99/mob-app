<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAdminOrderAcceptedNotification

{
    public function handle(OrderAccepted $event)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AdminOrderAcceptedNotification($event->user));
        }
    }
}
