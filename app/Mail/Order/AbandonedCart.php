<?php

namespace App\Mail\Order;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCart extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Cart $cart) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.abandoned_cart_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order.abandoned-cart',
            with: [
                'cart'   => $this->cart,
                'user'   => $this->cart->user,
                'locale' => app()->getLocale(),
            ],
        );
    }
}
