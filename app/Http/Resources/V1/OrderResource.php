<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number'       => $this->order_number,
            'status'             => $this->status,
            'order_type'         => $this->order_type,
            'table_number'       => $this->table_number,
            'address'            => $this->address,
            'delivery_type'      => $this->delivery_type,
            'scheduled_at'       => $this->scheduled_at?->toIso8601String(),
            'subtotal'           => (float) $this->subtotal,
            'delivery_fee'       => $this->delivery_fee !== null ? (float) $this->delivery_fee : null,
            'discount'           => (float) ($this->discount ?? 0),
            'total'              => (float) $this->total,
            'rejection_reason'   => $this->rejection_reason,
            'customer_note'      => $this->customer_note,
            'items'              => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at'         => $this->created_at->toIso8601String(),
        ];
    }
}

