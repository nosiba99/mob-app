<?php

namespace App\Listeners;

use App\Events\DeliveryReturned;
use App\Notifications\DeliveryReturnedNotification;
use Illuminate\Support\Facades\Notification;

class SendDeliveryReturnedNotification
{
    public function handle(DeliveryReturned $event)
    {
        Notification::send(
            $event->order->user,
            new DeliveryReturnedNotification($event->order)
        );
    }
}
