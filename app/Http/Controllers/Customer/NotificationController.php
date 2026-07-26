<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        $unreadCount = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        $data = $notifications->map(fn($n) => [
            'id'        => $n->id,
            'type'      => $n->type,
            'title_ar'  => $n->title_ar,
            'title_en'  => $n->title_en,
            'body_ar'   => $n->body_ar,
            'body_en'   => $n->body_en,
            'data'      => $n->data,
            'is_read'   => $n->is_read,
            'created_at'=> $n->created_at,
        ]);

        return response()->json([
            'data'         => $data,
            'unread_count' => $unreadCount,
            'total'        => $notifications->total(),
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== request()->user()->id) {
            abort(403, __('notification.unauthorized'));
        }

        $notification->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => __('notification.marked_read')]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => __('notification.all_marked_read')]);
    }
}
