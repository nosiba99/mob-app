<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class MessageToAdmin
{
    use Dispatchable, SerializesModels;

    public $user;
    public $message;

    public function __construct(User $user, $message)
    {
        $this->user    = $user;
        $this->message = $message;
    }
}
