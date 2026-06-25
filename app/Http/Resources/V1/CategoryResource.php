<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,          // locale-aware via getNameAttribute()
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'sort_order' => $this->sort_order,
            'parent_id' => $this->parent_id,
        ];
    }
}
