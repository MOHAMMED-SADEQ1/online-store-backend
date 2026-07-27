<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftCardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'gift_cards' => GiftCard::with('purchaser:id,username,email,first_name,last_name')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($g) => [
                    'id'               => $g->id,
                    'code'             => $g->code,
                    'original_balance' => (float) $g->original_balance,
                    'current_balance'  => (float) $g->current_balance,
                    'purchaser'        => $g->purchaser,
                    'recipient_email'  => $g->recipient_email,
                    'recipient_name'   => $g->recipient_name,
                    'sent_at'          => $g->sent_at?->toIso8601String(),
                    'expires_at'       => $g->expires_at?->toIso8601String(),
                    'is_active'        => $g->is_active,
                    'created_at'       => $g->created_at->toIso8601String(),
                ]),
        ]);
    }

    public function show(GiftCard $giftCard): JsonResponse
    {
        return response()->json([
            'gift_card' => [
                'id'               => $giftCard->id,
                'code'             => $giftCard->code,
                'original_balance' => (float) $giftCard->original_balance,
                'current_balance'  => (float) $giftCard->current_balance,
                'purchaser'        => $giftCard->purchaser,
                'recipient_email'  => $giftCard->recipient_email,
                'recipient_name'   => $giftCard->recipient_name,
                'message'          => $giftCard->message,
                'sent_at'          => $giftCard->sent_at?->toIso8601String(),
                'expires_at'       => $giftCard->expires_at?->toIso8601String(),
                'is_active'        => $giftCard->is_active,
                'usages'           => $giftCard->usages()->with('order:id,order_number')->latest()->get(),
                'created_at'       => $giftCard->created_at->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, GiftCard $giftCard): JsonResponse
    {
        $data = $request->validate([
            'current_balance' => 'nullable|numeric|min:0',
            'is_active'       => 'boolean',
            'expires_at'      => 'nullable|date|after:now',
        ]);

        $giftCard->update($data);

        return response()->json([
            'message'   => 'Gift card updated successfully.',
            'gift_card' => $giftCard->fresh()->load('purchaser:id,username,email,first_name,last_name'),
        ]);
    }

    public function destroy(GiftCard $giftCard): JsonResponse
    {
        $giftCard->usages()->delete();
        $giftCard->delete();

        return response()->json(['message' => 'Gift card deleted successfully.']);
    }
}
