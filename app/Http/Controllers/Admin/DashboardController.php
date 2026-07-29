<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\RecentlyViewed;
use App\Models\StockAlert;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\OrderService;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected StatisticsService $statistics,
    ) {}

    /**
     * Main dashboard stats (existing, enhanced).
     */
    public function stats(Request $request): JsonResponse
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $paidOrders = fn($q) => $q->where('payment_status', 'paid');

        // ── Revenue ──
        $totalRevenue = (float) Order::where('payment_status', 'paid')->sum('final_amount');
        $todayRevenue = (float) Order::where('payment_status', 'paid')->where('created_at', '>=', $today)->sum('final_amount');
        $thisMonthRevenue = (float) Order::where('payment_status', 'paid')->where('created_at', '>=', $thisMonth)->sum('final_amount');
        $lastMonthRevenue = (float) Order::where('payment_status', 'paid')->whereBetween('created_at', [$lastMonth, $lastMonthEnd])->sum('final_amount');
        $revenueGrowthPercent = $lastMonthRevenue > 0 ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2) : 0;
        $averageOrderValue = (float) Order::where('payment_status', 'paid')->avg('final_amount') ?? 0;

        // ── Orders ──
        $totalOrders = Order::count();
        $todayOrders = Order::where('created_at', '>=', $today)->count();
        $thisMonthOrders = Order::where('created_at', '>=', $thisMonth)->count();
        $pendingOrdersCount = Order::where('order_status', 'pending')->count();
        $ordersByStatus = Order::selectRaw('order_status, COUNT(*) as count')->groupBy('order_status')->pluck('count', 'order_status');
        $ordersByPaymentStatus = Order::selectRaw('payment_status, COUNT(*) as count')->groupBy('payment_status')->pluck('count', 'payment_status');
        $recentOrders = Order::with('user:id,username,email,first_name,last_name')->latest()->take(10)->get();

        // ── Products ──
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $outOfStockProducts = Product::where('quantity_in_stock', '<=', 0)->count();
        $featuredProductsCount = Product::where('is_featured', true)->count();
        $lowStockProducts = Product::where('quantity_in_stock', '<=', DB::raw('low_stock_threshold'))
            ->where('is_active', true)->take(10)->get(['id', 'name_ar', 'name_en', 'sku', 'quantity_in_stock', 'low_stock_threshold']);

        $topSellingProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total_price) as revenue'))
            ->whereHas('order', $paidOrders)
            ->groupBy('product_id')->orderByDesc('total_sold')->take(10)
            ->get()->load('product:id,name_ar,name_en');

        $productsByCategory = DB::table('product_categories')
            ->join('categories', 'product_categories.category_id', '=', 'categories.id')
            ->select('categories.id as category_id', 'categories.name_ar as category_name_ar', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'categories.name_ar')->orderByDesc('count')->get();

        // ── Customers ──
        $totalCustomers = User::where('role', 'customer')->count();
        $newCustomersThisMonth = User::where('role', 'customer')->where('created_at', '>=', $thisMonth)->count();
        $customersByRole = User::selectRaw('role, COUNT(*) as count')->groupBy('role')->pluck('count', 'role');

        $topCustomers = User::where('users.role', 'customer')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.payment_status', 'paid')
            ->select('users.id', 'users.username', 'users.first_name', 'users.last_name',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(orders.final_amount), 0) as total_spent')
            )
            ->groupBy('users.id', 'users.username', 'users.first_name', 'users.last_name')
            ->orderByDesc('total_spent')
            ->take(10)
            ->get();

        // ── Financial ──
        $totalTaxCollected = (float) Order::where('payment_status', 'paid')->sum('tax_amount');
        $totalShippingRevenue = (float) Order::where('payment_status', 'paid')->sum('shipping_amount');
        $totalDiscountsGiven = (float) Order::where('payment_status', 'paid')->sum('discount_amount');

        $paymentMethodDistribution = Payment::whereHas('order', $paidOrders)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')->orderByDesc('count')->get();

        // ── Miscellaneous ──
        $pendingReviewsCount = ProductReview::where('is_approved', false)->count();
        $activeCouponsCount = Coupon::where('is_active', true)
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $now))
            ->count();
        $priceAlertsActive = PriceAlert::where('is_active', true)->count();
        $stockAlertsActive = StockAlert::where('is_notified', false)->count();
        $todayVisitors = RecentlyViewed::whereDate('viewed_at', $today)
            ->selectRaw('COUNT(DISTINCT COALESCE(user_id, session_id)) as count')
            ->value('count');
        $totalWishlistItems = Wishlist::count();

        return response()->json([
            'total_revenue'          => $totalRevenue,
            'today_revenue'          => $todayRevenue,
            'this_month_revenue'     => $thisMonthRevenue,
            'last_month_revenue'     => $lastMonthRevenue,
            'revenue_growth_percent' => $revenueGrowthPercent,
            'average_order_value'    => $averageOrderValue,

            'total_orders'             => $totalOrders,
            'today_orders'             => $todayOrders,
            'this_month_orders'        => $thisMonthOrders,
            'pending_orders_count'     => $pendingOrdersCount,
            'orders_by_status'         => $ordersByStatus,
            'orders_by_payment_status' => $ordersByPaymentStatus,
            'recent_orders'            => $recentOrders,

            'total_products'          => $totalProducts,
            'active_products'         => $activeProducts,
            'out_of_stock_products'   => $outOfStockProducts,
            'featured_products_count' => $featuredProductsCount,
            'low_stock_products'      => $lowStockProducts,
            'top_selling_products'    => $topSellingProducts,
            'products_by_category'    => $productsByCategory,

            'total_customers'          => $totalCustomers,
            'new_customers_this_month' => $newCustomersThisMonth,
            'customers_by_role'        => $customersByRole,
            'top_customers'            => $topCustomers,

            'total_tax_collected'       => $totalTaxCollected,
            'total_shipping_revenue'    => $totalShippingRevenue,
            'total_discounts_given'     => $totalDiscountsGiven,
            'payment_method_distribution' => $paymentMethodDistribution,

            'pending_reviews_count' => $pendingReviewsCount,
            'active_coupons_count'  => $activeCouponsCount,
            'price_alerts_active'   => $priceAlertsActive,
            'stock_alerts_active'   => $stockAlertsActive,
            'today_visitors'        => $todayVisitors,
            'total_wishlist_items'  => $totalWishlistItems,
        ]);
    }

    // ========================================================================
    // ADVANCED STATISTICS ENDPOINTS
    // ========================================================================

    /**
     * 1. Monthly sales breakdown (last 12 months).
     * GET /api/admin/dashboard/monthly-sales?months=12
     */
    public function monthlySales(Request $request): JsonResponse
    {
        $months = (int) $request->get('months', 12);
        return response()->json($this->statistics->monthlySales($months));
    }

    /**
     * 2. Top selling products.
     * GET /api/admin/dashboard/top-products?period=all&limit=20&date_from=&date_to=
     */
    public function topProducts(Request $request): JsonResponse
    {
        return response()->json($this->statistics->topSellingProducts($request));
    }

    /**
     * 3. Customer analytics.
     * GET /api/admin/dashboard/customer-analytics
     */
    public function customerAnalytics(): JsonResponse
    {
        return response()->json($this->statistics->customerAnalytics());
    }

    /**
     * 4. Conversion rate (funnel analysis).
     * GET /api/admin/dashboard/conversion-rate
     */
    public function conversionRate(): JsonResponse
    {
        return response()->json($this->statistics->conversionRate());
    }

    /**
     * 5. Real-time / Pulse statistics.
     * GET /api/admin/dashboard/realtime
     */
    public function realtime(): JsonResponse
    {
        return response()->json($this->statistics->realtimeStats());
    }

    /**
     * 6. Sales by custom date range.
     * GET /api/admin/dashboard/sales-by-date?date_from=2026-01-01&date_to=2026-07-01&group_by=day
     */
    public function salesByDate(Request $request): JsonResponse
    {
        return response()->json($this->statistics->salesByDateRange($request));
    }

    /**
     * 7. Order fulfillment analytics.
     * GET /api/admin/dashboard/fulfillment
     */
    public function fulfillment(): JsonResponse
    {
        return response()->json($this->statistics->orderFulfillment());
    }

    /**
     * 8. Product performance analytics.
     * GET /api/admin/dashboard/product-performance
     */
    public function productPerformance(): JsonResponse
    {
        return response()->json($this->statistics->productPerformance());
    }
}
