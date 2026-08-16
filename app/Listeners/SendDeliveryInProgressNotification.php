<?php

namespace App\Listeners;

use App\Events\DeliveryInProgress;
use App\Notifications\DeliveryInProgressNotification;

class SendDeliveryInProgressNotification
{
    public function handle(DeliveryInProgress $event)
    {
        $event->order->user->notify(
            new DeliveryInProgressNotification($event->order)
        );
    }
}
