<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
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

        // Reconciled financial metrics
        $paidRevenueUsd = Payment::where('status', 'paid')->sum('amount');
        $paidRevenueFormatted = Money::format((string) $paidRevenueUsd);

        $totalOrdersCount = Order::count();
        $pendingOrdersCount = Order::whereIn('fulfillment_status', [Order::FULFILLMENT_PENDING, Order::FULFILLMENT_CONFIRMED, Order::FULFILLMENT_PROCESSING])->count();
        $customersCount = User::whereDoesntHave('roles')->count();

        // Low stock items (available <= 5)
        $lowStockCount = Inventory::whereRaw('(on_hand_quantity - reserved_quantity) <= 5')->count();

        // 30-Day Daily Sales Chart dataset
        $startDate = Carbon::now()->subDays(29)->startOfDay();
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
        for ($i = 29; $i >= 0; $i--) {
            $dateStr = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('M d');
            $chartData[] = isset($dailySales[$dateStr]) ? (float) $dailySales[$dateStr]->daily_total : 0.0;
        }

        // Recent 8 orders
        $recentOrders = Order::with(['user:id,name,email', 'shippingAddress'])
            ->latest()
            ->take(8)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'status' => $order->fulfillment_status,
                'payment_status' => $order->payment_status,
                'total_amount' => Money::format($order->grand_total),
                'total_khr' => Money::toKhr($order->grand_total, $rate),
                'created_at' => $order->created_at->format('M d, H:i'),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'paid_revenue' => $paidRevenueFormatted,
                'paid_revenue_khr' => Money::toKhr($paidRevenueFormatted, $rate),
                'total_orders' => $totalOrdersCount,
                'pending_orders' => $pendingOrdersCount,
                'customers_count' => $customersCount,
                'low_stock_count' => $lowStockCount,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartData,
            ],
            'recentOrders' => $recentOrders,
        ]);
    }
}
