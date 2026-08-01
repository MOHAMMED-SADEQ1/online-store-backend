<?php

use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CategoryController;
use App\Http\Controllers\Customer\CompareController;
use App\Http\Controllers\Customer\CouponController;
use App\Http\Controllers\Customer\FlashSaleController;
use App\Http\Controllers\Customer\FilterController;
use App\Http\Controllers\Customer\GiftCardController;
use App\Http\Controllers\Customer\GuestCartController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\LoyaltyController;
use App\Http\Controllers\Customer\NewsletterController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\InvoiceController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\ReturnController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\SearchController;
use App\Http\Controllers\Customer\ShareController;
use App\Http\Controllers\Customer\ShippingController;
use App\Http\Controllers\Customer\WishlistController;
use Illuminate\Support\Facades\Route;

$customerRoutes = function () {

    // ============================================================
    // Public Routes (no auth)
    // ============================================================

    // OTP Authentication (rate-limited)
    Route::post('auth/send-otp', [AuthController::class, 'sendOtp'])->name('auth.send-otp')->middleware('throttle:3,1');
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.verify-otp')->middleware('throttle:10,1');
    Route::post('auth/complete-registration', [AuthController::class, 'completeRegistration'])->name('auth.complete-registration')->middleware('throttle:10,1');

    // Home
    Route::get('home', [HomeController::class, 'index'])->name('home');

    // Categories
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    // Products (traditional search - backwards compatible)
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/filters', [FilterController::class, 'filters'])->name('products.filters');
    Route::get('products/search-suggestions', [FilterController::class, 'searchSuggestions'])->name('products.search-suggestions');
    Route::get('products/{slug}/p{product}', [ProductController::class, 'show'])->name('products.show')->whereNumber('product');
    Route::get('products/{slug}/p{product}/related', [ProductController::class, 'related'])->name('products.related')->whereNumber('product');
    Route::get('products/{slug}/p{product}/frequently-bought-together', [ProductController::class, 'frequentlyBought'])->name('products.frequently-bought')->whereNumber('product');
    Route::get('products/{slug}/p{product}/share', [ShareController::class, 'links'])->name('products.share')->whereNumber('product');

    // ════════════════════════════════════════════════════════════
    // 🧠 Advanced AI Search (Meilisearch-powered)
    // ════════════════════════════════════════════════════════════

    // Faceted filter options (cached)
    Route::get('search/filters', [SearchController::class, 'filters'])->name('search.filters');

    // Auto-complete suggestions (instant)
    Route::get('search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

    // Full-text search with faceted filters, spell correction, sorting
    Route::get('search', [SearchController::class, 'search'])->name('search');

    // Recommendations (public)
    Route::get('recommendations/top-selling', [HomeController::class, 'topSelling'])->name('recommendations.top-selling');

    // Coupon validation (rate-limited)
    Route::post('coupons/validate', [CouponController::class, 'validateCoupon'])->name('coupons.validate')->middleware('throttle:30,1');

    // Shipping
    Route::get('shipping/cities', [ShippingController::class, 'cities'])->name('shipping.cities');
    Route::post('shipping/calculate', [ShippingController::class, 'calculate'])->name('shipping.calculate');

    // Flash Sales
    Route::get('flash-sales', [FlashSaleController::class, 'index'])->name('flash-sales.index');

    // Compare
    Route::post('compare', [CompareController::class, 'compare'])->name('compare');

    // Guest Cart (rate-limited)
    Route::post('guest/cart', [GuestCartController::class, 'store'])->name('guest.cart.store')->middleware('throttle:20,1');

    // Newsletter
    Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

    // Return Policy
    Route::get('return-policy', [ReturnController::class, 'policy'])->name('return-policy');

    // ════════════════════════════════════════════════════════════
    // Customer Routes (auth:sanctum + customer)
    // ════════════════════════════════════════════════════════════
    Route::middleware(['auth:sanctum', 'customer'])->group(function () {

        // Profile
        Route::get('profile', [AuthController::class, 'profile'])->name('profile');
        Route::put('profile', [AuthController::class, 'updateProfile'])->name('profile.update');

        // Merge guest cart
        Route::post('auth/merge-cart', [AuthController::class, 'mergeCart'])->name('auth.merge-cart');

        // Addresses
        Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

        // Cart
        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('cart', [CartController::class, 'store'])->name('cart.store');

        // ⚠️ Important: coupon routes must be registered BEFORE cart/{cartItem}
        // so the static 'coupon' segment isn't captured by the dynamic {cartItem}.
        Route::post('cart/apply-coupon', [CartController::class, 'applyCoupon'])->name('cart.apply-coupon');
        Route::match(['delete', 'post'], 'cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.remove-coupon');

        Route::put('cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

        // Orders
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store')->middleware('throttle:10,1');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('orders/{order}/tracking', [OrderController::class, 'tracking'])->name('orders.tracking');
        Route::get('orders/{order}/invoice', [InvoiceController::class, 'download'])->name('orders.invoice');
        Route::get('orders/{order}/invoice-preview', [InvoiceController::class, 'preview'])->name('orders.invoice-preview');

        // Checkout / Payment (no order creation before payment)
        Route::get('payment-methods', [CheckoutController::class, 'paymentMethods'])->name('payment-methods');
        Route::get('payment/config', [CheckoutController::class, 'paymentConfig'])->name('payment.config');
        Route::post('checkout/pay', [CheckoutController::class, 'pay'])->name('checkout.pay');
        Route::post('checkout/{checkout}/verify', [CheckoutController::class, 'verify'])->name('checkout.verify');
        Route::get('orders/{order}/returns', [ReturnController::class, 'index'])->name('orders.returns.index');
        Route::post('orders/{order}/returns', [ReturnController::class, 'store'])->name('orders.returns.store');

        // Wishlist
        Route::get('wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
        Route::delete('wishlist/{wishlist}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

        // Reviews
        Route::get('reviews/purchasable', [ReviewController::class, 'purchasable'])->name('reviews.purchasable');
        Route::post('reviews', [ReviewController::class, 'store'])->name('reviews.store');

        // Recently Viewed
        Route::post('recently-viewed', [ProductController::class, 'recentlyViewed'])->name('recently-viewed');

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::put('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::put('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        // ═══════════════════════════════════════════════════════
        // 🎯 Loyalty Program (Points, Tiers, Referrals)
        // ═══════════════════════════════════════════════════════

        // Points & Tier info
        Route::get('loyalty/points', [LoyaltyController::class, 'points'])->name('loyalty.points');
        Route::get('loyalty/transactions', [LoyaltyController::class, 'transactions'])->name('loyalty.transactions');
        Route::get('loyalty/tiers', [LoyaltyController::class, 'tiers'])->name('loyalty.tiers');

        // Points estimation & redemption
        Route::post('loyalty/estimate', [LoyaltyController::class, 'estimatePointsValue'])->name('loyalty.estimate');

        // Referral system
        Route::get('loyalty/referral-code', [LoyaltyController::class, 'referralCode'])->name('loyalty.referral-code');
        Route::get('loyalty/referral-history', [LoyaltyController::class, 'referralHistory'])->name('loyalty.referral-history');
        Route::post('loyalty/referral/register', [LoyaltyController::class, 'registerReferral'])->name('loyalty.referral.register');

        // ═══════════════════════════════════════════════════════
        // 🎁 Gift Cards
        // ═══════════════════════════════════════════════════════

        // Purchase a gift card
        Route::post('gift-cards/purchase', [GiftCardController::class, 'purchase'])->name('gift-cards.purchase');

        // My purchased gift cards
        Route::get('gift-cards/purchased', [GiftCardController::class, 'purchased'])->name('gift-cards.purchased');

        // Validate & check gift card balance
        Route::post('gift-cards/validate', [GiftCardController::class, 'validate'])->name('gift-cards.validate');
        Route::post('gift-cards/balance', [GiftCardController::class, 'balance'])->name('gift-cards.balance');
    });
};

// Without locale prefix — locale is detected via SetLocale middleware from URL, header, or query param
Route::prefix('customer')->name('customer.')->group($customerRoutes);
