<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->display_name,      // locale-aware accessor
            'name_ar'        => $this->name_ar,
            'name_en'        => $this->name_en,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'is_weight_based' => (bool) $this->is_weight_based,
            'price'           => $this->is_weight_based ? null : (float) $this->price,
            'discount_price'  => (!$this->is_weight_based && $this->discount_price) ? (float) $this->discount_price : null,
            'effective_price' => $this->is_weight_based ? null : (float) $this->effective_price,
            'price_per_kg'    => $this->is_weight_based ? (float) $this->price_per_kg : null,
            'weights'         => $this->is_weight_based ? $this->whenLoaded('weights', fn () =>
                $this->weights->map(fn ($w) => [
                    'id'       => $w->id,
                    'name'     => $w->name,
                    'value_kg' => (float) $w->value_kg,
                ])->values()
            ) : null,
            'image'          => $this->image ? asset('storage/' . $this->image) : null,
            'category_id'    => $this->category_id,
            'category'       => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->display_name,
            ]),
            'is_available'   => (bool) $this->is_available,
            'is_featured'    => (bool) $this->is_featured,
            'sort_order'     => $this->sort_order,
            'slug'           => $this->slug,
        ];
    }
}

