<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'tax_rates' => TaxRate::orderBy('rate_percent')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'      => 'required|string|max:50',
            'name_en'      => 'required|string|max:50',
            'rate_percent' => 'required|numeric|min:0|max:100',
            'is_active'    => 'boolean',
        ]);

        $taxRate = TaxRate::create($data);

        return response()->json([
            'message'  => 'Tax rate created successfully.',
            'tax_rate' => $taxRate,
        ], 201);
    }

    public function update(Request $request, TaxRate $taxRate): JsonResponse
    {
        $data = $request->validate([
            'name_ar'      => 'sometimes|string|max:50',
            'name_en'      => 'sometimes|string|max:50',
            'rate_percent' => 'sometimes|numeric|min:0|max:100',
            'is_active'    => 'boolean',
        ]);

        $taxRate->update($data);

        return response()->json([
            'message'  => 'Tax rate updated successfully.',
            'tax_rate' => $taxRate->fresh(),
        ]);
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $taxRate->delete();

        return response()->json(['message' => 'Tax rate deleted successfully.']);
    }
}
