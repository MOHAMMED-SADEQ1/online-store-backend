<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderResource extends JsonResource
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
            'cancel_reason'   => $this->cancel_reason,
            'confirmed_at'    => $this->confirmed_at,
            'processing_at'   => $this->processing_at,
            'shipped_at'      => $this->shipped_at,
            'delivered_at'    => $this->delivered_at,
            'cancelled_at'    => $this->cancelled_at,
            'created_at'      => $this->created_at,
            'items'           => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'         => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity'   => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price'=> (float) $item->total_price,
                'product_name' => $item->{'product_name_' . $locale},
                'product'    => $item->product ? [
                    'id'   => $item->product->id,
                    'slug' => $item->product->slug,
                    'image'=> $item->product->main_image ? url($item->product->main_image) : null,
                ] : null,
            ])),
            'shipping_address' => $this->whenLoaded('shippingAddress', fn() => [
                'street_address' => $this->shippingAddress->street_address,
                'city'   => $this->shippingAddress->city,
                'state'  => $this->shippingAddress->state,
                'country'=> $this->shippingAddress->country,
                'postal_code' => $this->shippingAddress->postal_code,
            ]),
        ];
    }
}
