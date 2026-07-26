<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\ReferralCode;
use App\Models\ReferralRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Points earned per SAR spent.
     */
    const POINTS_PER_CURRENCY = 1; // 1 point per 1 SAR

    /**
     * Point value in SAR per point (for redemption).
     */
    const POINT_VALUE = 0.10; // 1 point = 0.10 SAR

    /**
     * Points awarded on signup.
     */
    const SIGNUP_BONUS_POINTS = 200;

    /**
     * Points for writing a review.
     */
    const REVIEW_BONUS_POINTS = 50;

    /**
     * Referral reward points for the inviter.
     */
    const REFERRAL_REWARD_POINTS = 100;

    /**
     * Get or create loyalty points account for a user.
     */
    public function getPointsAccount(User $user): LoyaltyPoint
    {
        $points = LoyaltyPoint::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'lifetime_earned' => 0, 'lifetime_spent' => 0]
        );

        // Assign tier based on lifetime points
        $this->syncTier($points);

        return $points;
    }

    /**
     * Earn points for a purchase.
     */
    public function earnPointsForPurchase(Order $order): void
    {
        $user = $order->user;
        if (!$user) return;

        $points = $this->getPointsAccount($user);
        $multiplier = $points->tier?->points_multiplier ?? 1.0;

        $basePoints = (int) ($order->final_amount * self::POINTS_PER_CURRENCY);
        $earnedPoints = (int) ($basePoints * $multiplier);

        if ($earnedPoints <= 0) return;

        DB::transaction(function () use ($points, $order, $earnedPoints) {
            $newBalance = $points->balance + $earnedPoints;

            $points->increment('balance', $earnedPoints);
            $points->increment('lifetime_earned', $earnedPoints);

            LoyaltyTransaction::create([
                'user_id'       => $points->user_id,
                'order_id'      => $order->id,
                'type'          => 'earned',
                'source'        => 'purchase',
                'points'        => $earnedPoints,
                'balance_after' => $newBalance,
                'description_ar' => 'نقاط من الطلب #' . $order->order_number,
                'description_en' => 'Points from order #' . $order->order_number,
                'meta'          => ['order_id' => $order->id, 'order_number' => $order->order_number],
            ]);

            $this->syncTier($points);
        });
    }

    /**
     * Spend points on an order (discount).
     */
    public function spendPoints(User $user, int $points, ?Order $order = null): array
    {
        $account = $this->getPointsAccount($user);

        if ($account->balance < $points) {
            throw new \Exception(__('loyalty.insufficient_points'));
        }

        $discountAmount = $points * self::POINT_VALUE;

        DB::transaction(function () use ($account, $order, $points, $discountAmount) {
            $newBalance = $account->balance - $points;

            $account->decrement('balance', $points);
            $account->increment('lifetime_spent', $points);

            LoyaltyTransaction::create([
                'user_id'       => $account->user_id,
                'order_id'      => $order?->id,
                'type'          => 'spent',
                'source'        => 'redemption',
                'points'        => -$points,
                'balance_after' => $newBalance,
                'description_ar' => 'استبدال نقاط' . ($order ? ' للطلب #' . $order->order_number : ''),
                'description_en' => 'Points redeemed' . ($order ? ' for order #' . $order->order_number : ''),
            ]);

            $this->syncTier($account);
        });

        return [
            'points_spent'    => $points,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Award signup bonus points.
     */
    public function awardSignupBonus(User $user): void
    {
        $points = $this->getPointsAccount($user);

        DB::transaction(function () use ($points, $user) {
            $newBalance = $points->balance + self::SIGNUP_BONUS_POINTS;

            $points->increment('balance', self::SIGNUP_BONUS_POINTS);
            $points->increment('lifetime_earned', self::SIGNUP_BONUS_POINTS);

            LoyaltyTransaction::create([
                'user_id'       => $user->id,
                'type'          => 'earned',
                'source'        => 'signup',
                'points'        => self::SIGNUP_BONUS_POINTS,
                'balance_after' => $newBalance,
                'description_ar' => 'مكافأة التسجيل',
                'description_en' => 'Signup bonus',
            ]);

            $this->syncTier($points);
        });
    }

    /**
     * Award review bonus points.
     */
    public function awardReviewBonus(User $user): void
    {
        $points = $this->getPointsAccount($user);

        DB::transaction(function () use ($points, $user) {
            $newBalance = $points->balance + self::REVIEW_BONUS_POINTS;

            $points->increment('balance', self::REVIEW_BONUS_POINTS);
            $points->increment('lifetime_earned', self::REVIEW_BONUS_POINTS);

            LoyaltyTransaction::create([
                'user_id'       => $user->id,
                'type'          => 'earned',
                'source'        => 'review',
                'points'        => self::REVIEW_BONUS_POINTS,
                'balance_after' => $newBalance,
                'description_ar' => 'نقاط تقييم منتج',
                'description_en' => 'Product review points',
            ]);

            $this->syncTier($points);
        });
    }

    /**
     * Handle referral: generate code + award points when referred user completes first order.
     */
    public function createReferralCode(User $user): ReferralCode
    {
        $existing = ReferralCode::where('user_id', $user->id)->first();
        if ($existing) return $existing;

        return ReferralCode::generateForUser($user);
    }

    /**
     * Process referral when a referred user completes their first order.
     */
    public function processReferralReward(ReferralCode $referralCode, User $referredUser, Order $order): void
    {
        $redemption = ReferralRedemption::where('referral_code_id', $referralCode->id)
            ->where('referred_user_id', $referredUser->id)
            ->first();

        if (!$redemption || $redemption->status !== 'pending') return;

        DB::transaction(function () use ($referralCode, $redemption, $order) {
            $redemption->update([
                'order_id'     => $order->id,
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            $referralCode->increment('total_referred');
            $referralCode->increment('total_earned', self::REFERRAL_REWARD_POINTS * self::POINT_VALUE);

            // Award points to the inviter
            $inviterPoints = $this->getPointsAccount($referralCode->user);
            $newBalance = $inviterPoints->balance + self::REFERRAL_REWARD_POINTS;

            $inviterPoints->increment('balance', self::REFERRAL_REWARD_POINTS);
            $inviterPoints->increment('lifetime_earned', self::REFERRAL_REWARD_POINTS);

            LoyaltyTransaction::create([
                'user_id'       => $inviterPoints->user_id,
                'order_id'      => $order->id,
                'type'          => 'earned',
                'source'        => 'referral',
                'points'        => self::REFERRAL_REWARD_POINTS,
                'balance_after' => $newBalance,
                'description_ar' => 'مكافأة إحالة',
                'description_en' => 'Referral reward',
                'meta'          => ['referred_user_id' => $referredUser->id],
            ]);

            // Award bonus to the referred user
            $referredPoints = $this->getPointsAccount($referredUser);
            $referredBalance = $referredPoints->balance + self::REFERRAL_REWARD_POINTS;

            $referredPoints->increment('balance', self::REFERRAL_REWARD_POINTS);
            $referredPoints->increment('lifetime_earned', self::REFERRAL_REWARD_POINTS);

            LoyaltyTransaction::create([
                'user_id'       => $referredUser->id,
                'order_id'      => $order->id,
                'type'          => 'earned',
                'source'        => 'referral',
                'points'        => self::REFERRAL_REWARD_POINTS,
                'balance_after' => $referredBalance,
                'description_ar' => 'مكافأة دعوة صديق',
                'description_en' => 'Friend invitation reward',
                'meta'          => ['inviter_user_id' => $referralCode->user_id],
            ]);

            $this->syncTier($inviterPoints);
            $this->syncTier($referredPoints);
        });
    }

    /**
     * Register a referral when a new user signs up with a referral code.
     */
    public function registerReferral(string $referralCode, User $newUser): ?ReferralRedemption
    {
        $code = ReferralCode::where('code', $referralCode)->where('is_active', true)->first();
        if (!$code || $code->user_id === $newUser->id) return null;

        // Prevent duplicate referrals
        $existing = ReferralRedemption::where('referred_user_id', $newUser->id)->exists();
        if ($existing) return null;

        return ReferralRedemption::create([
            'referral_code_id' => $code->id,
            'referred_user_id' => $newUser->id,
            'status'           => 'pending',
        ]);
    }

    /**
     * Get points balance and tier info for a user.
     */
    public function getPointsInfo(User $user): array
    {
        $account = $this->getPointsAccount($user);
        $locale = app()->getLocale();
        $nextTier = null;

        if ($account->tier) {
            $nextTier = LoyaltyTier::active()
                ->where('min_points', '>', $account->lifetime_earned)
                ->orderBy('min_points')
                ->first();
        } else {
            $nextTier = LoyaltyTier::active()->orderBy('min_points')->first();
        }

        return [
            'balance'          => $account->balance,
            'lifetime_earned'  => $account->lifetime_earned,
            'lifetime_spent'   => $account->lifetime_spent,
            'points_value'     => $account->balance * self::POINT_VALUE,
            'tier'             => $account->tier ? [
                'id'                => $account->tier->id,
                'name'              => $account->tier->{'name_' . $locale},
                'slug'              => $account->tier->slug,
                'points_multiplier' => (float) $account->tier->points_multiplier,
                'discount_percent'  => (float) $account->tier->discount_percent,
                'free_shipping'     => $account->tier->free_shipping,
                'badge'             => $account->tier->badge,
            ] : null,
            'next_tier'        => $nextTier ? [
                'name'       => $nextTier->{'name_' . $locale},
                'points_needed' => $nextTier->min_points - $account->lifetime_earned,
            ] : null,
        ];
    }

    /**
     * Get transaction history for a user.
     */
    public function getTransactionHistory(User $user, int $perPage = 20)
    {
        return LoyaltyTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Sync the user's tier based on lifetime earned points.
     */
    private function syncTier(LoyaltyPoint $points): void
    {
        $tier = LoyaltyTier::findTierForPoints($points->lifetime_earned);

        if ($tier && $tier->id !== $points->tier_id) {
            $points->update([
                'tier_id'          => $tier->id,
                'tier_assigned_at' => now(),
            ]);
        } elseif (!$tier && $points->tier_id) {
            $points->update([
                'tier_id'          => null,
                'tier_assigned_at' => null,
            ]);
        }
    }

    /**
     * Get all loyalty tiers.
     */
    public function getTiers(): array
    {
        $locale = app()->getLocale();

        return LoyaltyTier::active()->get()->map(fn($t) => [
            'id'                => $t->id,
            'name'              => $t->{'name_' . $locale},
            'slug'              => $t->slug,
            'min_points'        => $t->min_points,
            'max_points'        => $t->max_points,
            'points_multiplier' => (float) $t->points_multiplier,
            'discount_percent'  => (float) $t->discount_percent,
            'free_shipping'     => $t->free_shipping,
            'priority_support'  => $t->priority_support,
            'badge'             => $t->badge,
        ])->toArray();
    }
}
