<?php

namespace App\Listeners;

use App\Events\OrderCreated;   // ← هذا هو الصحيح
use App\Models\User;
use App\Notifications\AdminOrderCreatedNotification;

class SendAdminOrderCreatedNotification
{
    public function handle(OrderCreated $event)
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new AdminOrderCreatedNotification($event->order));
        }
    }
}
