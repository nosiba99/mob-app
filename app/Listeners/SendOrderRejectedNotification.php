<?php

namespace App\Listeners;

use App\Events\OrderAccepted;
use App\Notifications\OrderAcceptedNotification;
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
