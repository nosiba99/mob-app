<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'status'  => true,
            'message' => 'تم جلب طلبات المستخدم',

            'data' => [
                'items' => OrderResource::collection($this->collection),
            ],

            'pagination' => [
                'current_page' => $this->currentPage(),
                'last_page'    => $this->lastPage(),
                'per_page'     => $this->perPage(),
                'total'        => $this->total(),
            ]
        ];
    }
}
