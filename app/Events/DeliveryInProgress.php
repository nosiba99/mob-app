<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryInProgress
{
    use Dispatchable, SerializesModels;

    public $order;
    public $delivery;

    public function __construct($order, $delivery)
    {
        $this->order = $order;
        $this->delivery = $delivery;
    }
}
