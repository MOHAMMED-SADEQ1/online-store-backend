<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id'          => $this->id,
            'name'        => $this->{'name_' . $locale},
            'slug'        => $this->slug,
            'description' => $this->{'description_' . $locale},
            'image'       => $this->image ? url($this->image) : null,
            'parent_id'   => $this->parent_id,
            'display_order' => $this->display_order,
            'children'    => self::collection($this->whenLoaded('children')),
            'products_count' => $this->whenHas('products_count', fn() => (int) $this->products_count),
        ];
    }
}
