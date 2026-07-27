<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterSubscriberController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'subscribers' => NewsletterSubscriber::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'        => 'required|email|max:100|unique:newsletter_subscribers,email',
            'is_active'    => 'boolean',
            'subscribed_at' => 'nullable|date',
        ]);

        $data['subscribed_at'] = $data['subscribed_at'] ?? now();

        $subscriber = NewsletterSubscriber::create($data);

        return response()->json([
            'message'    => 'Subscriber added successfully.',
            'subscriber' => $subscriber,
        ], 201);
    }

    public function show(NewsletterSubscriber $newsletterSubscriber): JsonResponse
    {
        return response()->json([
            'subscriber' => $newsletterSubscriber,
        ]);
    }

    public function update(Request $request, NewsletterSubscriber $newsletterSubscriber): JsonResponse
    {
        $data = $request->validate([
            'email'          => 'sometimes|email|max:100|unique:newsletter_subscribers,email,' . $newsletterSubscriber->id,
            'is_active'      => 'boolean',
            'unsubscribed_at' => 'nullable|date',
        ]);

        $newsletterSubscriber->update($data);

        return response()->json([
            'message'    => 'Subscriber updated successfully.',
            'subscriber' => $newsletterSubscriber->fresh(),
        ]);
    }

    public function destroy(NewsletterSubscriber $newsletterSubscriber): JsonResponse
    {
        $newsletterSubscriber->delete();

        return response()->json(['message' => 'Subscriber deleted successfully.']);
    }
}
