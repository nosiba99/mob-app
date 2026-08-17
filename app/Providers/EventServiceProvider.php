<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// ضيفي ال use statements
use App\Events\OrderAccepted;
use App\Events\DeliveryStarted;
use App\Events\DeliveryArrived;
use App\Events\DeliveryCompleted;
use App\Events\OrderRejected;
use App\Events\UserRegistered;
use App\Events\UserLoggedIn;
use App\Events\OrderCreated;
use App\Events\OrderRejectedByDelivery;
use App\Events\OrderAcceptedByDelivery;
use App\Events\OrderDelivered;
use App\Events\OrderProblem;
use App\Events\AdminNewMessage;
use App\Events\DeliveryAssigned;
use App\Events\DeliveryInProgress;

use App\Listeners\SendDeliveryArrivedNotification;
use App\Listeners\SendDeliveryStartedNotification;
use App\Listeners\SendDeliveryCompletedNotification;
use App\Listeners\SendOrderRejectedNotification;
use App\Listeners\SendAdminUserRegisteredNotification;
use App\Listeners\SendAdminUserLoggedInNotification;
use App\Listeners\SendAdminOrderCreatedNotification;
use App\Listeners\SendAdminOrderRejectedNotification;
use App\Listeners\SendAdminOrderAcceptedNotification;
use App\Listeners\SendAdminOrderDeliveredNotification;
use App\Listeners\SendAdminOrderProblemNotification;
use App\Listeners\SendAdminNewMessageNotification;
use App\Listeners\SendDeliveryAssignedNotification;
use App\Listeners\SendDeliveryInProgressNotification;

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
