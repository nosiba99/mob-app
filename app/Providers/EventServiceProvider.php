<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// ضيفي ال use statements
use App\Events\OrderAccepted;
use App\Listeners\SendOrderAcceptedNotification;

use App\Events\DeliveryStarted;
use App\Listeners\SendDeliveryStartedNotification;

use App\Events\DeliveryArrived;
use App\Listeners\SendDeliveryArrivedNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // إشعار قبول الطلب
        OrderAccepted::class => [
            SendOrderAcceptedNotification::class,
        ],

        // إشعار خروج المندوب
        DeliveryStarted::class => [
            SendDeliveryStartedNotification::class,
        ],

    
    DeliveryCompleted::class => [
        SendDeliveryCompletedNotification::class,
    ],

    OrderRejected::class => [
        SendOrderRejectedNotification::class,
    ],


    UserRegistered::class => [
        SendAdminUserRegisteredNotification::class,
    ],

    UserLoggedIn::class => [
        SendAdminUserLoggedInNotification::class,
    ],

    OrderCreated::class => [
        SendAdminOrderCreatedNotification::class,
    ],

    OrderRejectedByDelivery::class => [
        SendAdminOrderRejectedNotification::class,
    ],

    OrderAcceptedByDelivery::class => [
        SendAdminOrderAcceptedNotification::class,
    ],

    OrderDelivered::class => [
        SendAdminOrderDeliveredNotification::class,
    ],

    OrderProblem::class => [
        SendAdminOrderProblemNotification::class,
    ],

    AdminNewMessage::class => [
        SendAdminNewMessageNotification::class,
    ],


    DeliveryAssigned::class => [
        SendDeliveryAssignedNotification::class,
    ],

    DeliveryInProgress::class => [
        SendDeliveryInProgressNotification::class,
    ],
];


    

    public function boot(): void
    {
        //
    }
}
