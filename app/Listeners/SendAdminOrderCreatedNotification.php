<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendAdminOrderCreatedNotification
{
    public function handle(OrderCreated $event)
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new AdminOrderCreatedNotification($event->user));
        }
    }
}

