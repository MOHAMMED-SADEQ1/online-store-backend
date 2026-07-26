<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::with('user:id,username,email')
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->event_type, fn($q, $v) => $q->where('event_type', $v))
            ->when($request->auditable_type, fn($q, $v) => $q->where('auditable_type', $v))
            ->when($request->auditable_id, fn($q, $v) => $q->where('auditable_id', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($logs);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user');

        return response()->json(['log' => $auditLog]);
    }
}
