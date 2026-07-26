<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::with('user:id,username,email')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->when($request->is_read !== null, fn($q) => $q->where('is_read', $request->is_read))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($notifications);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'  => 'required|exists:users,id',
            'type'     => 'required|string|max:100',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'body_ar'  => 'nullable|string',
            'body_en'  => 'nullable|string',
            'data'     => 'nullable|json',
        ]);

        if (isset($data['data'])) {
            $data['data'] = json_decode($data['data'], true);
        }

        $notification = Notification::create($data);

        return response()->json([
            'message'      => 'Notification sent.',
            'notification' => $notification->load('user'),
        ], 201);
    }

    public function show(Notification $notification): JsonResponse
    {
        $notification->load('user');

        return response()->json(['notification' => $notification]);
    }

    public function update(Request $request, Notification $notification): JsonResponse
    {
        $data = $request->validate([
            'type'     => 'sometimes|string|max:100',
            'title_ar' => 'sometimes|string|max:255',
            'title_en' => 'sometimes|string|max:255',
            'body_ar'  => 'nullable|string',
            'body_en'  => 'nullable|string',
            'is_read'  => 'boolean',
            'data'     => 'nullable|json',
        ]);

        if (isset($data['data'])) {
            $data['data'] = json_decode($data['data'], true);
        }
        if (isset($data['is_read']) && $data['is_read']) {
            $data['read_at'] = now();
        }

        $notification->update($data);

        return response()->json([
            'message'      => 'Notification updated.',
            'notification' => $notification->fresh()->load('user'),
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        $notification->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }
}
