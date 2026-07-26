<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\AuditService;

class SettingObserver
{
    public function __construct(protected AuditService $auditService) {}

    public function created(Setting $setting): void
    {
        $this->auditService->log('created', $setting, null, $setting);
    }

    public function updated(Setting $setting): void
    {
        $before = new Setting();
        $before->setRawAttributes($setting->getOriginal());
        $this->auditService->log('updated', $setting, $before, $setting);
    }

    public function deleted(Setting $setting): void
    {
        $this->auditService->log('deleted', $setting, $setting, null);
    }
}
