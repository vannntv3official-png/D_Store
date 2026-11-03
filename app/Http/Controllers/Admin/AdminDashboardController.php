<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Setting;
use App\Models\User;
use App\Services\Pricing\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $rate = (int) Setting::get('exchange_rate_khr_per_usd', 4100);

        // Reconciled financial metrics (Gross vs Net after approved refunds)
        $grossRevenue = (float) Payment::where('status', 'paid')->sum('amount');
        $totalRefunds = (float) Refund::whereIn('status', ['approved', 'completed'])->sum('amount');
        $netRevenue = max(0, $grossRevenue - $totalRefunds);
        $netRevenueFormatted = Money::format((string) $netRevenue);
        $refundsFormatted = Money::format((string) $totalRefunds);

        $totalOrdersCount = Order::count();
        $pendingOrdersCount = Order::whereIn('fulfillment_status', [
            Order::FULFILLMENT_PENDING,
            Order::FULFILLMENT_CONFIRMED,
            Order::FULFILLMENT_PROCESSING,
        ])->count();
        $customersCount = User::whereDoesntHave('roles')->count();

        // Low stock items count (available <= 5)
        $lowStockCount = Inventory::whereRaw('(on_hand_quantity - reserved_quantity) <= 5')->count();

        // Timeframe filter for chronological analytics
        $period = (string) $request->query('period', '30d');
        $now = Carbon::now();
        switch ($period) {
            case '7d':
                $days = 7;
                $startDate = $now->copy()->subDays(6)->startOfDay();
                break;
            case '90d':
                $days = 90;
                $startDate = $now->copy()->subDays(89)->startOfDay();
                break;
            case 'this_month':
                $days = max(1, (int) $now->day);
                $startDate = $now->copy()->startOfMonth()->startOfDay();
                break;
            case '30d':
            default:
                $period = '30d';
                $days = 30;
                $startDate = $now->copy()->subDays(29)->startOfDay();
                break;
        }

        // Daily Sales Chart dataset for the selected period
        $dailySales = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(grand_total) as daily_total'),
            DB::raw('COUNT(id) as orders_count')
        )
            ->where('created_at', '>=', $startDate)
            ->whereNotIn('fulfillment_status', [Order::FULFILLMENT_CANCELLED])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartData = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $chartLabels[] = $date->format('M d');
            $chartData[] = isset($dailySales[$dateStr]) ? (float) $dailySales[$dateStr]->daily_total : 0.0;
        }

        // Period analytical summary
        $periodTotalRevenue = array_sum($chartData);
        $avgDailyRevenue = $days > 0 ? $periodTotalRevenue / $days : 0;
        $maxChartVal = !empty($chartData) ? max($chartData) : 0;
        $maxKey = !empty($chartData) ? array_search($maxChartVal, $chartData) : false;
        $peakDayLabel = ($maxKey !== false && isset($chartLabels[$maxKey])) ? $chartLabels[$maxKey] : 'N/A';

        // Calculate comparison with preceding period of identical duration
        $priorStartDate = $startDate->copy()->subDays($days);
        $priorEndDate = $startDate->copy();
        $priorTotalRevenue = (float) Order::where('created_at', '>=', $priorStartDate)
            ->where('created_at', '<', $priorEndDate)
            ->whereNotIn('fulfillment_status', [Order::FULFILLMENT_CANCELLED])
            ->sum('grand_total');

        $trendPercentage = 0.0;
        if ($priorTotalRevenue > 0) {
            $trendPercentage = round((($periodTotalRevenue - $priorTotalRevenue) / $priorTotalRevenue) * 100, 1);
        } elseif ($periodTotalRevenue > 0) {
            $trendPercentage = 100.0;
        }

        $avgOrderValue = $totalOrdersCount > 0 ? round($netRevenue / $totalOrdersCount, 2) : 0.0;

        // Critical Low Stock Watchlist (actionable directly from dashboard)
        $lowStockWatchlist = Inventory::with(['variant.product'])
            ->whereRaw('(on_hand_quantity - reserved_quantity) <= 5')
            ->orderByRaw('(on_hand_quantity - reserved_quantity) ASC')
            ->take(5)
            ->get()
            ->map(fn (Inventory $inv) => [
                'id' => $inv->id,
                'variant_id' => $inv->product_variant_id,
                'sku' => $inv->variant?->sku ?? 'N/A',
                'product_name' => $inv->variant?->product?->getLocalizedName() ?? 'Unknown Product',
                'on_hand' => $inv->on_hand_quantity,
                'reserved' => $inv->reserved_quantity,
                'available' => max(0, $inv->on_hand_quantity - $inv->reserved_quantity),
            ]);

        // Top Selling Products
        $topSelling = OrderItem::select(
            'product_variant_id',
            'sku_snapshot',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(line_total) as total_sales')
        )
            ->with('variant.product')
            ->whereHas('order', fn ($o) => $o->whereNotIn('fulfillment_status', [Order::FULFILLMENT_CANCELLED]))
            ->groupBy('product_variant_id', 'sku_snapshot')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get()
            ->map(fn ($item) => [
                'sku' => $item->sku_snapshot ?? $item->variant?->sku ?? 'N/A',
                'product_name' => $item->variant?->product?->getLocalizedName() ?? ($item->sku_snapshot ?? 'Item'),
                'total_quantity' => (int) $item->total_quantity,
                'total_sales' => Money::format((string) $item->total_sales),
                'total_sales_khr' => Money::toKhr((string) $item->total_sales, $rate),
            ]);

        // Recent 8 orders
        $recentOrders = Order::with(['user:id,name,email', 'shippingAddress', 'payments'])
            ->latest()
            ->take(8)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'status' => $order->fulfillment_status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payments->last()?->gateway ?? 'cod',
                'total_amount' => Money::format($order->grand_total),
                'total_khr' => Money::toKhr($order->grand_total, $rate),
                'created_at' => $order->created_at->format('M d, H:i'),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'paid_revenue' => $netRevenueFormatted,
                'paid_revenue_khr' => Money::toKhr($netRevenueFormatted, $rate),
                'gross_revenue' => Money::format((string) $grossRevenue),
                'refunds_total' => $refundsFormatted,
                'total_orders' => $totalOrdersCount,
                'pending_orders' => $pendingOrdersCount,
                'customers_count' => $customersCount,
                'low_stock_count' => $lowStockCount,
                'trend_percentage' => $trendPercentage,
                'avg_order_value' => Money::format((string) $avgOrderValue),
                'avg_order_value_khr' => Money::toKhr((string) $avgOrderValue, $rate),
            ],
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartData,
                'period' => $period,
                'period_total' => Money::format((string) $periodTotalRevenue),
                'avg_daily' => Money::format((string) $avgDailyRevenue),
                'peak_day' => $peakDayLabel,
                'peak_amount' => Money::format((string) $maxChartVal),
            ],
            'lowStockWatchlist' => $lowStockWatchlist,
            'topSelling' => $topSelling,
            'recentOrders' => $recentOrders,
        ]);
    }
}
