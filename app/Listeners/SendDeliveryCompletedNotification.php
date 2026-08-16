<?php

namespace App\Listeners;

use App\Events\DeliveryCompleted;
use App\Notifications\DeliveryCompletedNotification;
use Illuminate\Support\Facades\Notification;

class SendDeliveryCompletedNotification
{
    public function handle(DeliveryCompleted $event)
    {
        $order = $event->order;

        Notification::send($order->user, new DeliveryCompletedNotification($order));
    }
}
