<?php

namespace App\Listeners;

use App\Events\DeliveryAssigned;
use App\Notifications\DeliveryAssignedNotification;

class SendDeliveryAssignedNotification
{
    public function handle(DeliveryAssigned $event)
    {
        $delivery = $event->delivery;

        // إرسال إشعار للمندوب
        $delivery->notify(new DeliveryAssignedNotification($event->order));
    }
}
