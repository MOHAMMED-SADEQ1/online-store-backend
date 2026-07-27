<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyPointController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'loyalty_points' => LoyaltyPoint::with([
                'user:id,username,email,first_name,last_name',
                'tier:id,name_ar,name_en',
            ])
                ->orderBy('balance', 'desc')
                ->get(),
        ]);
    }

    public function show(LoyaltyPoint $loyaltyPoint): JsonResponse
    {
        return response()->json([
            'loyalty_point' => $loyaltyPoint->load([
                'user:id,username,email,first_name,last_name',
                'tier',
                'transactions' => fn($q) => $q->latest()->limit(50),
            ]),
        ]);
    }

    public function transactions(Request $request, ?int $userId = null): JsonResponse
    {
        $query = LoyaltyTransaction::with('order:id,order_number');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $perPage = (int) $request->get('per_page', 20);

        return response()->json([
            'transactions' => $query->latest()->paginate($perPage),
        ]);
    }

    public function adjustBalance(Request $request, LoyaltyPoint $loyaltyPoint): JsonResponse
    {
        $data = $request->validate([
            'points'        => 'required|integer',
            'description_ar' => 'required|string|max:255',
            'description_en' => 'required|string|max:255',
        ]);

        $newBalance = $loyaltyPoint->balance + $data['points'];

        if ($newBalance < 0) {
            return response()->json([
                'message' => 'Insufficient points balance.',
                'code'    => 422,
            ], 422);
        }

        $loyaltyPoint->increment('balance', $data['points']);
        $loyaltyPoint->increment($data['points'] > 0 ? 'lifetime_earned' : 'lifetime_spent', abs($data['points']));

        LoyaltyTransaction::create([
            'user_id'       => $loyaltyPoint->user_id,
            'type'          => $data['points'] > 0 ? 'earned' : 'spent',
            'source'        => 'admin_adjustment',
            'points'        => $data['points'],
            'balance_after' => $newBalance,
            'description_ar' => $data['description_ar'],
            'description_en' => $data['description_en'],
            'meta'          => ['adjusted_by_admin' => true],
        ]);

        return response()->json([
            'message'       => 'Points adjusted successfully.',
            'loyalty_point' => $loyaltyPoint->fresh()->load('user:id,username,email,first_name,last_name'),
        ]);
    }
}
