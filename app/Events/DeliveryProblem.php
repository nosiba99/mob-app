<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class DeliveryProblem
{
    use Dispatchable, SerializesModels;

    public $order;
    public $message;

    public function __construct(Order $order, $message)
    {
        $this->order  = $order;
        $this->message = $message;
    }
}
