<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::with('user:id,username,email')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->address_type, fn($q, $v) => $q->where('address_type', $v))
            ->when($request->search, fn($q, $v) => $q->where('street_address', 'like', "%{$v}%")
                ->orWhere('city', 'like', "%{$v}%"))
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'              => 'required|exists:users,id',
            'address_type'         => 'sometimes|in:home,work,other,shipping,billing,both',
            'street_address'       => 'required|string|max:255',
            'city'                 => 'required|string|max:100',
            'state'                => 'nullable|string|max:100',
            'postal_code'          => 'nullable|string|max:20',
            'country'              => 'sometimes|string|max:100',
            'is_default'           => 'boolean',
            'latitude'             => 'nullable|numeric',
            'longitude'            => 'nullable|numeric',
            'building_number'      => 'nullable|string|max:50',
            'floor_number'         => 'nullable|string|max:10',
            'apartment_number'     => 'nullable|string|max:50',
            'additional_directions'=> 'nullable|string|max:500',
        ]);

        $address = Address::create($data);

        return response()->json([
            'message' => 'Address created.',
            'address' => $address->load('user'),
        ], 201);
    }

    public function show(Address $address): JsonResponse
    {
        $address->load('user');

        return response()->json(['address' => $address]);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        $data = $request->validate([
            'address_type'         => 'sometimes|in:home,work,other,shipping,billing,both',
            'street_address'       => 'sometimes|string|max:255',
            'city'                 => 'sometimes|string|max:100',
            'state'                => 'nullable|string|max:100',
            'postal_code'          => 'nullable|string|max:20',
            'country'              => 'sometimes|string|max:100',
            'is_default'           => 'boolean',
            'latitude'             => 'nullable|numeric',
            'longitude'            => 'nullable|numeric',
            'building_number'      => 'nullable|string|max:50',
            'floor_number'         => 'nullable|string|max:10',
            'apartment_number'     => 'nullable|string|max:50',
            'additional_directions'=> 'nullable|string|max:500',
        ]);

        $address->update($data);

        return response()->json([
            'message' => 'Address updated.',
            'address' => $address->fresh()->load('user'),
        ]);
    }

    public function destroy(Address $address): JsonResponse
    {
        $address->delete();

        return response()->json(['message' => 'Address deleted.']);
    }
}
