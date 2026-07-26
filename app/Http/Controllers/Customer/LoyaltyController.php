<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(protected LoyaltyService $loyaltyService) {}

    /**
     * Get points balance and tier info.
     */
    public function points(Request $request): JsonResponse
    {
        $info = $this->loyaltyService->getPointsInfo($request->user());

        return response()->json(['loyalty' => $info]);
    }

    /**
     * Get loyalty transaction history.
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = $this->loyaltyService->getTransactionHistory(
            $request->user(),
            $request->per_page ?? 20
        );

        return response()->json($transactions);
    }

    /**
     * Get all available loyalty tiers.
     */
    public function tiers(): JsonResponse
    {
        $tiers = $this->loyaltyService->getTiers();

        return response()->json(['tiers' => $tiers]);
    }

    /**
     * Get or create referral code.
     */
    public function referralCode(Request $request): JsonResponse
    {
        $code = $this->loyaltyService->createReferralCode($request->user());

        $locale = app()->getLocale();
        $shareUrl = config('app.url') . '/register?ref=' . $code->code;

        return response()->json([
            'referral_code'  => $code->code,
            'share_url'      => $shareUrl,
            'total_referred' => $code->total_referred,
            'total_earned'   => (float) $code->total_earned,
            'share_links'    => [
                'whatsapp' => 'https://wa.me/?text=' . urlencode(__('loyalty.share_text') . ' ' . $shareUrl),
                'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($shareUrl),
                'twitter'  => 'https://twitter.com/intent/tweet?text=' . urlencode(__('loyalty.share_text') . ' ' . $shareUrl),
            ],
        ]);
    }

    /**
     * Register with a referral code (called during signup).
     */
    public function registerReferral(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $redemption = $this->loyaltyService->registerReferral(
            $data['code'],
            $request->user()
        );

        if (!$redemption) {
            return response()->json(['message' => __('loyalty.referral_invalid')], 422);
        }

        return response()->json([
            'message' => __('loyalty.referral_registered'),
        ]);
    }

    /**
     * Spend points on current order (calculate discount).
     */
    public function estimatePointsValue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'points' => 'required|integer|min:100',
        ]);

        $user = $request->user();
        $points = $this->loyaltyService->getPointsAccount($user);

        if ($data['points'] > $points->balance) {
            return response()->json([
                'valid'  => false,
                'message' => __('loyalty.insufficient_points'),
            ], 422);
        }

        $discount = $data['points'] * LoyaltyService::POINT_VALUE;

        return response()->json([
            'valid'         => true,
            'points'        => $data['points'],
            'discount_value'=> round($discount, 2),
        ]);
    }

    /**
     * Get referral history.
     */
    public function referralHistory(Request $request): JsonResponse
    {
        $code = ReferralCode::where('user_id', $request->user()->id)->first();

        if (!$code) {
            return response()->json(['redemptions' => []]);
        }

        $redemptions = $code->redemptions()
            ->with('referredUser:id,first_name,last_name,email')
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'            => $r->id,
                'referred_user' => $r->referredUser?->first_name . ' ' . $r->referredUser?->last_name,
                'referred_email'=> $r->referredUser?->email,
                'status'        => $r->status,
                'reward_amount' => (float) $r->reward_amount,
                'completed_at'  => $r->completed_at?->toIso8601String(),
                'created_at'    => $r->created_at->toIso8601String(),
            ]);

        return response()->json(['redemptions' => $redemptions]);
    }
}
