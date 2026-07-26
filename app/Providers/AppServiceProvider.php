<?php

namespace App\Providers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Observers\CouponObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\SettingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\AuditService::class);
    }

    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
        Coupon::observe(CouponObserver::class);
        Setting::observe(SettingObserver::class);
    }
}
