<?php

namespace App\Mail;

use App\Models\GiftCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftCardPurchased extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GiftCard $giftCard,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('gift_card.email_subject') . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-card',
        );
    }
}
