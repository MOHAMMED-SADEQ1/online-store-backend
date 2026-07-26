<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index(): JsonResponse
    {
        $attributes = Attribute::with('values')->orderBy('display_order')->get();

        return response()->json(['attributes' => $attributes]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name_ar'       => 'required|string|max:100',
            'name_en'       => 'required|string|max:100',
            'attribute_type' => 'sometimes|in:select,color,size,text',
            'display_order' => 'integer|min:0',
            'is_global'     => 'boolean',
        ]);

        $attribute = Attribute::create($data);

        return response()->json([
            'message'   => 'Attribute created successfully.',
            'attribute' => $attribute,
        ], 201);
    }

    public function show(Attribute $attribute): JsonResponse
    {
        $attribute->load('values');

        return response()->json(['attribute' => $attribute]);
    }

    public function update(Request $request, Attribute $attribute): JsonResponse
    {
        $data = $request->validate([
            'name_ar'       => 'sometimes|string|max:100',
            'name_en'       => 'sometimes|string|max:100',
            'attribute_type' => 'sometimes|in:select,color,size,text',
            'display_order' => 'integer|min:0',
            'is_global'     => 'boolean',
        ]);

        $attribute->update($data);

        return response()->json([
            'message'   => 'Attribute updated successfully.',
            'attribute' => $attribute->fresh(),
        ]);
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $attribute->delete();

        return response()->json(['message' => 'Attribute deleted successfully.']);
    }

    public function storeValue(Request $request, Attribute $attribute): JsonResponse
    {
        $data = $request->validate([
            'value_ar'      => 'required|string|max:100',
            'value_en'      => 'required|string|max:100',
            'extra_data'    => 'nullable|json',
            'display_order' => 'integer|min:0',
        ]);

        if (isset($data['extra_data'])) {
            $data['extra_data'] = json_decode($data['extra_data'], true);
        }

        $value = $attribute->values()->create($data);

        return response()->json([
            'message' => 'Value added successfully.',
            'value'   => $value,
        ], 201);
    }

    public function updateValue(Request $request, Attribute $attribute, $valueId): JsonResponse
    {
        $value = $attribute->values()->findOrFail($valueId);

        $data = $request->validate([
            'value_ar'      => 'sometimes|string|max:100',
            'value_en'      => 'sometimes|string|max:100',
            'extra_data'    => 'nullable|json',
            'display_order' => 'integer|min:0',
        ]);

        if (isset($data['extra_data'])) {
            $data['extra_data'] = json_decode($data['extra_data'], true);
        }

        $value->update($data);

        return response()->json([
            'message' => 'Value updated successfully.',
            'value'   => $value->fresh(),
        ]);
    }

    public function destroyValue(Attribute $attribute, $valueId): JsonResponse
    {
        $value = $attribute->values()->findOrFail($valueId);
        $value->delete();

        return response()->json(['message' => 'Value deleted successfully.']);
    }
}
