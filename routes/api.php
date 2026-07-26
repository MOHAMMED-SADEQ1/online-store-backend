<?php

use Illuminate\Support\Facades\Route;

// Admin API (full access)
require __DIR__ . '/admin.php';

// Customer API (public + customer-only)
require __DIR__ . '/customer.php';

// Webhooks (no auth, POST only, CSRF exempt via api middleware)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    require __DIR__ . '/webhooks.php';
});
