<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Models\ReferralRedemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralCodeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'referral_codes' => ReferralCode::with('user:id,username,email,first_name,last_name')
                ->withCount('redemptions')
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }

    public function show(ReferralCode $referralCode): JsonResponse
    {
        return response()->json([
            'referral_code' => $referralCode->load([
                'user:id,username,email,first_name,last_name',
                'redemptions' => fn($q) => $q->with([
                    'referredUser:id,username,email,first_name,last_name',
                    'order:id,order_number,final_amount',
                ])->latest(),
            ]),
        ]);
    }

    public function redemptions(Request $request, ?int $referralCodeId = null): JsonResponse
    {
        $query = ReferralRedemption::with([
            'referralCode.user:id,username,email',
            'referredUser:id,username,email,first_name,last_name',
            'order:id,order_number,final_amount',
        ]);

        if ($referralCodeId) {
            $query->where('referral_code_id', $referralCodeId);
        }

        $perPage = (int) $request->get('per_page', 20);

        return response()->json([
            'redemptions' => $query->latest()->paginate($perPage),
        ]);
    }

    public function update(Request $request, ReferralCode $referralCode): JsonResponse
    {
        $data = $request->validate([
            'is_active' => 'boolean',
        ]);

        $referralCode->update($data);

        return response()->json([
            'message'       => 'Referral code updated successfully.',
            'referral_code' => $referralCode->fresh()->load('user:id,username,email,first_name,last_name'),
        ]);
    }
}
