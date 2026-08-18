<?php

namespace App\Listeners;

use App\Events\DeliveryArrived;
use App\Notifications\DeliveryArrivedNotification;
use Illuminate\Support\Facades\Notification;

class SendDeliveryArrivedNotification
{
    public function handle(DeliveryArrived $event)
    {
        Notification::send(
            $event->order->user,
            new DeliveryArrivedNotification($event->order)
        );
    }
}
