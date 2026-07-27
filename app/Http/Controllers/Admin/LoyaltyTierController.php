<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoyaltyTierController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'loyalty_tiers' => LoyaltyTier::orderBy('min_points')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'          => 'required|string|max:100',
            'name_en'          => 'required|string|max:100',
            'slug'             => 'nullable|string|max:100|unique:loyalty_tiers,slug',
            'min_points'       => 'required|integer|min:0',
            'max_points'       => 'nullable|integer|gt:min_points',
            'points_multiplier'=> 'numeric|min:1',
            'discount_percent' => 'numeric|min:0|max:100',
            'free_shipping'    => 'boolean',
            'priority_support' => 'boolean',
            'is_active'        => 'boolean',
            'badge'            => 'nullable|string|max:50',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name_en']);

        $tier = LoyaltyTier::create($data);

        return response()->json([
            'message'      => 'Loyalty tier created successfully.',
            'loyalty_tier' => $tier,
        ], 201);
    }

    public function show(LoyaltyTier $loyaltyTier): JsonResponse
    {
        return response()->json([
            'loyalty_tier' => $loyaltyTier,
        ]);
    }

    public function update(Request $request, LoyaltyTier $loyaltyTier): JsonResponse
    {
        $data = $request->validate([
            'name_ar'          => 'sometimes|string|max:100',
            'name_en'          => 'sometimes|string|max:100',
            'slug'             => 'nullable|string|max:100|unique:loyalty_tiers,slug,' . $loyaltyTier->id,
            'min_points'       => 'sometimes|integer|min:0',
            'max_points'       => 'nullable|integer|gt:min_points',
            'points_multiplier'=> 'numeric|min:1',
            'discount_percent' => 'numeric|min:0|max:100',
            'free_shipping'    => 'boolean',
            'priority_support' => 'boolean',
            'is_active'        => 'boolean',
            'badge'            => 'nullable|string|max:50',
        ]);

        $loyaltyTier->update($data);

        return response()->json([
            'message'      => 'Loyalty tier updated successfully.',
            'loyalty_tier' => $loyaltyTier->fresh(),
        ]);
    }

    public function destroy(LoyaltyTier $loyaltyTier): JsonResponse
    {
        $loyaltyTier->delete();

        return response()->json(['message' => 'Loyalty tier deleted successfully.']);
    }
}
