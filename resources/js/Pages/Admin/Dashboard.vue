<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import { 
    DollarSign, ShoppingCart, Clock, Users, AlertTriangle, 
    ArrowUpRight, ArrowRight, TrendingUp 
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
    total_orders: number;
    pending_orders: number;
    customers_count: number;
    low_stock_count: number;
}

interface ChartDataPayload {
    labels: string[];
    data: number[];
}

interface RecentOrder {
    id: number;
    order_number: string;
    customer_name: string;
    status: string;
    payment_status: string;
    total_amount: string;
    total_khr: string;
    created_at: string;
}

interface Props {
    metrics: Metrics;
    chart: ChartDataPayload;
    recentOrders: RecentOrder[];
}

const props = defineProps<Props>();

const chartConfigData = computed<ChartData<'line'>>(() => ({
    labels: props.chart.labels,
    datasets: [
        {
            label: 'Sales Revenue (USD)',
            data: props.chart.data,
            borderColor: '#111111',
            backgroundColor: 'rgba(17, 17, 17, 0.04)',
            borderWidth: 2.5,
            pointBackgroundColor: '#111111',
            pointBorderColor: '#FFFFFF',
            pointBorderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 6,
            tension: 0.35,
            fill: true,
        },
    ],
}));

const chartOptions: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#111111',
            titleColor: '#FFFFFF',
            bodyColor: '#FFFFFF',
            padding: 12,
            cornerRadius: 10,
            displayColors: false,
            callbacks: {
                label: (context) => `$${(context.parsed.y ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`,
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
                },
                color: '#86868B',
                maxTicksLimit: 10,
            },
        },
        y: {
            grid: {
                color: '#F5F5F7',
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
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard — D STORE" />

        <div class="space-y-8">
            <!-- Header Title -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Executive Overview</h1>
                    <p class="text-xs text-[#86868B] mt-1">Reconciled business metrics and live operational indicators.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/admin/orders">
                        <Button variant="secondary" size="sm" class="flex items-center gap-1.5">
                            <ShoppingCart class="w-3.5 h-3.5" />
                            <span>View All Orders</span>
                        </Button>
                    </Link>
                    <Link href="/admin/products/create">
                        <Button variant="primary" size="sm" class="flex items-center gap-1.5">
                            <span>Add Product</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Paid Revenue -->
                <div class="bg-white rounded-2xl p-6 border border-[#E5E5EA] shadow-sm flex flex-col justify-between">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-[#86868B]">Settled Revenue</p>
                            <h3 class="text-2xl font-black text-[#111111] mt-1 tracking-tight">
                                ${{ metrics.paid_revenue }}
                            </h3>
                            <p class="text-xs text-[#86868B] mt-0.5 font-medium">
                                ៛{{ metrics.paid_revenue_khr }}
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <DollarSign class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#F5F5F7] flex items-center text-[11px] text-emerald-600 font-bold gap-1">
                        <TrendingUp class="w-3.5 h-3.5" />
                        <span>Reconciled across KHQR, PayWay & COD</span>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white rounded-2xl p-6 border border-[#E5E5EA] shadow-sm flex flex-col justify-between">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-[#86868B]">Total Orders</p>
                            <h3 class="text-2xl font-black text-[#111111] mt-1 tracking-tight">
                                {{ metrics.total_orders.toLocaleString() }}
                            </h3>
                            <p class="text-xs text-[#86868B] mt-0.5">Lifetime customer checkouts</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-[#F5F5F7] text-[#111111] flex items-center justify-center shrink-0">
                            <ShoppingCart class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#F5F5F7] flex items-center text-[11px] text-[#86868B]">
                        <span>All channels & locations</span>
                    </div>
                </div>

                <!-- Pending Fulfillment -->
                <div class="bg-white rounded-2xl p-6 border border-[#E5E5EA] shadow-sm flex flex-col justify-between">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-[#86868B]">Pending Fulfillment</p>
                            <h3 class="text-2xl font-black text-amber-600 mt-1 tracking-tight">
                                {{ metrics.pending_orders.toLocaleString() }}
                            </h3>
                            <p class="text-xs text-[#86868B] mt-0.5">Requires packing / courier handoff</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <Clock class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#F5F5F7] flex items-center text-[11px] text-amber-600 font-bold">
                        <Link href="/admin/orders" class="hover:underline flex items-center gap-1">
                            <span>Process queues</span>
                            <ArrowRight class="w-3 h-3" />
                        </Link>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="bg-white rounded-2xl p-6 border border-[#E5E5EA] shadow-sm flex flex-col justify-between">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-[#86868B]">Low Stock Alerts</p>
                            <h3 class="text-2xl font-black mt-1 tracking-tight" :class="metrics.low_stock_count > 0 ? 'text-[#DC2626]' : 'text-[#111111]'">
                                {{ metrics.low_stock_count }}
                            </h3>
                            <p class="text-xs text-[#86868B] mt-0.5">Available stock &le; 5 units</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <AlertTriangle class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#F5F5F7] flex items-center text-[11px] text-[#DC2626] font-bold">
                        <Link href="/admin/inventory" class="hover:underline flex items-center gap-1">
                            <span>Review inventory levels</span>
                            <ArrowRight class="w-3 h-3" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 30-Day Sales Revenue Line Chart -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-black text-[#111111] tracking-tight">Sales Revenue Trend</h2>
                        <p class="text-xs text-[#86868B] mt-0.5">30-day chronological net checkout revenue in USD.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#111111]"></span>
                        <span class="text-xs font-semibold text-[#111111]">Daily Revenue</span>
                    </div>
                </div>

                <div class="h-72 w-full">
                    <Line :data="chartConfigData" :options="chartOptions" />
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="p-6 border-b border-[#F5F5F7] flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-black text-[#111111] tracking-tight">Recent Orders</h2>
                        <p class="text-xs text-[#86868B] mt-0.5">Latest transactions submitted to D STORE.</p>
                    </div>
                    <Link href="/admin/orders">
                        <Button variant="ghost" size="sm" class="flex items-center gap-1 text-xs font-bold text-[#111111]">
                            <span>View All</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Button>
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Order</th>
                                <th class="py-3.5 px-6">Customer</th>
                                <th class="py-3.5 px-6">Date</th>
                                <th class="py-3.5 px-6">Payment</th>
                                <th class="py-3.5 px-6">Fulfillment</th>
                                <th class="py-3.5 px-6 text-right">Amount</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="recentOrders.length === 0">
                                <td colspan="7" class="py-8 text-center text-[#86868B]">
                                    No orders placed yet.
                                </td>
                            </tr>
                            <tr
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-bold text-[#111111]">
                                    {{ order.order_number }}
                                </td>
                                <td class="py-4 px-6 font-medium text-[#1D1D1F]">
                                    {{ order.customer_name }}
                                </td>
                                <td class="py-4 px-6 text-[#86868B]">
                                    {{ order.created_at }}
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
                                        <Button variant="secondary" size="sm">
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
        </div>
    </AdminLayout>
</template>
