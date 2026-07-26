<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', $request->user()->id)->get();

        return response()->json(['addresses' => $addresses]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address_type'         => 'nullable|string|max:50',
            'street_address'       => 'required|string|max:500',
            'city'                 => 'required|string|max:100',
            'state'                => 'nullable|string|max:100',
            'postal_code'          => 'nullable|string|max:20',
            'country'              => 'required|string|max:100',
            'is_default'           => 'boolean',
            'latitude'             => 'required_with:longitude|nullable|numeric',
            'longitude'            => 'required_with:latitude|nullable|numeric',
            'building_number'      => 'nullable|string|max:50',
            'floor_number'         => 'nullable|string|max:10',
            'apartment_number'     => 'nullable|string|max:50',
            'additional_directions'=> 'nullable|string|max:1000',
        ]);

        $data['user_id'] = $request->user()->id;

        if ($data['is_default'] ?? false) {
            Address::where('user_id', $data['user_id'])->update(['is_default' => false]);
        }

        $address = Address::create($data);

        return response()->json([
            'message' => __('address.added'),
            'address' => $address,
        ], 201);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => __('address.unauthorized')], 403);
        }

        $data = $request->validate([
            'address_type'         => 'nullable|string|max:50',
            'street_address'       => 'sometimes|string|max:500',
            'city'                 => 'sometimes|string|max:100',
            'state'                => 'nullable|string|max:100',
            'postal_code'          => 'nullable|string|max:20',
            'country'              => 'sometimes|string|max:100',
            'is_default'           => 'boolean',
            'latitude'             => 'required_with:longitude|nullable|numeric',
            'longitude'            => 'required_with:latitude|nullable|numeric',
            'building_number'      => 'nullable|string|max:50',
            'floor_number'         => 'nullable|string|max:10',
            'apartment_number'     => 'nullable|string|max:50',
            'additional_directions'=> 'nullable|string|max:1000',
        ]);

        if ($data['is_default'] ?? false) {
            Address::where('user_id', $address->user_id)->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json([
            'message' => __('address.updated'),
            'address' => $address->fresh(),
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => __('address.unauthorized')], 403);
        }

        $address->delete();

        return response()->json(['message' => __('address.deleted')]);
    }
}
