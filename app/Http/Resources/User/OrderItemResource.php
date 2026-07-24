<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'       => $this->id,
            'quantity' => $this->quantity,
            'price'    => $this->price,

            'product' => [
                'id'    => $this->product->id,
                'name'  => $this->product->name,
                'image' => $this->product->mainImage?->image_url,
            ],

            'variant' => $this->variant ? [
                'color' => $this->variant->color?->name,
                'size'  => $this->variant->size?->name,
            ] : null,
        ];
    }
}
