<?php

namespace App\Http\Resources\V1;

use App\Support\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Drop only strictly-null values to shrink the payload (e.g. discount_price,
        // weights/price_per_kg on non-weight products, empty descriptions). Using
        // `!== null` — NOT array_filter's default — so `false` (is_available) and
        // `0`/`0.0` (a free product) are preserved. whenLoaded()'s MissingValue
        // entries are stripped by the framework afterwards.
        return array_filter($this->fields(), fn ($v) => $v !== null);
    }

    private function fields(): array
    {
        // When the weight-products feature is off, a weight-based product is
        // exposed as a normal, fixed-price product: no weight fields leak, and
        // pricing falls back to the base `price`. This is the API enforcement
        // layer for `products.weight_products`.
        $isWeightBased = (bool) $this->is_weight_based && Feature::enabled('products.weight_products');
        $optionsEnabled = Feature::enabled('products.options');

        return [
            'id' => $this->id,
            'name' => $this->display_name,      // locale-aware accessor
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description_ar' => $this->description_ar ?? $this->details,
            'description_en' => $this->description_en,
            'is_weight_based' => $isWeightBased,
            'price' => $isWeightBased ? null : (float) $this->price,
            'discount_price' => (! $isWeightBased && $this->discount_price) ? (float) $this->discount_price : null,
            'effective_price' => $isWeightBased ? null : (float) $this->effective_price,
            'price_per_kg' => $isWeightBased ? (float) $this->price_per_kg : null,
            'weights' => $isWeightBased ? $this->whenLoaded('weights', fn () => $this->weights->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'value_kg' => (float) $w->value_kg,
            ])->values()
            ) : null,
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->display_name,
            ]),
            'is_available' => (bool) $this->is_available,
            'is_featured' => (bool) $this->is_featured,
            // Options are stripped entirely when the `products.options` feature is off.
            'options' => $optionsEnabled ? $this->whenLoaded('optionValues', fn () => $this->optionValues
                ->groupBy(fn ($v) => $v->option->name)
                ->map(fn ($values, $name) => [
                    'name' => $name,
                    'values' => $values->map(fn ($v) => [
                        'id' => $v->id,
                        'name' => $v->name,
                    ])->values(),
                ])
                ->values()
            ) : null,
        ];
    }
}
