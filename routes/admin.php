<?php

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CartController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CompareController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FlashSaleController;
use App\Http\Controllers\Admin\GiftCardController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\LoyaltyPointController;
use App\Http\Controllers\Admin\LoyaltyTierController;
use App\Http\Controllers\Admin\NewsletterSubscriberController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PriceAlertController;
use App\Http\Controllers\Admin\PriceHistoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RecentlyViewedController;
use App\Http\Controllers\Admin\ReferralCodeController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\ShippingCityController;
use App\Http\Controllers\Admin\ShippingZoneController;
use App\Http\Controllers\Admin\StockAlertController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    // ============================================================
    // Authentication (no middleware)
    // ============================================================
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login')->middleware('throttle:5,1');

    // ============================================================
    // Protected Routes (auth:sanctum + admin)
    // ============================================================
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        // Advanced Statistics
        Route::get('dashboard/monthly-sales', [DashboardController::class, 'monthlySales'])->name('dashboard.monthly-sales');
        Route::get('dashboard/top-products', [DashboardController::class, 'topProducts'])->name('dashboard.top-products');
        Route::get('dashboard/customer-analytics', [DashboardController::class, 'customerAnalytics'])->name('dashboard.customer-analytics');
        Route::get('dashboard/conversion-rate', [DashboardController::class, 'conversionRate'])->name('dashboard.conversion-rate');
        Route::get('dashboard/realtime', [DashboardController::class, 'realtime'])->name('dashboard.realtime');
        Route::get('dashboard/sales-by-date', [DashboardController::class, 'salesByDate'])->name('dashboard.sales-by-date');
        Route::get('dashboard/fulfillment', [DashboardController::class, 'fulfillment'])->name('dashboard.fulfillment');
        Route::get('dashboard/product-performance', [DashboardController::class, 'productPerformance'])->name('dashboard.product-performance');

        // ============================================================
        // Products & EAV System
        // ============================================================

        // Products (full API resource)
        Route::apiResource('products', ProductController::class);

        // Product Variants (nested under products)
        Route::get('products/{product}/variants', [VariantController::class, 'index'])->name('products.variants.index');
        Route::post('products/{product}/variants', [VariantController::class, 'store'])->name('products.variants.store');
        Route::put('products/{product}/variants/{variant}', [VariantController::class, 'update'])->name('products.variants.update');
        Route::delete('products/{product}/variants/{variant}', [VariantController::class, 'destroy'])->name('products.variants.destroy');

        // Product Images (nested under products)
        Route::get('products/{product}/images', [ImageController::class, 'index'])->name('products.images.index');
        Route::post('products/{product}/images', [ImageController::class, 'store'])->name('products.images.store');
        Route::get('products/{product}/images/{image}', [ImageController::class, 'show'])->name('products.images.show');
        Route::put('products/{product}/images/{image}', [ImageController::class, 'update'])->name('products.images.update');
        Route::delete('products/{product}/images/{image}', [ImageController::class, 'destroy'])->name('products.images.destroy');

        // Variant Images (nested under variants)
        Route::get('products/{product}/variants/{variant}/images', [ImageController::class, 'variantImages'])->name('products.variants.images');

        // Categories (hierarchical)
        Route::apiResource('categories', CategoryController::class);

        // Tags
        Route::apiResource('tags', TagController::class)->except('show');

        // Attributes (EAV)
        Route::apiResource('attributes', AttributeController::class);
        Route::post('attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');
        Route::put('attributes/{attribute}/values/{value}', [AttributeController::class, 'updateValue'])->name('attributes.values.update');
        Route::delete('attributes/{attribute}/values/{value}', [AttributeController::class, 'destroyValue'])->name('attributes.values.destroy');

        // ============================================================
        // Orders, Payments & Shipping
        // ============================================================

        // Orders
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        // Order Shipping (nested under orders)
        Route::get('orders/{order}/shipping', [ShippingController::class, 'index'])->name('orders.shipping.index');
        Route::post('orders/{order}/shipping', [ShippingController::class, 'store'])->name('orders.shipping.store');
        Route::get('orders/{order}/shipping/{shipping}', [ShippingController::class, 'show'])->name('orders.shipping.show');
        Route::put('orders/{order}/shipping/{shipping}', [ShippingController::class, 'update'])->name('orders.shipping.update');
        Route::delete('orders/{order}/shipping/{shipping}', [ShippingController::class, 'destroy'])->name('orders.shipping.destroy');

        // Payments
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

        // Payment Methods
        Route::apiResource('payment-methods', PaymentMethodController::class)->parameters(['payment-methods' => 'paymentMethod']);

        // ============================================================
        // Customers & Users
        // ============================================================

        // Users (customers, admins, vendors)
        Route::apiResource('users', UserController::class);

        // Addresses (all user addresses)
        Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::get('addresses/{address}', [AddressController::class, 'show'])->name('addresses.show');
        Route::put('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

        // User Sub-Entities
        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
        Route::get('wishlist/{wishlist}', [WishlistController::class, 'show'])->name('wishlist.show');
        Route::put('wishlist/{wishlist}', [WishlistController::class, 'update'])->name('wishlist.update');
        Route::delete('wishlist/{wishlist}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

        Route::get('compare', [CompareController::class, 'index'])->name('compare.index');
        Route::post('compare', [CompareController::class, 'store'])->name('compare.store');
        Route::get('compare/{compareItem}', [CompareController::class, 'show'])->name('compare.show');
        Route::delete('compare/{compareItem}', [CompareController::class, 'destroy'])->name('compare.destroy');

        Route::get('carts', [CartController::class, 'index'])->name('carts.index');
        Route::get('carts/{cart}', [CartController::class, 'show'])->name('carts.show');
        Route::delete('carts/{cart}', [CartController::class, 'destroy'])->name('carts.destroy');
        Route::get('carts/{cart}/items', [CartController::class, 'items'])->name('carts.items');
        Route::put('carts/{cart}/items/{item}', [CartController::class, 'updateItem'])->name('carts.items.update');
        Route::delete('carts/{cart}/items/{item}', [CartController::class, 'removeItem'])->name('carts.items.remove');

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications', [NotificationController::class, 'store'])->name('notifications.store');
        Route::get('notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::put('notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

        // ============================================================
        // Marketing & Discounts
        // ============================================================

        // Coupons
        Route::apiResource('coupons', CouponController::class);
        Route::post('coupons/validate', [CouponController::class, 'validateCoupon'])->name('coupons.validate');

        // Reviews
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::put('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
        Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        // Price Alerts
        Route::get('price-alerts', [PriceAlertController::class, 'index'])->name('price-alerts.index');
        Route::post('price-alerts', [PriceAlertController::class, 'store'])->name('price-alerts.store');
        Route::get('price-alerts/{priceAlert}', [PriceAlertController::class, 'show'])->name('price-alerts.show');
        Route::put('price-alerts/{priceAlert}', [PriceAlertController::class, 'update'])->name('price-alerts.update');
        Route::delete('price-alerts/{priceAlert}', [PriceAlertController::class, 'destroy'])->name('price-alerts.destroy');

        // Stock Alerts
        Route::get('stock-alerts', [StockAlertController::class, 'index'])->name('stock-alerts.index');
        Route::post('stock-alerts', [StockAlertController::class, 'store'])->name('stock-alerts.store');
        Route::get('stock-alerts/{stockAlert}', [StockAlertController::class, 'show'])->name('stock-alerts.show');
        Route::put('stock-alerts/{stockAlert}', [StockAlertController::class, 'update'])->name('stock-alerts.update');
        Route::delete('stock-alerts/{stockAlert}', [StockAlertController::class, 'destroy'])->name('stock-alerts.destroy');

        // ============================================================
        // Inventory & Pricing
        // ============================================================

        // Price History
        Route::get('price-history', [PriceHistoryController::class, 'index'])->name('price-history.index');
        Route::post('price-history', [PriceHistoryController::class, 'store'])->name('price-history.store');
        Route::get('price-history/{priceHistory}', [PriceHistoryController::class, 'show'])->name('price-history.show');
        Route::delete('price-history/{priceHistory}', [PriceHistoryController::class, 'destroy'])->name('price-history.destroy');

        // Inventory Transactions
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('inventory/{inventoryTransaction}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::put('inventory/{inventoryTransaction}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('inventory/{inventoryTransaction}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

        // ============================================================
        // Configuration
        // ============================================================

        // Tax Rates
        Route::apiResource('tax-rates', TaxRateController::class)->parameters(['tax-rates' => 'taxRate']);

        // Shipping Zones
        Route::apiResource('shipping-zones', ShippingZoneController::class)->parameters(['shipping-zones' => 'shippingZone']);

        // Shipping Cities (nested under shipping-zones or standalone)
        Route::apiResource('shipping-cities', ShippingCityController::class)->parameters(['shipping-cities' => 'shippingCity']);

        // Brands
        Route::apiResource('brands', BrandController::class);

        // Flash Sales
        Route::apiResource('flash-sales', FlashSaleController::class)->parameters(['flash-sales' => 'flashSale']);

        // Newsletter Subscribers
        Route::apiResource('newsletter-subscribers', NewsletterSubscriberController::class)->parameters(['newsletter-subscribers' => 'newsletterSubscriber']);

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        // ============================================================
        // Analytics & Monitoring
        // ============================================================

        // Recently Viewed
        Route::get('recently-viewed', [RecentlyViewedController::class, 'index'])->name('recently-viewed.index');
        Route::post('recently-viewed', [RecentlyViewedController::class, 'store'])->name('recently-viewed.store');
        Route::get('recently-viewed/{recentlyViewed}', [RecentlyViewedController::class, 'show'])->name('recently-viewed.show');
        Route::delete('recently-viewed/{recentlyViewed}', [RecentlyViewedController::class, 'destroy'])->name('recently-viewed.destroy');

        // Audit Log (read-only)
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

        // Return Requests (admin management)
        Route::get('return-requests', [ReturnRequestController::class, 'index'])->name('return-requests.index');
        Route::get('return-requests/{returnRequest}', [ReturnRequestController::class, 'show'])->name('return-requests.show');
        Route::patch('return-requests/{returnRequest}/status', [ReturnRequestController::class, 'updateStatus'])->name('return-requests.status');
        Route::delete('return-requests/{returnRequest}', [ReturnRequestController::class, 'destroy'])->name('return-requests.destroy');

        // ============================================================
        // Loyalty Program
        // ============================================================

        // Loyalty Tiers
        Route::apiResource('loyalty-tiers', LoyaltyTierController::class)->parameters(['loyalty-tiers' => 'loyaltyTier']);

        // Loyalty Points (with transactions)
        Route::get('loyalty-points', [LoyaltyPointController::class, 'index'])->name('loyalty-points.index');
        Route::get('loyalty-points/{loyaltyPoint}', [LoyaltyPointController::class, 'show'])->name('loyalty-points.show');
        Route::post('loyalty-points/{loyaltyPoint}/adjust', [LoyaltyPointController::class, 'adjustBalance'])->name('loyalty-points.adjust');
        Route::get('loyalty-transactions', [LoyaltyPointController::class, 'transactions'])->name('loyalty-transactions.index');
        Route::get('loyalty-transactions/user/{user}', [LoyaltyPointController::class, 'transactions'])->name('loyalty-transactions.user');

        // Referral Codes
        Route::get('referral-codes', [ReferralCodeController::class, 'index'])->name('referral-codes.index');
        Route::get('referral-codes/{referralCode}', [ReferralCodeController::class, 'show'])->name('referral-codes.show');
        Route::put('referral-codes/{referralCode}', [ReferralCodeController::class, 'update'])->name('referral-codes.update');
        Route::get('referral-redemptions', [ReferralCodeController::class, 'redemptions'])->name('referral-redemptions.index');
        Route::get('referral-redemptions/code/{referralCode}', [ReferralCodeController::class, 'redemptions'])->name('referral-redemptions.by-code');

        // ============================================================
        // Gift Cards
        // ============================================================

        Route::get('gift-cards', [GiftCardController::class, 'index'])->name('gift-cards.index');
        Route::get('gift-cards/{giftCard}', [GiftCardController::class, 'show'])->name('gift-cards.show');
        Route::put('gift-cards/{giftCard}', [GiftCardController::class, 'update'])->name('gift-cards.update');
        Route::delete('gift-cards/{giftCard}', [GiftCardController::class, 'destroy'])->name('gift-cards.destroy');

        // ============================================================
        // System Monitoring
        // ============================================================

        Route::get('system/failed-jobs', [SystemController::class, 'failedJobs'])->name('system.failed-jobs');
        Route::get('system/failed-jobs/{id}', [SystemController::class, 'showFailedJob'])->name('system.failed-jobs.show');
        Route::post('system/failed-jobs/{id}/retry', [SystemController::class, 'retryFailedJob'])->name('system.failed-jobs.retry');
        Route::get('system/job-batches', [SystemController::class, 'jobBatches'])->name('system.job-batches');
        Route::get('system/job-batches/{id}', [SystemController::class, 'showJobBatch'])->name('system.job-batches.show');

        // Jobs Queue
        Route::get('system/jobs', [SystemController::class, 'jobs'])->name('system.jobs');
    });
});
