<?php

namespace App\Listeners;

use App\Events\OrderAccepted;
use App\Notifications\OrderAcceptedNotification;
use Illuminate\Support\Facades\Notification;

class SendOrderAcceptedNotification
{
    public function handle(OrderAccepted $event)
    {
        $order = $event->order;

        Notification::send($order->user, new OrderAcceptedNotification($order));
    }
}
