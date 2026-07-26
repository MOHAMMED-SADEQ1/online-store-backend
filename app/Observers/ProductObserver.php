<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\AuditService;

class ProductObserver
{
    public function __construct(protected AuditService $auditService) {}

    public function created(Product $product): void
    {
        $this->auditService->log('created', $product, null, $product);
    }

    public function updated(Product $product): void
    {
        $before = new Product();
        $before->setRawAttributes($product->getOriginal());
        $this->auditService->log('updated', $product, $before, $product);
    }

    public function deleted(Product $product): void
    {
        $this->auditService->log('deleted', $product, $product, null);
    }
}
