<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name_ar'              => $this->name_ar,
            'name_en'              => $this->name_en,
            'slug'                 => $this->slug,
            'description_ar'       => $this->description_ar,
            'description_en'       => $this->description_en,
            'sku'                  => $this->sku,
            'regular_price'        => (float) $this->regular_price,
            'sale_price'           => (float) $this->sale_price,
            'cost_price'           => (float) $this->cost_price,
            'tax_rate_id'          => $this->tax_rate_id,
            'quantity_in_stock'    => $this->quantity_in_stock,
            'low_stock_threshold'  => $this->low_stock_threshold,
            'weight'               => (float) $this->weight,
            'dimensions'           => $this->dimensions,
            'main_image'           => $this->main_image
                ? (str_starts_with($this->main_image, 'http')
                    ? $this->main_image
                    : url('storage/' . $this->main_image))
                : null,
            'is_active'            => $this->is_active,
            'is_featured'          => $this->is_featured,
            'is_returnable'        => $this->is_returnable,
            'is_exchangeable'      => $this->is_exchangeable,
            'return_period_days'   => $this->return_period_days,
            'is_cancellable'       => $this->is_cancellable,
            'categories'           => $this->whenLoaded('categories'),
            'tags'                 => $this->whenLoaded('tags'),
            'attributes'           => $this->whenLoaded('attributes', fn() => $this->attributes->map(fn($a) => [
                'id'             => $a->id,
                'name_ar'        => $a->name_ar,
                'name_en'        => $a->name_en,
                'attribute_type' => $a->attribute_type,
                'pivot'          => [
                    'is_variation'  => (bool) $a->pivot->is_variation,
                    'display_order' => (int) $a->pivot->display_order,
                ],
            ])),
            'variants'             => AdminVariantResource::collection($this->whenLoaded('variants')),
            'images'               => $this->whenLoaded('images', fn() => $this->images->map(fn($img) => [
                'id'             => $img->id,
                'product_id'     => $img->product_id,
                'variant_id'     => $img->variant_id,
                'image_url'      => url($img->image_url ? (str_starts_with($img->image_url, 'http') ? $img->image_url : 'storage/' . $img->image_url) : ''),
                'alt_text'       => $img->alt_text,
                'display_order'  => $img->display_order,
                'is_main'        => $img->is_main,
            ])),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
