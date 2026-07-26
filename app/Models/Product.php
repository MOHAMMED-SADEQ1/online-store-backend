<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable;

    protected $fillable = [
        'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
        'sku', 'regular_price', 'sale_price', 'cost_price', 'tax_rate_id',
        'quantity_in_stock', 'low_stock_threshold', 'max_per_order',
        'price_includes_tax', 'weight', 'dimensions',
        'main_image', 'is_active', 'is_featured', 'brand_id',
        'is_returnable', 'is_exchangeable', 'return_period_days', 'is_cancellable',
        'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'regular_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_returnable' => 'boolean',
            'is_exchangeable' => 'boolean',
            'is_cancellable' => 'boolean',
            'return_period_days' => 'integer',
            'price_includes_tax' => 'boolean',
            'quantity_in_stock' => 'integer',
            'max_per_order' => 'integer',
        ];
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->quantity_in_stock <= 0) {
            return 'out_of_stock';
        }
        if ($this->low_stock_threshold > 0 && $this->quantity_in_stock <= $this->low_stock_threshold) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->withPivot('is_variation', 'display_order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function firstImage()
    {
        return $this->hasOne(ProductImage::class)->whereNull('variant_id')->orderBy('display_order')->orderBy('id');
    }

    // ─── Scout / Meilisearch ───────────────────────────────────────────

    public function toSearchableArray(): array
    {
        $locale = app()->getLocale();

        $cheapestVariant = $this->variants()
            ->where('is_active', true)
            ->orderByRaw('COALESCE(sale_price, regular_price) ASC')
            ->first();

        $price = $cheapestVariant
            ? (float) ($cheapestVariant->sale_price ?? $cheapestVariant->regular_price)
            : (float) ($this->sale_price ?? $this->regular_price);

        $regularPrice = $cheapestVariant
            ? (float) $cheapestVariant->regular_price
            : (float) $this->regular_price;

        return [
            'id'                => $this->id,
            'name_ar'           => $this->name_ar,
            'name_en'           => $this->name_en,
            'slug'              => $this->slug,
            'description_ar'    => strip_tags((string) $this->description_ar),
            'description_en'    => strip_tags((string) $this->description_en),
            'sku'               => $this->sku,
            'regular_price'     => $regularPrice,
            'sale_price'        => $price !== $regularPrice ? $price : null,
            'price'             => $price,
            'is_active'         => $this->is_active,
            'is_featured'       => $this->is_featured,
            'stock_status'      => $this->stock_status,
            'category_id'       => $this->categories()->pluck('categories.id')->toArray(),
            'category_names'    => $this->categories->pluck('name_' . $locale)->implode(' '),
            'tag_names'         => $this->tags->pluck('name_' . $locale)->implode(' '),
            'brand_id'          => $this->brand_id,
            'brand_name'        => $this->brand?->{'name_' . $locale},
            'created_at'        => $this->created_at?->timestamp,
            'rating'            => (float) ($this->reviews_avg_rating ?? $this->reviews()->avg('rating') ?? 0),
            'sold_count'        => (int) \App\Models\OrderItem::where('product_id', $this->id)
                ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
                ->sum('quantity'),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }

    public function searchableAs(): string
    {
        return 'products';
    }
}


