<?php

namespace App\Listeners;

use App\Events\DeliveryProblem;
use App\Notifications\DeliveryProblemNotification;
use Illuminate\Support\Facades\Notification;

class SendDeliveryProblemNotification
{
    public function handle(DeliveryProblem $event)
    {
        Notification::send(
            $event->order->user,
            new DeliveryProblemNotification($event->order, $event->message)
        );
    }
}
