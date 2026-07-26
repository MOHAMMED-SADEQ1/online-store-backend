<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerCartResource;
use App\Mail\OtpMail;
use App\Models\Cart;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => 'required|string|max:255',
        ]);

        $identifier = $data['identifier'];
        $type = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if ($type === 'phone') {
            return response()->json([
                'message' => __('auth.phone_not_supported'),
            ], 422);
        }

        OtpCode::where('identifier', $identifier)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'identifier'      => $identifier,
            'identifier_type' => $type,
            'otp'             => $otp,
            'expires_at'      => now()->addMinutes(5),
        ]);

        Mail::to($identifier)->send(new OtpMail($otp, $identifier));

        return response()->json([
            'message'         => __('auth.otp_sent'),
            'identifier_type' => $type,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => 'required|string|max:255',
            'otp'        => 'required|string|size:6',
        ]);

        $otpRecord = OtpCode::valid($data['identifier'], $data['otp'])->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => __('auth.otp_invalid'),
            ], 422);
        }

        $otpRecord->update([
            'used_at'     => now(),
            'verified_at' => now(),
        ]);

        $user = User::where('email', $data['identifier'])->first();

        if ($user) {
            $user->tokens()->where('name', 'customer-token')->delete();
            $token = $user->createToken('customer-token')->plainTextToken;

            return response()->json([
                'status'  => 'login',
                'token'   => $token,
                'user'    => $this->userData($user),
                'is_new'  => false,
            ]);
        }

        $tempToken = Str::random(60);

        $otpRecord->update(['temp_token' => $tempToken]);

        return response()->json([
            'status'          => 'register_required',
            'identifier'      => $data['identifier'],
            'identifier_type' => $otpRecord->identifier_type,
            'temp_token'      => $tempToken,
            'expires_in'      => 900,
        ]);
    }

    public function completeRegistration(Request $request): JsonResponse
    {
        $data = $request->validate([
            'temp_token' => 'required|string|size:60',
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
        ]);

        $otpRecord = OtpCode::where('temp_token', $data['temp_token'])
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'message' => __('auth.registration_token_invalid'),
            ], 422);
        }

        $identifier = $otpRecord->identifier;

        if (User::where('email', $identifier)->exists()) {
            return response()->json([
                'message' => __('auth.email_already_registered'),
            ], 409);
        }

        $emailPrefix = strstr($identifier, '@', true);
        $username = $emailPrefix . '_' . random_int(1000, 9999);

        $user = User::create([
            'username'   => $username,
            'email'      => $identifier,
            'password'   => null,
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'role'       => 'customer',
            'locale'     => $request->input('locale', app()->getLocale()),
        ]);

        $otpRecord->update(['temp_token' => null]);

        // Award signup bonus points
        try {
            app(LoyaltyService::class)->awardSignupBonus($user);
        } catch (\Exception $e) {
            \Log::warning('Failed to award signup bonus', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        $token = $user->createToken('customer-token')->plainTextToken;

        return response()->json([
            'status' => 'registered',
            'token'  => $token,
            'user'   => $this->userData($user),
            'is_new' => true,
        ], 201);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userData($request->user()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name'   => 'sometimes|string|max:100',
            'last_name'    => 'sometimes|string|max:100',
            'phone'        => 'sometimes|string|max:50',
            'date_of_birth'=> 'sometimes|date',
            'locale'       => 'sometimes|string|in:ar,en',
        ]);

        $user->update($data);

        return response()->json([
            'message' => __('auth.profile_updated'),
            'user'    => $this->userData($user->fresh()),
        ]);
    }

    public function mergeCart(Request $request): JsonResponse
    {
        $data = $request->validate([
            'guest_token' => 'required|string|max:255',
        ]);

        $guestCart = Cart::where('session_id', $data['guest_token'])->first();
        if (!$guestCart || $guestCart->items->isEmpty()) {
            return response()->json(['message' => __('auth.no_guest_cart')]);
        }

        $userCart = Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['session_id' => null]
        );

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('variant_id', $guestItem->variant_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
            } else {
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        $guestCart->delete();

        $userCart->load(['items.product.images' => fn($q) => $q->whereNull('variant_id'), 'items.variant']);

        return response()->json([
            'message' => __('auth.cart_merged'),
            'cart'    => new CustomerCartResource($userCart),
        ]);
    }

    private function userData(User $user): array
    {
        return [
            'id'           => $user->id,
            'username'     => $user->username,
            'email'        => $user->email,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'phone'        => $user->phone,
            'date_of_birth'=> $user->date_of_birth,
            'locale'       => $user->locale,
            'role'         => $user->role,
        ];
    }
}
