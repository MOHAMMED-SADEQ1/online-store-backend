<?php

namespace App\Http\Resources\Customer;

use App\Models\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        $cheapest = null;
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            $cheapest = $this->variants
                ->sortBy(fn($v) => $v->sale_price ?? $v->regular_price)
                ->first();
        }

        $price = $cheapest
            ? ($cheapest->sale_price ?? $cheapest->regular_price)
            : ($this->sale_price ?? $this->regular_price);

        $regularPrice = $cheapest
            ? (float) $cheapest->regular_price
            : (float) $this->regular_price;

        $variantImage = $cheapest && $cheapest->relationLoaded('images') && $cheapest->images->isNotEmpty()
            ? $cheapest->images->first()
            : null;

        $productImage = $this->relationLoaded('images') && $this->images->isNotEmpty()
            ? $this->images->first()
            : null;

        $firstImage = $variantImage ?? $productImage;

        // Check for active flash sale
        $flashSale = FlashSale::active()
            ->where('product_id', $this->id)
            ->first();

        return [
            'id'                => $this->id,
            'name'              => $this->{'name_' . $locale},
            'slug'              => $this->slug,
            'regular_price'     => $flashSale ? (float) $regularPrice : $regularPrice,
            'sale_price'        => $flashSale ? (float) $flashSale->flash_price : ($price !== $regularPrice ? (float) $price : null),
            'flash_sale'        => $flashSale ? [
                'id'               => $flashSale->id,
                'title'            => $flashSale->{'title_' . $locale},
                'flash_price'      => (float) $flashSale->flash_price,
                'end_date'         => $flashSale->end_date->toIso8601String(),
                'remaining'        => max(0, $flashSale->max_quantity - $flashSale->sold_quantity),
            ] : null,
            'price_includes_tax'=> (bool) $this->price_includes_tax,
            'first_image'       => $firstImage ? url($firstImage->image_url ? (str_starts_with($firstImage->image_url, 'http') ? $firstImage->image_url : 'storage/' . $firstImage->image_url) : '') : null,
            'is_featured'       => $this->is_featured,
            'stock_status'      => $this->stock_status,
            'max_per_order'     => $this->max_per_order,
            'is_returnable'     => $this->is_returnable,
            'is_exchangeable'   => $this->is_exchangeable,
            'return_period_days'=> $this->return_period_days,
            'is_cancellable'    => $this->is_cancellable,
            'rating'            => (float) $this->reviews_avg_rating,
            'review_count'      => (int) $this->reviews_count,
            'categories'        => $this->whenLoaded('categories', fn() => $this->categories->map(fn($c) => [
                'id'      => $c->id,
                'name'    => $c->{'name_' . $locale},
                'slug'    => $c->slug,
            ])),
        ];
    }
}
