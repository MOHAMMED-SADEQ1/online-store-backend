<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $exists = NewsletterSubscriber::where('email', $data['email'])->first();

        if ($exists) {
            if ($exists->is_active) {
                return response()->json(['message' => __('newsletter.already_subscribed')]);
            }
            $exists->update(['is_active' => true, 'subscribed_at' => now(), 'unsubscribed_at' => null]);
            return response()->json(['message' => __('newsletter.resubscribed')]);
        }

        NewsletterSubscriber::create([
            'email'         => $data['email'],
            'is_active'     => true,
            'subscribed_at' => now(),
        ]);

        return response()->json(['message' => __('newsletter.subscribed')]);
    }
}
