<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::when($request->role, fn($q, $v) => $q->where('role', $v))
            ->when($request->search, fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('username', 'like', "%{$v}%")
                  ->orWhere('email', 'like', "%{$v}%")
                  ->orWhere('first_name', 'like', "%{$v}%")
                  ->orWhere('last_name', 'like', "%{$v}%")
                  ->orWhere('phone', 'like', "%{$v}%");
            }))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->is_active))
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount('orders', 'reviews');

        return response()->json(['user' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username'   => 'nullable|string|max:50|unique:users,username',
            'email'      => 'required|email|max:100|unique:users,email',
            'password'   => 'nullable|string|min:8',
            'first_name' => 'nullable|string|max:50',
            'last_name'  => 'nullable|string|max:50',
            'phone'      => 'nullable|string|max:20',
            'role'       => 'sometimes|in:customer,admin,vendor',
            'is_active'  => 'boolean',
            'locale'     => 'sometimes|string|max:10',
        ]);

        if ($data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user = User::create($data);

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => $user,
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'username'   => 'sometimes|string|max:50|unique:users,username,' . $user->id,
            'email'      => 'sometimes|email|max:100|unique:users,email,' . $user->id,
            'password'   => 'sometimes|string|min:8',
            'first_name' => 'nullable|string|max:50',
            'last_name'  => 'nullable|string|max:50',
            'phone'      => 'nullable|string|max:20',
            'role'       => 'sometimes|in:customer,admin,vendor',
            'is_active'  => 'boolean',
            'locale'     => 'sometimes|string|max:10',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully.',
            'user'    => $user->fresh(),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last admin account.',
                'code'    => 409,
            ], 409);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
