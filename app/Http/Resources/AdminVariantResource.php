<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'product_id'     => $this->product_id,
            'sku'            => $this->sku,
            'regular_price'  => (float) $this->regular_price,
            'sale_price'     => (float) $this->sale_price,
            'cost_price'     => (float) $this->cost_price,
            'stock_quantity' => $this->stock_quantity,
            'barcode'        => $this->barcode,
            'is_active'      => $this->is_active,
            'attribute_values' => $this->whenLoaded('attributeValues'),
            'images'           => $this->whenLoaded('images', fn() => $this->images->map(fn($img) => [
                'id'             => $img->id,
                'product_id'     => $img->product_id,
                'variant_id'     => $img->variant_id,
                'image_url'      => url($img->image_url ? (str_starts_with($img->image_url, 'http') ? $img->image_url : 'storage/' . $img->image_url) : ''),
                'alt_text'       => $img->alt_text,
                'display_order'  => $img->display_order,
                'is_main'        => $img->is_main,
            ])),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
