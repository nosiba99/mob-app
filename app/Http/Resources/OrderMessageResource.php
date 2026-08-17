<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderMessageResource extends JsonResource
{
    public function toArray($request)
    {
        $currentUserId = $request->user()->id;

        return [
            'id'   => $this->id,
            'text' => $this->message,
            'time' => $this->created_at->format('H:i'),
            'type' => $this->sender_id == $currentUserId ? 'outgoing' : 'incoming'
        ];
    }
}
