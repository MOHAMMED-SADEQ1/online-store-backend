<?php

namespace App\Observers;

use App\Models\Coupon;
use App\Services\AuditService;

class CouponObserver
{
    public function __construct(protected AuditService $auditService) {}

    public function created(Coupon $coupon): void
    {
        $this->auditService->log('created', $coupon, null, $coupon);
    }

    public function updated(Coupon $coupon): void
    {
        $before = new Coupon();
        $before->setRawAttributes($coupon->getOriginal());
        $this->auditService->log('updated', $coupon, $before, $coupon);
    }

    public function deleted(Coupon $coupon): void
    {
        $this->auditService->log('deleted', $coupon, $coupon, null);
    }
}
