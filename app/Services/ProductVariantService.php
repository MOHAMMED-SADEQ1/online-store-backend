<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class ProductVariantService
{
    public function getVariants(Product $product)
    {
        return $product->variants()
            ->with(['attributeValues.attribute', 'images'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            $data['product_id'] = $product->id;

            $variant = ProductVariant::create($data);

            if (isset($data['attribute_values'])) {
                $variant->attributeValues()->sync($data['attribute_values']);
            }

            return $variant->load('attributeValues', 'images');
        });
    }

    public function updateVariant(Product $product, ProductVariant $variant, array $data): ProductVariant
    {
        if ($variant->product_id !== $product->id) {
            abort(404, 'Variant does not belong to this product.');
        }

        return DB::transaction(function () use ($variant, $data) {
            $variant->update($data);

            if (isset($data['attribute_values'])) {
                $variant->attributeValues()->sync($data['attribute_values']);
            }

            return $variant->fresh()->load('attributeValues', 'images');
        });
    }

    public function deleteVariant(Product $product, ProductVariant $variant): void
    {
        if ($variant->product_id !== $product->id) {
            abort(404, 'Variant does not belong to this product.');
        }

        DB::transaction(function () use ($variant) {
            $variant->attributeValues()->detach();
            $variant->delete();
        });
    }

    public function isVariantInStock(ProductVariant $variant, int $quantity = 1): bool
    {
        return $variant->is_active && $variant->stock_quantity >= $quantity;
    }

    public function decrementStock(ProductVariant $variant, int $quantity = 1): void
    {
        $variant->decrement('stock_quantity', $quantity);
    }

    public function incrementStock(ProductVariant $variant, int $quantity = 1): void
    {
        $variant->increment('stock_quantity', $quantity);
    }
}
