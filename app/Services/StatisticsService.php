<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\RecentlyViewed;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\PendingCheckout;
use App\Models\ReturnRequest;

class StatisticsService
{
    /**
     * ============================================================
     *  1. MONTHLY SALES — المبيعات الشهرية
     * ============================================================
     */
    public function monthlySales(?int $monthsBack = 12): array
    {
        $start = Carbon::now()->subMonths($monthsBack)->startOfMonth();

        $monthly = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $start)
            ->selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_orders,
                COALESCE(SUM(final_amount), 0) as revenue,
                COALESCE(SUM(tax_amount), 0) as tax,
                COALESCE(SUM(shipping_amount), 0) as shipping,
                COALESCE(SUM(discount_amount), 0) as discounts,
                COALESCE(AVG(final_amount), 0) as avg_order_value
            ")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill missing months with zero
        $result = [];
        for ($i = $monthsBack; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $row = $monthly->get($key);

            $result[] = [
                'month'           => $key,
                'label_ar'        => $date->locale('ar')->translatedFormat('F Y'),
                'label_en'        => $date->translatedFormat('F Y'),
                'total_orders'    => (int) ($row->total_orders ?? 0),
                'revenue'         => (float) ($row->revenue ?? 0),
                'tax'             => (float) ($row->tax ?? 0),
                'shipping'        => (float) ($row->shipping ?? 0),
                'discounts'       => (float) ($row->discounts ?? 0),
                'avg_order_value' => (float) ($row->avg_order_value ?? 0),
            ];
        }

        // Calculate growth
        $totalRevenue = array_sum(array_column($result, 'revenue'));
        $totalOrders  = array_sum(array_column($result, 'total_orders'));
        $lastMonth     = count($result) > 1 ? $result[count($result) - 1] : null;
        $prevMonth     = count($result) > 2 ? $result[count($result) - 2] : null;
        $revenueGrowth = $prevMonth && $prevMonth['revenue'] > 0
            ? round((($lastMonth['revenue'] - $prevMonth['revenue']) / $prevMonth['revenue']) * 100, 2)
            : 0;

