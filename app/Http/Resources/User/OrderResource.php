<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'total_price'   => $this->total_price,
            'status'        => $this->status,
            'payment'       => $this->payment_method,
            'address'       => $this->address,
            'notes'         => $this->notes,
            'created_at'    => $this->created_at->format('Y-m-d'),

            // المنطقة
            'area' => [
                'id'   => $this->area?->id,
                'name' => $this->area?->name,
            ],

            // المستودع
            'warehouse' => [
                'id'   => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],

            // المندوب
            'delivery' => $this->delivery ? [
                'id'    => $this->delivery->id,
                'name'  => $this->delivery->name,
                'phone' => $this->delivery->phone,
            ] : null,

            // عناصر الطلب
            'items' => OrderItemResource::collection($this->items),
        ];
    }
}
