<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'total_price' => $this->total_price,
            'status'      => $this->status,
            'payment'     => $this->payment_method,
            'address'     => $this->address,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at->format('Y-m-d'),

            'items' => OrderItemResource::collection($this->items),
        ];
    }
}
