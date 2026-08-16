<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product_name' => $this->product?->name,

            'variant_id'   => $this->variant_id,
            'color'        => $this->variant?->color?->name,
            'size'         => $this->size?->name,

            'quantity'     => $this->quantity,
            'price'        => $this->price,
            'total'        => $this->total,

            // الصور
            'main_image'   => $this->product?->mainImage?->path ?? null,
            'images'       => $this->product?->images->pluck('path'),
        ];
    }
}
