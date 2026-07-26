<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'total_amount'    => (float) $this->total_amount,
            'tax_amount'      => (float) $this->tax_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'discount_amount' => (float) $this->discount_amount,
            'coupon_code'     => $this->coupon_code,
            'final_amount'    => (float) $this->final_amount,
            'order_status'    => $this->order_status,
            'payment_status'  => $this->payment_status,
            'notes'           => $this->notes,
            'callback_url'    => $this->callback_url,
            'payment_method_id' => $this->payment_method_id,
            'currency'        => $this->currency ?? 'SAR',
            'created_at'      => $this->created_at,
            'confirmed_at'    => $this->confirmed_at,
            'processing_at'   => $this->processing_at,
            'shipped_at'      => $this->shipped_at,
            'delivered_at'    => $this->delivered_at,
            'cancelled_at'    => $this->cancelled_at,
            'cancel_reason'   => $this->cancel_reason,

            'user'            => $this->whenLoaded('user', fn() => [
                'id'         => $this->user->id,
                'username'   => $this->user->username,
                'email'      => $this->user->email,
                'first_name' => $this->user->first_name,
                'last_name'  => $this->user->last_name,
                'phone'      => $this->user->phone,
                'locale'     => $this->user->locale,
                'is_active'  => $this->user->is_active,
            ]),

            'shipping_address' => $this->whenLoaded('shippingAddress', fn() => [
                'id'             => $this->shippingAddress->id,
                'label'          => $this->shippingAddress->label,
                'street_address' => $this->shippingAddress->street_address,
                'city'           => $this->shippingAddress->city,
                'state'          => $this->shippingAddress->state,
                'country'        => $this->shippingAddress->country,
                'postal_code'    => $this->shippingAddress->postal_code,
                'latitude'       => $this->shippingAddress->latitude,
                'longitude'      => $this->shippingAddress->longitude,
                'is_default'     => $this->shippingAddress->is_default,
            ]),

            'billing_address' => $this->whenLoaded('billingAddress', fn() => [
                'id'             => $this->billingAddress->id,
                'label'          => $this->billingAddress->label,
                'street_address' => $this->billingAddress->street_address,
                'city'           => $this->billingAddress->city,
                'state'          => $this->billingAddress->state,
                'country'        => $this->billingAddress->country,
                'postal_code'    => $this->billingAddress->postal_code,
                'latitude'       => $this->billingAddress->latitude,
                'longitude'      => $this->billingAddress->longitude,
                'is_default'     => $this->billingAddress->is_default,
            ]),

            'items'           => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'           => $item->id,
                'quantity'     => $item->quantity,
                'unit_price'   => (float) $item->unit_price,
                'subtotal'     => (float) ($item->subtotal ?? $item->unit_price * $item->quantity),
                'tax_amount'   => (float) $item->tax_amount,
                'total_price'  => (float) $item->total_price,
                'product_name' => $item->product_name_ar ?? $item->product?->name_ar,
                'sku_snapshot' => $item->sku_snapshot,
                'product'      => $item->product ? [
                    'id'                 => $item->product->id,
                    'name_ar'            => $item->product->name_ar,
                    'name_en'            => $item->product->name_en,
                    'slug'               => $item->product->slug,
                    'sku'                => $item->product->sku,
                    'regular_price'      => (float) $item->product->regular_price,
                    'sale_price'         => $item->product->sale_price ? (float) $item->product->sale_price : null,
                    'stock_status'       => $item->product->stock_status,
                    'main_image'         => $item->product->main_image ? url($item->product->main_image) : null,
                    'images'             => $item->product->images->map(fn($img) => [
                        'id'       => $img->id,
                        'image_url' => url($img->image_url ? (str_starts_with($img->image_url, 'http') ? $img->image_url : 'storage/' . $img->image_url) : ''),
                        'is_main'  => $img->is_main,
                    ]),
                    'categories'         => $item->product->categories->map(fn($c) => [
                        'id'   => $c->id,
                        'name' => $c->{'name_' . $locale},
                    ]),
                ] : null,
                'variant'      => $item->variant ? [
                    'id'             => $item->variant->id,
                    'sku'            => $item->variant->sku,
                    'regular_price'  => (float) $item->variant->regular_price,
                    'sale_price'     => (float) $item->variant->sale_price,
                    'stock_quantity' => $item->variant->stock_quantity,
                    'is_active'      => $item->variant->is_active,
                    'attribute_values' => $item->variant->attributeValues->map(fn($av) => [
                        'id'         => $av->id,
                        'value'      => $av->{'value_' . $locale},
                        'attribute'  => $av->attribute ? [
                            'id'   => $av->attribute->id,
                            'name' => $av->attribute->{'name_' . $locale},
                        ] : null,
                    ]),
                    'images'         => $item->variant->images->map(fn($img) => [
                        'id'       => $img->id,
                        'image_url' => url($img->image_url ? (str_starts_with($img->image_url, 'http') ? $img->image_url : 'storage/' . $img->image_url) : ''),
                        'is_main'  => $img->is_main,
                    ]),
                    'has_variant' => true,
                ] : ['has_variant' => false],
            ])),

            'shipping'        => $this->whenLoaded('shipping', fn() => [
                'id'              => $this->shipping->id,
                'carrier'         => $this->shipping->carrier,
                'tracking_number' => $this->shipping->tracking_number,
                'shipping_status' => $this->shipping->shipping_status,
                'estimated_days'  => $this->shipping->estimated_days_min && $this->shipping->estimated_days_max
                    ? "{$this->shipping->estimated_days_min}-{$this->shipping->estimated_days_max}"
                    : null,
                'shipping_zone'   => $this->shipping->shippingZone ? [
                    'id'   => $this->shipping->shippingZone->id,
                    'name' => $this->shipping->shippingZone->{'name_' . $locale},
                ] : null,
            ]),

            'payments'        => $this->whenLoaded('payments', fn() => $this->payments->map(fn($p) => [
                'id'          => $p->id,
                'amount'      => (float) $p->amount,
                'status'      => $p->status,
                'gateway'     => $p->gateway,
                'method'      => $p->method ? [
                    'id'   => $p->method->id,
                    'name' => $p->method->name,
                ] : null,
                'transaction_id' => $p->transaction_id,
                'paid_at'     => $p->paid_at,
            ])),
        ];
    }
}
