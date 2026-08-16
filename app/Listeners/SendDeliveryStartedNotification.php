<?php

namespace App\Listeners;

use App\Events\DeliveryStarted;
use App\Notifications\DeliveryStartedNotification;
use Illuminate\Support\Facades\Notification;

class SendDeliveryStartedNotification
{
    public function handle(DeliveryStarted $event)
    {
        $order = $event->order;

        Notification::send($order->user, new DeliveryStartedNotification($order));
    }
}
