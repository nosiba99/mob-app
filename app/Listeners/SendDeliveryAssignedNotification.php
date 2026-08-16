<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class SendDeliveryAssignedNotification
{
    public function handle(DeliveryAssigned $event)
    {
        $event->order->delivery->notify(
            new DeliveryAssignedNotification($event->order)
        );
    }
}
