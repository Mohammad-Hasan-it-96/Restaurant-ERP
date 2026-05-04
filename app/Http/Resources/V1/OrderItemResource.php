<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id'    => $this->product_id,
            'product_name'  => $this->product_name,
            'product_price' => (float) $this->product_price,
            'quantity'      => $this->quantity,
            'total'         => (float) $this->total,
        ];
    }
}

