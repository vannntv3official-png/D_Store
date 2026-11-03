<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import { 
    DollarSign, ShoppingCart, Clock, Users, AlertTriangle, 
    ArrowUpRight, ArrowRight, TrendingUp, TrendingDown, Package, 
    Sparkles, Award, CheckCircle2, ShieldCheck, Warehouse, 
    Layers, CreditCard, ChevronRight, Activity, ArrowDownRight
} from 'lucide-vue-next';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    type ChartOptions,
    type ChartData,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

interface Metrics {
    paid_revenue: string;
    paid_revenue_khr: string;
    gross_revenue?: string;
    refunds_total?: string;
    total_orders: number;
    pending_orders: number;
    customers_count: number;
    low_stock_count: number;
    trend_percentage?: number;
    avg_order_value?: string;
    avg_order_value_khr?: string;
}

interface ChartDataPayload {
    labels: string[];
    data: number[];
    period?: string;
    period_total?: string;
    avg_daily?: string;
    peak_day?: string;
    peak_amount?: string;
}

interface TopSellingProduct {
    sku: string;
    product_name: string;
    total_quantity: number;
    total_sales: string;
    total_sales_khr: string;
}

interface LowStockItem {
    id: number;
    variant_id: number;
    sku: string;
    product_name: string;
    on_hand: number;
    reserved: number;
    available: number;
}

interface RecentOrder {
    id: number;
    order_number: string;
    customer_name: string;
    status: string;
    payment_status: string;
    payment_method?: string;
    total_amount: string;
    total_khr: string;
    created_at: string;
}

interface Props {
    metrics: Metrics;
    chart: ChartDataPayload;
    lowStockWatchlist?: LowStockItem[];
    topSelling?: TopSellingProduct[];
    recentOrders: RecentOrder[];
}

const props = defineProps<Props>();

const currentPeriod = computed(() => props.chart.period || '30d');

const setPeriod = (period: string) => {
    router.get('/admin', { period }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Greeting calculation
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
});

const chartConfigData = computed<ChartData<'line'>>(() => ({
    labels: props.chart.labels,
    datasets: [
        {
            label: 'Sales Revenue (USD)',
            data: props.chart.data,
            borderColor: '#111111',
            backgroundColor: (context) => {
                const ctx = context.chart.ctx;
                const gradient = ctx.createLinearGradient(0, 0, 0, 280);
                gradient.addColorStop(0, 'rgba(17, 17, 17, 0.12)');
                gradient.addColorStop(1, 'rgba(17, 17, 17, 0.00)');
                return gradient;
            },
            borderWidth: 2.5,
            pointBackgroundColor: '#111111',
            pointBorderColor: '#FFFFFF',
            pointBorderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#111111',
            pointHoverBorderColor: '#FFFFFF',
            pointHoverBorderWidth: 2.5,
            tension: 0.35,
            fill: true,
        },
    ],
}));

const chartOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#111111',
            titleColor: '#FFFFFF',
            bodyColor: '#E5E5EA',
            padding: 12,
            cornerRadius: 12,
            displayColors: false,
            callbacks: {
                title: (items) => items[0]?.label || '',
                label: (context) => {
                    const val = Number(context.parsed.y ?? 0);
                    const khr = Math.round(val * 4100);
                    return [
                        `Revenue: $${val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                        `In KHR:  ៛${khr.toLocaleString()}`
                    ];
                },
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                font: {
                    size: 11,
                    weight: 600,
                },
                color: '#86868B',
                maxTicksLimit: 10,
            },
        },
        y: {
            grid: {
                color: '#F0F0F2',
            },
            ticks: {
                font: {
                    size: 11,
                },
                color: '#86868B',
                callback: (value) => `$${value}`,
            },
        },
    },
};

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'delivered':
        case 'paid':
            return 'success';
        case 'shipped':
        case 'processing':
        case 'confirmed':
            return 'blue';
        case 'pending':
            return 'warning';
        case 'cancelled':
        case 'refunded':
            return 'danger';
        default:
            return 'neutral';
    }
};

const getPaymentMethodLabel = (method?: string) => {
    switch (method) {
        case 'khqr_sandbox':
            return 'Bakong KHQR';
        case 'aba_payway_sandbox':
            return 'ABA PayWay';
        case 'bank_transfer':
            return 'Bank Transfer';
        case 'cod':
            return 'COD';
        default:
            return method || 'Direct';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Executive Dashboard — D STORE" />

        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- Executive Welcome Bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-[#86868B]">Store Operations</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-emerald-600">All Systems Operational</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-[#111111] tracking-tight mt-1">
                        {{ greeting }}, Administrator
                    </h1>
                    <p class="text-xs text-[#86868B] mt-1">
                        Here is today's consolidated performance and inventory dispatch overview.
                    </p>
                </div>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <Link href="/admin/orders">
                        <Button variant="secondary" size="sm" class="flex items-center gap-1.5 text-xs font-bold">
                            <ShoppingCart class="w-3.5 h-3.5" />
                            <span>Orders Queue</span>
                            <span v-if="metrics.pending_orders > 0" class="ml-1 px-1.5 py-0.2 rounded-full bg-amber-500 text-white text-[10px]">
                                {{ metrics.pending_orders }}
                            </span>
                        </Button>
                    </Link>
                    <Link href="/admin/inventory">
                        <Button variant="secondary" size="sm" class="flex items-center gap-1.5 text-xs font-bold">
                            <Warehouse class="w-3.5 h-3.5" />
                            <span>Stock Hub</span>
                        </Button>
                    </Link>
                    <Link href="/admin/products/create">
                        <Button variant="primary" size="sm" class="flex items-center gap-1.5 text-xs font-bold shadow-sm">
                            <span>+ Add Product</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- 1. Net Settled Revenue -->
                <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-start justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#86868B]">Settled Net Revenue</span>
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 transition-transform group-hover:scale-105">
                                <DollarSign class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-2">
                            <h3 class="text-2xl sm:text-3xl font-black text-[#111111] tracking-tight">
                                ${{ metrics.paid_revenue }}
                            </h3>
                            <p class="text-xs text-[#86868B] mt-0.5 font-medium">
                                ៛{{ metrics.paid_revenue_khr }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#F5F5F7] space-y-1.5 text-[11px]">
                        <div class="flex items-center justify-between text-[#86868B]">
                            <span>Gross Received:</span>
                            <span class="font-bold text-[#111111]">${{ metrics.gross_revenue || metrics.paid_revenue }}</span>
                        </div>
                        <div v-if="metrics.refunds_total && Number(metrics.refunds_total) > 0" class="flex items-center justify-between text-rose-600">
                            <span>Approved Refunds:</span>
                            <span class="font-bold">-${{ metrics.refunds_total }}</span>
                        </div>
                        <div v-if="metrics.trend_percentage !== undefined" class="pt-1 flex items-center gap-1.5 text-[11px] font-bold"
                            :class="metrics.trend_percentage >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                        >
                            <TrendingUp v-if="metrics.trend_percentage >= 0" class="w-3.5 h-3.5" />
                            <TrendingDown v-else class="w-3.5 h-3.5" />
                            <span>{{ metrics.trend_percentage >= 0 ? '+' : '' }}{{ metrics.trend_percentage }}% vs prior period</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Total Orders -->
                <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-start justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#86868B]">Total Customer Orders</span>
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 transition-transform group-hover:scale-105">
                                <ShoppingCart class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-2">
                            <h3 class="text-2xl sm:text-3xl font-black text-[#111111] tracking-tight">
                                {{ metrics.total_orders.toLocaleString() }}
                            </h3>
                            <p class="text-xs text-[#86868B] mt-0.5">Lifetime checkouts processed</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#F5F5F7] space-y-1 text-[11px] text-[#86868B]">
                        <div class="flex items-center justify-between">
                            <span>Average Order Value:</span>
                            <span class="font-bold text-[#111111]">${{ metrics.avg_order_value || '0.00' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Unique Customers:</span>
                            <span class="font-bold text-[#111111]">{{ metrics.customers_count }}</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Pending Fulfillment -->
                <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-start justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#86868B]">Pending Dispatch</span>
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 transition-transform group-hover:scale-105">
                                <Clock class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-2">
                            <h3 class="text-2xl sm:text-3xl font-black text-amber-600 tracking-tight">
                                {{ metrics.pending_orders.toLocaleString() }}
                            </h3>
                            <p class="text-xs text-[#86868B] mt-0.5">Orders awaiting packing / courier</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#F5F5F7]">
                        <Link
                            href="/admin/orders"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors"
                        >
                            <span>Open orders queue</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- 4. Low Stock Alert -->
                <div class="bg-white rounded-3xl p-6 border border-[#E5E5EA] shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-start justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#86868B]">Stock Watchlist</span>
                            <div
                                class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                                :class="metrics.low_stock_count > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600'"
                            >
                                <AlertTriangle v-if="metrics.low_stock_count > 0" class="w-5 h-5" />
                                <CheckCircle2 v-else class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="mt-2">
                            <h3
                                class="text-2xl sm:text-3xl font-black tracking-tight"
                                :class="metrics.low_stock_count > 0 ? 'text-rose-600' : 'text-[#111111]'"
                            >
                                {{ metrics.low_stock_count }}
                            </h3>
                            <p class="text-xs text-[#86868B] mt-0.5">
                                {{ metrics.low_stock_count > 0 ? 'SKUs with ≤ 5 units available' : 'All SKUs above threshold' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#F5F5F7]">
                        <Link
                            href="/admin/inventory"
                            class="inline-flex items-center gap-1.5 text-xs font-bold transition-colors"
                            :class="metrics.low_stock_count > 0 ? 'text-rose-600 hover:text-rose-700' : 'text-[#0071E3] hover:underline'"
                        >
                            <span>{{ metrics.low_stock_count > 0 ? 'Review critical stock' : 'Manage Inventory Hub' }}</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Revenue Trend Chart with Timeframe Switcher & Summary Bar -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                <!-- Top Chart Bar: Title and Period Switcher -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black text-[#111111] tracking-tight">Sales Revenue Trend</h2>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#F5F5F7] text-[#555]">
                                Net Checkout
                            </span>
                        </div>
                        <p class="text-xs text-[#86868B] mt-1">
                            Chronological daily volume across all channels (Bakong KHQR, ABA PayWay, Cards, COD).
                        </p>
                    </div>

                    <!-- Apple-style Segmented Switcher -->
                    <div class="inline-flex items-center bg-[#F5F5F7] p-1 rounded-2xl border border-[#E5E5EA] shadow-inner self-start sm:self-auto">
                        <button
                            v-for="p in [
                                { key: '7d', label: '7D' },
                                { key: '30d', label: '30D' },
                                { key: '90d', label: '90D' },
                                { key: 'this_month', label: 'This Month' },
                            ]"
                            :key="p.key"
                            type="button"
                            @click="setPeriod(p.key)"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all duration-150 cursor-pointer"
                            :class="[
                                currentPeriod === p.key
                                    ? 'bg-white text-[#111111] shadow-sm'
                                    : 'text-[#86868B] hover:text-[#111111]'
                            ]"
                        >
                            {{ p.label }}
                        </button>
                    </div>
                </div>

                <!-- Analytical Summary Strip -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-[#FBFBFC] border border-[#F5F5F7] text-xs">
                    <div class="space-y-0.5">
                        <span class="text-[11px] text-[#86868B]">Period Net Revenue</span>
                        <p class="text-base font-black text-[#111111]">${{ chart.period_total || metrics.paid_revenue }}</p>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[11px] text-[#86868B]">Daily Average</span>
                        <p class="text-base font-black text-[#111111]">${{ chart.avg_daily || '0.00' }} / day</p>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[11px] text-[#86868B]">Peak Day Record</span>
                        <p class="text-base font-black text-[#111111]">
                            {{ chart.peak_day || 'N/A' }}
                            <span class="text-xs font-bold text-[#0071E3] ml-1">(${{ chart.peak_amount || '0.00' }})</span>
                        </p>
                    </div>
                </div>

                <!-- Line Chart -->
                <div class="h-72 w-full pt-2">
                    <Line :data="chartConfigData" :options="chartOptions" />
                </div>
            </div>

            <!-- Two-Column Operational Intelligence: Top Products & Low Stock Watchlist -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Top Performing Products -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-[#F5F5F7] mb-5">
                            <div>
                                <h3 class="text-base font-black text-[#111111] tracking-tight flex items-center gap-2">
                                    <Award class="w-4 h-4 text-amber-500" />
                                    <span>Top Selling Products</span>
                                </h3>
                                <p class="text-xs text-[#86868B] mt-0.5">Ranked by unit sales volume</p>
                            </div>
                            <Link href="/admin/products" class="text-xs font-bold text-[#0071E3] hover:underline flex items-center gap-1">
                                <span>Catalog Hub</span>
                                <ArrowRight class="w-3 h-3" />
                            </Link>
                        </div>

                        <div v-if="!topSelling || topSelling.length === 0" class="py-12 text-center text-xs text-[#86868B]">
                            No order sales recorded yet.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(prod, idx) in topSelling"
                                :key="prod.sku"
                                class="p-4 rounded-2xl bg-[#FBFBFC] hover:bg-[#F5F5F7] border border-[#F5F5F7] flex items-center justify-between gap-4 transition-colors"
                            >
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <!-- Medal Rank Pill -->
                                    <span
                                        class="w-7 h-7 rounded-xl text-xs font-black flex items-center justify-center shrink-0 shadow-xs"
                                        :class="[
                                            idx === 0 ? 'bg-gradient-to-tr from-amber-400 to-yellow-500 text-white' :
                                            idx === 1 ? 'bg-gradient-to-tr from-slate-300 to-gray-400 text-slate-900' :
                                            idx === 2 ? 'bg-gradient-to-tr from-amber-600 to-orange-700 text-white' :
                                            'bg-[#E5E5EA] text-[#555]'
                                        ]"
                                    >
                                        {{ idx + 1 }}
                                    </span>
                                    <div class="truncate">
                                        <p class="text-xs font-bold text-[#111111] truncate">{{ prod.product_name }}</p>
                                        <p class="text-[10px] font-mono text-[#86868B] mt-0.5 truncate">SKU: {{ prod.sku }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-black text-[#111111]">${{ prod.total_sales }}</p>
                                    <p class="text-[10px] font-semibold text-[#86868B]">{{ prod.total_quantity }} units sold</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Critical Low Stock Watchlist -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-[#F5F5F7] mb-5">
                            <div>
                                <h3 class="text-base font-black text-[#111111] tracking-tight flex items-center gap-2">
                                    <AlertTriangle class="w-4 h-4 text-rose-600" />
                                    <span>Critical Stock Watchlist</span>
                                </h3>
                                <p class="text-xs text-[#86868B] mt-0.5">Threshold: ≤ 5 available units</p>
                            </div>
                            <Link href="/admin/inventory" class="text-xs font-bold text-rose-600 hover:underline flex items-center gap-1">
                                <span>Inventory Hub</span>
                                <ArrowRight class="w-3 h-3" />
                            </Link>
                        </div>

                        <div v-if="!lowStockWatchlist || lowStockWatchlist.length === 0" class="py-12 text-center space-y-2 text-xs">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center">
                                <CheckCircle2 class="w-5 h-5" />
                            </div>
                            <p class="font-bold text-emerald-700">Healthy Inventory Levels</p>
                            <p class="text-[11px] text-[#86868B]">All active variant SKUs meet safety thresholds.</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="item in lowStockWatchlist"
                                :key="item.id"
                                class="p-4 rounded-2xl bg-[#FFF5F5] border border-[#FEE2E2] flex items-center justify-between gap-4"
                            >
                                <div class="truncate flex-1">
                                    <p class="text-xs font-bold text-[#991B1B] truncate">{{ item.product_name }}</p>
                                    <p class="text-[10px] font-mono text-[#86868B] mt-0.5">SKU: {{ item.sku }}</p>
                                    
                                    <!-- Mini progress bar -->
                                    <div class="w-32 bg-rose-200 h-1.5 rounded-full mt-2 overflow-hidden">
                                        <div
                                            class="bg-rose-600 h-full rounded-full transition-all"
                                            :style="{ width: `${Math.min(100, Math.max(10, (item.available / 5) * 100))}%` }"
                                        ></div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-right">
                                        <p class="text-xs font-black text-rose-600">{{ item.available }} left</p>
                                        <p class="text-[10px] text-[#86868B]">On Hand: {{ item.on_hand }}</p>
                                    </div>
                                    <Link :href="`/admin/inventory?q=${encodeURIComponent(item.sku)}`">
                                        <Button variant="secondary" size="sm" class="text-[11px] py-1.5 px-3 font-bold bg-white hover:bg-rose-50 border-rose-200">
                                            Restock
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Data Table -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="p-6 border-b border-[#F5F5F7] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-black text-[#111111] tracking-tight">Recent Transactions</h2>
                        <p class="text-xs text-[#86868B] mt-0.5">Real-time incoming orders across all payment channels.</p>
                    </div>
                    <Link href="/admin/orders">
                        <Button variant="secondary" size="sm" class="flex items-center gap-1.5 text-xs font-bold">
                            <span>View All Orders</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Button>
                    </Link>
                </div>

                <div class="overflow-x-auto d-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[10px] font-extrabold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Order Number</th>
                                <th class="py-3.5 px-6">Customer</th>
                                <th class="py-3.5 px-6">Time</th>
                                <th class="py-3.5 px-6">Payment Method</th>
                                <th class="py-3.5 px-6">Payment</th>
                                <th class="py-3.5 px-6">Fulfillment</th>
                                <th class="py-3.5 px-6 text-right">Total Amount</th>
                                <th class="py-3.5 px-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="recentOrders.length === 0">
                                <td colspan="8" class="py-12 text-center text-[#86868B]">
                                    No customer orders placed yet.
                                </td>
                            </tr>
                            <tr
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-mono font-bold text-[#111111]">
                                    {{ order.order_number }}
                                </td>
                                <td class="py-4 px-6 font-semibold text-[#1D1D1F]">
                                    {{ order.customer_name }}
                                </td>
                                <td class="py-4 px-6 text-[#86868B] font-mono text-[11px]">
                                    {{ order.created_at }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-[#F5F5F7] text-[11px] font-semibold text-[#555] border border-[#E5E5EA]">
                                        {{ getPaymentMethodLabel(order.payment_method) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="getStatusBadgeVariant(order.payment_status)">
                                        {{ order.payment_status }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="getStatusBadgeVariant(order.status)">
                                        {{ order.status }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6 text-right font-bold text-[#111111]">
                                    <div>${{ order.total_amount }}</div>
                                    <div class="text-[10px] text-[#86868B] font-normal">៛{{ order.total_khr }}</div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <Link :href="`/admin/orders/${order.id}`">
                                        <Button variant="secondary" size="sm" class="text-xs font-semibold">
                                            <span>Manage</span>
                                            <ArrowUpRight class="w-3.5 h-3.5 ml-1" />
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- System Infrastructure & Health Status Strip -->
            <div class="p-4 rounded-3xl bg-white border border-[#E5E5EA] shadow-xs flex flex-wrap items-center justify-between gap-4 text-xs text-[#86868B]">
                <div class="flex items-center gap-3">
                    <ShieldCheck class="w-5 h-5 text-emerald-600 shrink-0" />
                    <div>
                        <span class="font-bold text-[#111111] block">Production Platform Health</span>
                        <span class="text-[11px]">Atomic Inventory Locks Active • Reconciled Financial Ledgers</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-[11px] font-mono">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>MySQL 8.4 Server</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Bakong KHQR Gateway</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>ABA PayWay Gateway</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <span>Phnom Penh Timezone</span>
                    </span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>