        return [
            'months'            => $result,
            'total_revenue'     => round($totalRevenue, 2),
            'total_orders'      => $totalOrders,
            'average_monthly'   => count($result) > 0 ? round($totalRevenue / count($result), 2) : 0,
            'revenue_growth'    => $revenueGrowth,
            'best_month'        => collect($result)->sortByDesc('revenue')->first(),
        ];
    }

    /**
     * ============================================================
     *  2. TOP SELLING PRODUCTS — أفضل المنتجات مبيعاً
     * ============================================================
     */
    public function topSellingProducts(Request $request): array
    {
        $period   = $request->get('period', 'all'); // all, month, week, custom
        $limit    = (int) $request->get('limit', 20);
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $query = OrderItem::select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total_price) as revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as order_count'),
                DB::raw('AVG(order_items.unit_price) as avg_price')
            )
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'));

        // Date filtering
        if ($period === 'month') {
            $query->whereHas('order', fn($q) => $q->where('created_at', '>=', Carbon::now()->startOfMonth()));
        } elseif ($period === 'week') {
            $query->whereHas('order', fn($q) => $q->where('created_at', '>=', Carbon::now()->startOfWeek()));
        } elseif ($dateFrom) {
            $query->whereHas('order', fn($q) => $q->whereDate('created_at', '>=', Carbon::parse($dateFrom)));
        }
        if ($dateTo) {
            $query->whereHas('order', fn($q) => $q->whereDate('created_at', '<=', Carbon::parse($dateTo)));
        }

        $items = $query->groupBy('order_items.product_id')
            ->orderByDesc('total_sold')
            ->take($limit)
            ->get();

        // Load product details
        $productIds = $items->pluck('product_id');
        $products = Product::whereIn('id', $productIds)
            ->with(['firstImage', 'categories'])
            ->get()
            ->keyBy('id');

        $locale = app()->getLocale();

        return $items->map(function ($item) use ($products, $locale) {
            $product = $products->get($item->product_id);

            return [
                'product_id'    => $item->product_id,
                'name'          => $product ? $product->{'name_' . $locale} : null,
                'name_ar'       => $product?->name_ar,
                'name_en'       => $product?->name_en,
                'slug'          => $product?->slug,
                'sku'           => $product?->sku,
                'image'         => $product?->firstImage?->image_url
                    ? url('storage/' . $product->firstImage->image_url)
                    : null,
                'category'      => $product?->categories->first()?->{'name_' . $locale},
                'regular_price' => (float) ($product?->regular_price ?? 0),
                'total_sold'    => (int) $item->total_sold,
                'revenue'       => (float) $item->revenue,
                'order_count'   => (int) $item->order_count,
                'avg_price'     => (float) $item->avg_price,
            ];
        })->toArray();
    }

    /**
     * ============================================================
     *  3. CUSTOMER ANALYTICS — تحليل العملاء
     * ============================================================
     */
    public function customerAnalytics(): array
    {
        $now = Carbon::now();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // ── Customer counts ──
        $totalCustomers = User::where('role', 'customer')->count();
        $newThisMonth   = User::where('role', 'customer')->where('created_at', '>=', $thisMonth)->count();
        $newLastMonth   = User::where('role', 'customer')->whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();
        $customerGrowth = $newLastMonth > 0
            ? round((($newThisMonth - $newLastMonth) / $newLastMonth) * 100, 2)
            : ($newThisMonth > 0 ? 100 : 0);

        // ── Repeat purchase rate ──
        $customerOrderCounts = Order::where('payment_status', 'paid')
            ->select('user_id', DB::raw('COUNT(*) as order_count'))
            ->groupBy('user_id')
            ->pluck('order_count', 'user_id');

        $totalBuyers     = $customerOrderCounts->count();
        $repeatBuyers    = $customerOrderCounts->filter(fn($c) => $c >= 2)->count();
        $repeatRate      = $totalBuyers > 0 ? round(($repeatBuyers / $totalBuyers) * 100, 2) : 0;

        // ── Customer acquisition over time (monthly signups) ──
        $signups = User::where('role', 'customer')
            ->where('created_at', '>=', $now->copy()->subMonths(12)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month')
            ->pluck('count', 'month');

        $signupTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $key  = $date->format('Y-m');
            $signupTrend[] = [
                'month'    => $key,
                'label_ar' => $date->locale('ar')->translatedFormat('F'),
                'label_en' => $date->translatedFormat('F'),
                'count'    => (int) ($signups->get($key, 0)),
            ];
        }

        // ── Top customers by LTV ──
        $topCustomers = User::where('users.role', 'customer')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.payment_status', 'paid')
            ->select(
                'users.id', 'users.username', 'users.first_name', 'users.last_name', 'users.email', 'users.phone',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(orders.final_amount), 0) as total_spent'),
                DB::raw('COALESCE(AVG(orders.final_amount), 0) as avg_order_value'),
                DB::raw('MAX(orders.created_at) as last_order_date')
            )
            ->groupBy('users.id', 'users.username', 'users.first_name', 'users.last_name', 'users.email', 'users.phone')
            ->orderByDesc('total_spent')
            ->take(20)
            ->get()
            ->map(fn($u) => [
                'id'              => $u->id,
                'name'            => trim($u->first_name . ' ' . $u->last_name) ?: $u->username,
                'email'           => $u->email,
                'phone'           => $u->phone,
                'total_orders'    => (int) $u->total_orders,
                'total_spent'     => (float) $u->total_spent,
                'avg_order_value' => (float) $u->avg_order_value,
                'last_order_date' => $u->last_order_date,
            ]);

        // ── Geographic distribution (by city) ──
        $customersByCity = DB::table('addresses')
            ->join('users', 'addresses.user_id', '=', 'users.id')
            ->where('users.role', 'customer')
            ->where('addresses.is_default', 1)
            ->select('addresses.city as name', DB::raw('COUNT(DISTINCT users.id) as count'))
            ->groupBy('addresses.city')
            ->orderByDesc('count')
            ->take(15)
            ->get();

        return [
            'total_customers'    => $totalCustomers,
            'active_buyers'      => $totalBuyers,
            'repeat_buyers'      => $repeatBuyers,
            'repeat_purchase_rate' => $repeatRate,
            'new_this_month'     => $newThisMonth,
            'new_last_month'     => $newLastMonth,
            'customer_growth'    => $customerGrowth,
            'signup_trend'       => $signupTrend,
            'top_customers'      => $topCustomers,
            'by_city'            => $customersByCity,
        ];
    }

    /**
     * ============================================================
     *  4. CONVERSION RATE — معدل التحويل
     * ============================================================
     */
    public function conversionRate(): array
    {
        $now    = Carbon::now();
        $today  = $now->copy()->startOfDay();
        $thisMonth = $now->copy()->startOfMonth();

        // Total visitors (based on recently_viewed unique sessions/users per day)
        $totalVisitors = RecentlyViewed::count();
        $todayVisitors = RecentlyViewed::whereDate('viewed_at', $today)
            ->selectRaw('COUNT(DISTINCT COALESCE(user_id, session_id)) as count')
            ->value('count');

        // Total carts created
        $totalCarts            = Cart::count();
        $cartsWithItems        = Cart::whereHas('items')->count();
        $cartsWithCoupon       = Cart::whereNotNull('coupon_code')->count();

        // Total pending checkouts (started checkout process)
        $totalCheckouts        = PendingCheckout::count();
        $todayCheckouts        = PendingCheckout::whereDate('created_at', $today)->count();

        // Completed orders
        $totalPaidOrders       = Order::where('payment_status', 'paid')->count();
        $totalOrders           = Order::count();
        $todayOrders           = Order::whereDate('created_at', $today)->count();

        // Conversion funnel
        $visitorToCartRate     = $totalVisitors > 0 ? round(($cartsWithItems / $totalVisitors) * 100, 2) : 0;
        $cartToCheckoutRate    = $cartsWithItems > 0 ? round(($totalCheckouts / $cartsWithItems) * 100, 2) : 0;
        $checkoutToPaidRate    = $totalCheckouts > 0 ? round(($totalPaidOrders / $totalCheckouts) * 100, 2) : 0;
        $overallConversionRate = $totalVisitors > 0 ? round(($totalPaidOrders / $totalVisitors) * 100, 2) : 0;

        // Abandoned carts
        $abandonedCarts   = Cart::whereHas('items')
            ->whereDoesntHave('user.orders', fn($q) => $q->where('payment_status', 'paid'))
            ->count();
        $abandonmentRate  = $cartsWithItems > 0 ? round(($abandonedCarts / $cartsWithItems) * 100, 2) : 0;

        return [
            'funnel' => [
                ['stage' => 'visitors',        'label_ar' => 'الزوار',            'label_en' => 'Visitors',     'count' => $totalVisitors],
                ['stage' => 'cart',             'label_ar' => 'سلة تسوق',         'label_en' => 'Cart',         'count' => $cartsWithItems],
                ['stage' => 'checkout',         'label_ar' => 'بدأ الدفع',        'label_en' => 'Checkout',     'count' => $totalCheckouts],
                ['stage' => 'paid_orders',      'label_ar' => 'طلبات مدفوعة',     'label_en' => 'Paid Orders',  'count' => $totalPaidOrders],
            ],
            'rates' => [
                'visitor_to_cart'      => $visitorToCartRate,
                'cart_to_checkout'     => $cartToCheckoutRate,
                'checkout_to_paid'     => $checkoutToPaidRate,
                'overall_conversion'   => $overallConversionRate,
                'cart_abandonment'     => $abandonmentRate,
            ],
            'today' => [
                'visitors'    => $todayVisitors,
                'checkouts'   => $todayCheckouts,
                'orders'      => $todayOrders,
            ],
            'totals' => [
                'total_carts'     => $totalCarts,
                'carts_with_items' => $cartsWithItems,
                'carts_with_coupon' => $cartsWithCoupon,
                'abandoned_carts' => $abandonedCarts,
                'total_checkouts' => $totalCheckouts,
                'total_orders'    => $totalOrders,
                'paid_orders'     => $totalPaidOrders,
            ],
        ];
    }

    /**
     * ============================================================
     *  5. REAL-TIME / PULSE STATS — إحصائيات فورية
     * ============================================================
     */
    public function realtimeStats(): array
    {
        $now   = Carbon::now();
        $today = $now->copy()->startOfDay();
        $thisHour = $now->copy()->startOfHour();

        // ── Today's pulse ──
        $todayRevenue    = (float) Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $today)->sum('final_amount');
        $todayOrders     = Order::where('created_at', '>=', $today)->count();
        $todayCustomers  = User::where('role', 'customer')->where('created_at', '>=', $today)->count();
        $todayVisitors   = RecentlyViewed::whereDate('viewed_at', $today)
            ->selectRaw('COUNT(DISTINCT COALESCE(user_id, session_id)) as count')
            ->value('count');
        $todayCheckouts  = PendingCheckout::whereDate('created_at', $today)->count();

        // ── This hour ──
        $hourOrders      = Order::where('created_at', '>=', $thisHour)->count();
        $hourRevenue     = (float) Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $thisHour)->sum('final_amount');

        // ── Pending actions (needs attention) ──
        $pendingOrders    = Order::where('order_status', 'pending')->count();
        $processingOrders = Order::where('order_status', 'processing')->count();
        $pendingReviews   = ProductReview::where('is_approved', false)->count();
        $lowStockCount    = Product::where('quantity_in_stock', '<=', DB::raw('low_stock_threshold'))
            ->where('is_active', true)->where('quantity_in_stock', '>', 0)->count();
        $outOfStockCount  = Product::where('quantity_in_stock', '<=', 0)->where('is_active', true)->count();
        $pendingReturns   = ReturnRequest::where('status', 'pending')->count();

        // ── Last 24 hours activity ──
        $last24hStart = $now->copy()->subHours(24);
        $last24hOrders = Order::where('created_at', '>=', $last24hStart)->count();
        $last24hRevenue = (float) Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $last24hStart)->sum('final_amount');
        $last24hUsers = User::where('created_at', '>=', $last24hStart)->count();

        return [
            'pulse' => [
                'today_revenue'    => $todayRevenue,
                'today_orders'     => $todayOrders,
                'today_customers'  => $todayCustomers,
                'today_visitors'   => $todayVisitors,
                'today_checkouts'  => $todayCheckouts,
                'hour_orders'      => $hourOrders,
                'hour_revenue'     => $hourRevenue,
            ],
            'needs_attention' => [
                'pending_orders'    => $pendingOrders,
                'processing_orders' => $processingOrders,
                'pending_reviews'   => $pendingReviews,
                'pending_returns'   => $pendingReturns,
                'low_stock'         => $lowStockCount,
                'out_of_stock'      => $outOfStockCount,
            ],
            'last_24h' => [
                'orders'  => $last24hOrders,
                'revenue' => $last24hRevenue,
                'users'   => $last24hUsers,
            ],
        ];
    }

    /**
     * ============================================================
     *  6. SALES BY DATE RANGE — مبيعات حسب نطاق تاريخي
     * ============================================================
     */
    public function salesByDateRange(Request $request): array
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to', Carbon::now()->toDateString());
        $groupBy  = $request->get('group_by', 'day'); // day, week, month

        $from = Carbon::parse($dateFrom)->startOfDay();
        $to   = Carbon::parse($dateTo)->endOfDay();

        $dateFormat = match ($groupBy) {
            'week'  => "%Y-%u",
            'month' => "%Y-%m",
            default => "%Y-%m-%d",
        };

        $sales = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("
                DATE_FORMAT(created_at, '{$dateFormat}') as period,
                COUNT(*) as total_orders,
                COALESCE(SUM(final_amount), 0) as revenue,
                COALESCE(SUM(tax_amount), 0) as tax,
                COALESCE(SUM(shipping_amount), 0) as shipping,
                COALESCE(SUM(discount_amount), 0) as discounts,
                COALESCE(AVG(final_amount), 0) as avg_order_value
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'group_by'  => $groupBy,
            'total_revenue' => (float) $sales->sum('revenue'),
            'total_orders'  => (int) $sales->sum('total_orders'),
            'sales'         => $sales->map(fn($s) => [
                'period'          => $s->period,
                'total_orders'    => (int) $s->total_orders,
                'revenue'         => (float) $s->revenue,
                'tax'             => (float) $s->tax,
                'shipping'        => (float) $s->shipping,
                'discounts'       => (float) $s->discounts,
                'avg_order_value' => (float) $s->avg_order_value,
            ]),
        ];
    }

    /**
     * ============================================================
     *  7. ORDER FULFILLMENT — تحليل تنفيذ الطلبات
     * ============================================================
     */
    public function orderFulfillment(): array
    {
        // Average time (in hours) between status changes
        $fulfillment = Order::where('payment_status', 'paid')
            ->whereNotNull('confirmed_at')
            ->selectRaw("
                COALESCE(AVG(TIMESTAMPDIFF(HOUR, created_at, confirmed_at)), 0) as avg_to_confirm,
                COALESCE(AVG(TIMESTAMPDIFF(HOUR, confirmed_at, shipped_at)), 0) as avg_to_ship,
                COALESCE(AVG(TIMESTAMPDIFF(HOUR, shipped_at, delivered_at)), 0) as avg_to_deliver,
                COALESCE(AVG(TIMESTAMPDIFF(HOUR, created_at, delivered_at)), 0) as avg_total,
                COUNT(*) as total_fulfilled
            ")
            ->first();

        // Orders by fulfillment status
        $statusCounts = Order::selectRaw('order_status, COUNT(*) as count')
            ->groupBy('order_status')
            ->pluck('count', 'order_status');

        // Orders that were cancelled and their reasons
        $cancelledOrders = Order::where('order_status', 'cancelled')
            ->whereNotNull('cancel_reason')
            ->selectRaw('cancel_reason, COUNT(*) as count')
            ->groupBy('cancel_reason')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        return [
            'avg_hours' => [
                'to_confirm'  => round((float) $fulfillment->avg_to_confirm, 1),
                'to_ship'     => round((float) $fulfillment->avg_to_ship, 1),
                'to_deliver'  => round((float) $fulfillment->avg_to_deliver, 1),
                'total'       => round((float) $fulfillment->avg_total, 1),
            ],
            'orders_by_status' => [
                'pending'    => (int) ($statusCounts->get('pending', 0)),
                'confirmed'  => (int) ($statusCounts->get('confirmed', 0)),
                'processing' => (int) ($statusCounts->get('processing', 0)),
                'shipped'    => (int) ($statusCounts->get('shipped', 0)),
                'delivered'  => (int) ($statusCounts->get('delivered', 0)),
                'cancelled'  => (int) ($statusCounts->get('cancelled', 0)),
            ],
            'cancellation_reasons' => $cancelledOrders->map(fn($r) => [
                'reason' => $r->cancel_reason,
                'count'  => (int) $r->count,
            ]),
            'total_fulfilled' => (int) $fulfillment->total_fulfilled,
        ];
    }

    /**
     * ============================================================
     *  8. PRODUCT PERFORMANCE — أداء المنتجات
     * ============================================================
     */
    public function productPerformance(): array
    {
        $locale = app()->getLocale();

        $totalProducts    = Product::count();
        $activeProducts   = Product::where('is_active', true)->count();
        $inactiveProducts = Product::where('is_active', false)->count();
        $productsWithVariants = Product::whereHas('variants')->count();

        // Products with zero sales
        $productsWithNoSales = Product::where('is_active', true)
            ->whereDoesntHave('orderItems', fn($q) => $q->whereHas('order', fn($oq) => $oq->where('payment_status', 'paid')))
            ->count();

        // Top categories by product count
        $categories = DB::table('product_categories')
            ->join('categories', 'product_categories.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                DB::raw("categories.name_{$locale} as name"),
                DB::raw('COUNT(*) as products_count')
            )
            ->groupBy('categories.id', "categories.name_{$locale}")
            ->orderByDesc('products_count')
            ->get();

        // Stock distribution
        $stockDistribution = [
            'in_stock'    => Product::where('quantity_in_stock', '>', DB::raw('low_stock_threshold'))->where('is_active', true)->count(),
            'low_stock'   => Product::where('quantity_in_stock', '<=', DB::raw('low_stock_threshold'))
                ->where('quantity_in_stock', '>', 0)->where('is_active', true)->count(),
            'out_of_stock' => Product::where('quantity_in_stock', '<=', 0)->where('is_active', true)->count(),
        ];

        // Average rating
        $avgRating = (float) ProductReview::avg('rating') ?? 0;
        $totalReviews = ProductReview::count();
        $approvedReviews = ProductReview::where('is_approved', true)->count();

        return [
            'totals' => [
                'total_products'       => $totalProducts,
                'active_products'      => $activeProducts,
                'inactive_products'    => $inactiveProducts,
                'with_variants'        => $productsWithVariants,
                'with_no_sales'        => $productsWithNoSales,
            ],
            'stock_distribution' => $stockDistribution,
            'top_categories'     => $categories,
            'reviews' => [
                'average_rating'   => round($avgRating, 2),
                'total_reviews'    => $totalReviews,
                'approved_reviews' => $approvedReviews,
            ],
        ];
    }
}
