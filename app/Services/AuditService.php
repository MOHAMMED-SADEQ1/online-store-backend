<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(string $action, Model $entity, ?Model $before = null, ?Model $after = null): void
    {
        $request = request();
        $user = $request?->user();

        AuditLog::create([
            'user_id'        => $user?->id,
            'action'         => $action,
            'entity_type'    => get_class($entity),
            'entity_id'      => $entity->getKey(),
            'before_payload' => $before ? $this->sanitize($before->toArray()) : null,
            'after_payload'  => $after ? $this->sanitize($after->toArray()) : null,
            'ip_address'     => $request?->ip(),
            'user_agent'     => $request?->userAgent(),
        ]);
    }

    public function sanitize(array $data): array
    {
        $hidden = ['password', 'remember_token'];

        return array_diff_key($data, array_flip($hidden));
    }
}
