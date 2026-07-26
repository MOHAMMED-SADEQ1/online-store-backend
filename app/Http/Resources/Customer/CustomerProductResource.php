<?php

namespace App\Http\Resources\Customer;

use App\Models\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        $images = $this->whenLoaded('images', fn() => $this->images
            ->where('variant_id', null)
            ->map(fn($img) => [
                'id'        => $img->id,
                'image_url' => url($img->image_url ? (str_starts_with($img->image_url, 'http') ? $img->image_url : 'storage/' . $img->image_url) : ''),
                'alt_text'  => $img->alt_text,
                'is_main'   => $img->is_main,
            ])->values());

        $variants = $this->whenLoaded('variants', fn() => $this->variants->map(fn($v) => [
            'id'             => $v->id,
            'sku'            => $v->sku,
            'regular_price'  => (float) $v->regular_price,
            'sale_price'     => (float) $v->sale_price,
            'stock_quantity' => $v->stock_quantity,
            'is_active'      => $v->is_active,
            'images'         => $v->images->map(fn($img) => [
                'image_url' => url($img->image_url ? (str_starts_with($img->image_url, 'http') ? $img->image_url : 'storage/' . $img->image_url) : ''),
                'alt_text'  => $img->alt_text,
                'is_main'   => $img->is_main,
            ]),
            'attribute_values' => $v->attributeValues->map(fn($av) => [
                'id'       => $av->id,
                'value'    => $av->{'value_' . $locale},
                'attribute' => [
                    'id'   => $av->attribute->id,
                    'name' => $av->attribute->{'name_' . $locale},
                ],
            ]),
        ]));

        $reviews = $this->whenLoaded('reviews', fn() => $this->reviews
            ->where('is_approved', true)
            ->map(fn($r) => [
                'id'           => $r->id,
                'rating'       => $r->rating,
                'review_title' => $r->review_title,
                'review_text'  => $r->review_text,
                'user_name'    => $r->user->username ?? 'Anonymous',
                'created_at'   => $r->created_at,
            ]));

        // Check for active flash sale
        $flashSale = FlashSale::active()
            ->where('product_id', $this->id)
            ->first();

        return [
            'id'                => $this->id,
            'name'              => $this->{'name_' . $locale},
            'description'       => $this->{'description_' . $locale},
            'slug'              => $this->slug,
            'sku'               => $this->sku,
            'regular_price'     => $flashSale ? (float) $this->regular_price : (float) $this->regular_price,
            'sale_price'        => $flashSale ? (float) $flashSale->flash_price : ($this->sale_price ? (float) $this->sale_price : null),
            'flash_sale'        => $flashSale ? [
                'id'               => $flashSale->id,
                'title'            => $flashSale->{'title_' . $locale},
                'flash_price'      => (float) $flashSale->flash_price,
                'end_date'         => $flashSale->end_date->toIso8601String(),
                'remaining'        => max(0, $flashSale->max_quantity - $flashSale->sold_quantity),
            ] : null,
            'price_includes_tax'=> (bool) $this->price_includes_tax,
            'quantity_in_stock' => $this->quantity_in_stock,
            'stock_status'      => $this->stock_status,
            'max_per_order'     => $this->max_per_order,
            'low_stock_threshold'=> $this->low_stock_threshold,
            'weight'            => $this->weight ? (float) $this->weight : null,
            'dimensions'        => $this->dimensions,
            'is_active'         => $this->is_active,
            'is_featured'       => $this->is_featured,
            'is_returnable'     => $this->is_returnable,
            'is_exchangeable'   => $this->is_exchangeable,
            'return_period_days'=> $this->return_period_days,
            'is_cancellable'    => $this->is_cancellable,
            'meta_title'        => $this->meta_title,
            'meta_description'  => $this->meta_description,
            'images'            => $images,
            'variants'          => $variants,
            'reviews'           => $reviews,
            'categories'        => $this->whenLoaded('categories', fn() => $this->categories->map(fn($c) => [
                'id'      => $c->id,
                'name'    => $c->{'name_' . $locale},
                'slug'    => $c->slug,
            ])),
            'tags'              => $this->whenLoaded('tags', fn() => $this->tags->map(fn($t) => [
                'id'      => $t->id,
                'name'    => $t->{'name_' . $locale},
                'slug'    => $t->slug,
            ])),
            'attributes'        => $this->whenLoaded('attributes', fn() => $this->attributes->map(fn($a) => [
                'id'             => $a->id,
                'name'           => $a->{'name_' . $locale},
                'attribute_type' => $a->attribute_type,
            ])),
        ];
    }
}
