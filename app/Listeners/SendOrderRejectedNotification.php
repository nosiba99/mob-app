<?php

namespace App\Listeners;

use App\Events\OrderRejected;
use App\Notifications\OrderRejectedNotification;
use Illuminate\Support\Facades\Notification;

class SendOrderRejectedNotification
{
    public function handle(OrderRejected $event)
    {
        Notification::send(
            $event->order->user,
            new OrderRejectedNotification($event->order)
        );
    }
}
