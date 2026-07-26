<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerCartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id'             => $this->id,
            'coupon_code'    => $this->coupon_code,
            'coupon_discount'=> (float) $this->coupon_discount,
            'subtotal'       => (float) $this->items->sum(fn($i) => $i->quantity * ($i->variant?->sale_price ?? $i->product?->sale_price ?? $i->variant?->regular_price ?? $i->product?->regular_price ?? 0)),
            'total'          => max(0, (float) $this->items->sum(fn($i) => $i->quantity * ($i->variant?->sale_price ?? $i->product?->sale_price ?? $i->variant?->regular_price ?? $i->product?->regular_price ?? 0)) - (float) $this->coupon_discount),
            'items_count'    => $this->items->sum('quantity'),
            'items'          => $this->items->map(fn($item) => [
                'id'         => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity'   => $item->quantity,
                'product'    => $item->product ? [
                    'id'      => $item->product->id,
                    'name'    => $item->product->{'name_' . $locale},
                    'slug'    => $item->product->slug,
                    'image'   => $item->product->main_image ? url($item->product->main_image) : ($item->product->images->first() ? url('storage/' . $item->product->images->first()->image_url) : null),
                ] : null,
                'variant'    => $item->variant ? [
                    'id'    => $item->variant->id,
                    'sku'   => $item->variant->sku,
                    'regular_price' => (float) $item->variant->regular_price,
                    'sale_price'    => (float) $item->variant->sale_price,
                ] : null,
                'unit_price' => (float) ($item->variant?->sale_price ?? $item->product?->sale_price ?? $item->variant?->regular_price ?? $item->product?->regular_price ?? 0),
                'total_price'=> (float) ($item->quantity * ($item->variant?->sale_price ?? $item->product?->sale_price ?? $item->variant?->regular_price ?? $item->product?->regular_price ?? 0)),
            ]),
        ];
    }
}
