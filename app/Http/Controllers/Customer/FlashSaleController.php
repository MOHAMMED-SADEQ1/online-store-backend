<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = app()->getLocale();

        $flashSales = FlashSale::active()
            ->with('product')
            ->get()
            ->map(function ($flashSale) use ($locale) {
                $product = $flashSale->product;
                $remaining = $flashSale->max_quantity - $flashSale->sold_quantity;

                return [
                    'id'               => $flashSale->id,
                    'title'            => $flashSale->{'title_' . $locale},
                    'product_id'       => $flashSale->product_id,
                    'variant_id'       => $flashSale->variant_id,
                    'product_name'     => $product?->{'name_' . $locale},
                    'product_slug'     => $product?->slug,
                    'product_image'    => $product?->main_image ? url($product->main_image) : null,
                    'regular_price'    => (float) ($flashSale->variant?->sale_price ?? $flashSale->variant?->regular_price ?? $product?->sale_price ?? $product?->regular_price),
                    'flash_price'      => (float) $flashSale->flash_price,
                    'discount_percent' => $this->calcDiscount($flashSale),
                    'remaining'        => max(0, $remaining),
                    'sold_quantity'    => $flashSale->sold_quantity,
                    'max_quantity'     => $flashSale->max_quantity,
                    'start_date'       => $flashSale->start_date->toIso8601String(),
                    'end_date'         => $flashSale->end_date->toIso8601String(),
                    'is_active'        => true,
                ];
            });

        return response()->json([
            'flash_sales' => $flashSales,
        ]);
    }

    private function calcDiscount(FlashSale $flashSale): int
    {
        $regular = $flashSale->variant?->sale_price
            ?? $flashSale->variant?->regular_price
            ?? $flashSale->product?->sale_price
            ?? $flashSale->product?->regular_price
            ?? 0;

        if ($regular <= 0 || $flashSale->flash_price <= 0) {
            return 0;
        }

        return (int) round((1 - $flashSale->flash_price / $regular) * 100);
    }
}
