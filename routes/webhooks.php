<?php

use App\Http\Controllers\Webhook\MoyasarWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('moyasar', [MoyasarWebhookController::class, 'handle'])->name('webhooks.moyasar');
