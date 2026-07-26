<?php

namespace App\Services;

use App\Models\GiftCard;
use App\Models\GiftCardUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GiftCardService
{
    /**
     * Create a new gift card.
     */
    public function create(array $data, User $purchaser): GiftCard
    {
        return DB::transaction(function () use ($data, $purchaser) {
            $giftCard = GiftCard::create([
                'code'              => GiftCard::generateCode(),
                'original_balance'  => $data['amount'],
                'current_balance'   => $data['amount'],
                'purchaser_user_id' => $purchaser->id,
                'recipient_email'   => $data['recipient_email'] ?? null,
                'recipient_name'    => $data['recipient_name'] ?? null,
                'message'           => $data['message'] ?? null,
                'expires_at'        => $data['expires_at'] ?? now()->addYear(),
                'is_active'         => true,
            ]);

            return $giftCard;
        });
    }

    /**
     * Validate a gift card code.
     */
    public function validate(string $code): array
    {
        $giftCard = GiftCard::where('code', $code)->valid()->first();

        if (!$giftCard) {
            return ['valid' => false, 'message' => __('gift_card.invalid')];
        }

        return [
            'valid'           => true,
            'gift_card'       => [
                'id'               => $giftCard->id,
                'code'             => $giftCard->code,
                'current_balance'  => (float) $giftCard->current_balance,
                'original_balance' => (float) $giftCard->original_balance,
            ],
        ];
    }

    /**
     * Apply gift card to an order for a discount.
     */
    public function applyToOrder(Order $order, string $code): array
    {
        $giftCard = GiftCard::where('code', $code)->valid()->first();

        if (!$giftCard) {
            throw new \Exception(__('gift_card.invalid'));
        }

        $amount = min($giftCard->current_balance, $order->final_amount);

        return DB::transaction(function () use ($giftCard, $order, $amount) {
            $newBalance = $giftCard->current_balance - $amount;

            $giftCard->decrement('current_balance', $amount);

            GiftCardUsage::create([
                'gift_card_id' => $giftCard->id,
                'order_id'     => $order->id,
                'user_id'      => $order->user_id,
                'amount_used'  => $amount,
                'balance_after'=> $newBalance,
            ]);

            if ($newBalance <= 0) {
                $giftCard->update(['is_active' => false]);
            }

            return [
                'amount_applied' => $amount,
                'new_balance'    => $newBalance,
            ];
        });
    }

    /**
     * Get gift card balance for a code.
     */
    public function checkBalance(string $code): ?array
    {
        $giftCard = GiftCard::where('code', $code)->valid()->first();

        if (!$giftCard) return null;

        return [
            'code'             => $giftCard->code,
            'current_balance'  => (float) $giftCard->current_balance,
            'original_balance' => (float) $giftCard->original_balance,
            'expires_at'       => $giftCard->expires_at?->toIso8601String(),
        ];
    }

    /**
     * Get usage history for a gift card.
     */
    public function getUsageHistory(GiftCard $giftCard)
    {
        return $giftCard->usages()->with('order')->latest()->get();
    }

    /**
     * Get all gift cards purchased by a user.
     */
    public function getPurchasedGiftCards(User $user)
    {
        return GiftCard::where('purchaser_user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn($g) => [
                'id'               => $g->id,
                'code'             => $g->code,
                'original_balance' => (float) $g->original_balance,
                'current_balance'  => (float) $g->current_balance,
                'recipient_email'  => $g->recipient_email,
                'recipient_name'   => $g->recipient_name,
                'sent_at'          => $g->sent_at?->toIso8601String(),
                'expires_at'       => $g->expires_at?->toIso8601String(),
                'is_active'        => $g->is_active,
                'created_at'       => $g->created_at->toIso8601String(),
            ]);
    }
}
