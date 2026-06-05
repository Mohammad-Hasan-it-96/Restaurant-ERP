<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isWeighted  = !empty($this->weight_name) && $this->weight_value_kg > 0;
        $pricePerKg  = $isWeighted
            ? round((float) $this->product_price / (float) $this->weight_value_kg, 2)
            : null;

        return [
            'product_id'      => $this->product_id,
            'product_name'    => $this->product_name,
            'product_price'   => (float) $this->product_price,
            'quantity'        => $this->quantity,
            'total'           => (float) $this->total,
            // Weight-based fields (null for normal products)
            'weight_name'     => $this->weight_name,
            'weight_value_kg' => $this->weight_value_kg !== null ? (float) $this->weight_value_kg : null,
            'price_per_kg'    => $pricePerKg,
        ];
    }
}

