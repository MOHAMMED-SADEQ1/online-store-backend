<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\GiftCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    public function __construct(protected GiftCardService $giftCardService) {}

    /**
     * Purchase a new gift card.
     */
    public function purchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'          => 'required|numeric|min:10|max:10000',
            'recipient_email' => 'nullable|email',
            'recipient_name'  => 'nullable|string|max:100',
            'message'         => 'nullable|string|max:500',
            'expires_at'      => 'nullable|date|after:today',
        ]);

        $giftCard = $this->giftCardService->create($data, $request->user());

        // If recipient email provided, send the gift card via email
        if ($giftCard->recipient_email) {
            try {
                \Illuminate\Support\Facades\Mail::to($giftCard->recipient_email)
                    ->send(new \App\Mail\GiftCardPurchased($giftCard));
                $giftCard->update(['sent_at' => now()]);
            } catch (\Exception $e) {
                // Email sending failed but gift card is still created
                \Illuminate\Support\Facades\Log::warning('Gift card email failed', [
                    'gift_card_id' => $giftCard->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message'   => __('gift_card.created'),
            'gift_card' => [
                'id'               => $giftCard->id,
                'code'             => $giftCard->code,
                'original_balance' => (float) $giftCard->original_balance,
                'recipient_email'  => $giftCard->recipient_email,
                'expires_at'       => $giftCard->expires_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Check gift card balance.
     */
    public function balance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $balance = $this->giftCardService->checkBalance($data['code']);

        if (!$balance) {
            return response()->json(['message' => __('gift_card.invalid')], 404);
        }

        return response()->json($balance);
    }

    /**
     * Validate a gift card for use.
     */
    public function validate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $result = $this->giftCardService->validate($data['code']);

        if (!$result['valid']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Get purchased gift cards history.
     */
    public function purchased(Request $request): JsonResponse
    {
        $giftCards = $this->giftCardService->getPurchasedGiftCards($request->user());

        return response()->json(['gift_cards' => $giftCards]);
    }
}
