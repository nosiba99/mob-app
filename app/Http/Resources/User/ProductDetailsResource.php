<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,

            'category' => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ],

            'images' => $this->images->map(fn($img) => $img->image_url),

            'variants' => $this->variants->map(function ($variant) {
                return [
                    'id'    => $variant->id,
                    'color' => $variant->color?->name,
                    'size'  => $variant->size?->name,
                    'stock' => $variant->stock,
                ];
            }),
        ];
    }
}
