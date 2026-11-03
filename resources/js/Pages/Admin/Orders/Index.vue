<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Badge from '@/Components/Badge.vue';
import { Search, ShoppingBag, Eye, Printer } from 'lucide-vue-next';

interface OrderSummary {
    id: number;
    order_number: string;
    customer_name: string;
    customer_phone: string;
    status: string;
    payment_status: string;
    total_amount: string;
    total_khr: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    orders: {
        data: OrderSummary[];
        links: PaginationLink[];
        total: number;
    };
    filters: {
        status?: string;
        payment_status?: string;
        q?: string;
    };
    statuses: string[];
}

const props = defineProps<Props>();

const search = ref(props.filters.q || '');
const selectedStatus = ref(props.filters.status || '');
const selectedPaymentStatus = ref(props.filters.payment_status || '');

const applyFilters = () => {
    router.get(
        '/admin/orders',
        {
            q: search.value || undefined,
            status: selectedStatus.value || undefined,
            payment_status: selectedPaymentStatus.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'delivered':
        case 'paid':
            return 'success';
        case 'shipped':
        case 'processing':
        case 'confirmed':
        case 'packed':
            return 'blue';
        case 'pending':
            return 'warning';
        case 'cancelled':
        case 'failed':
            return 'danger';
        default:
            return 'neutral';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Orders Management — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Customer Orders</h1>
                    <p class="text-xs text-[#86868B] mt-1">Review state transitions, tracking assignments, and customer settlements.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-2xl border border-[#E5E5EA] shadow-sm flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <Search class="w-4 h-4 text-[#86868B] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search order #, customer name, or phone..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-[#F5F5F7] border border-transparent rounded-xl focus:bg-white focus:border-[#111111] outline-none transition-all"
                    />
                </div>

                <div class="w-full sm:w-48">
                    <select
                        v-model="selectedStatus"
                        @change="applyFilters"
                        class="w-full py-2 px-3 text-xs bg-[#F5F5F7] border border-transparent rounded-xl focus:bg-white focus:border-[#111111] outline-none transition-all cursor-pointer"
                    >
                        <option value="">All Fulfillment Statuses</option>
                        <option v-for="st in statuses" :key="st" :value="st">
                            {{ st.toUpperCase() }}
                        </option>
                    </select>
                </div>

                <div class="w-full sm:w-48">
                    <select
                        v-model="selectedPaymentStatus"
                        @change="applyFilters"
                        class="w-full py-2 px-3 text-xs bg-[#F5F5F7] border border-transparent rounded-xl focus:bg-white focus:border-[#111111] outline-none transition-all cursor-pointer"
                    >
                        <option value="">All Payment States</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>

                <Button variant="secondary" size="sm" @click="applyFilters" class="w-full sm:w-auto">
                    Filter
                </Button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Order</th>
                                <th class="py-3.5 px-6">Customer</th>
                                <th class="py-3.5 px-6">Date</th>
                                <th class="py-3.5 px-6">Fulfillment</th>
                                <th class="py-3.5 px-6">Payment</th>
                                <th class="py-3.5 px-6 text-right">Grand Total</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="orders.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-[#86868B]">
                                    No orders found matching criteria.
                                </td>
                            </tr>
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-bold text-[#111111]">
                                    {{ order.order_number }}
                                </td>
                                <td class="py-4 px-6 font-medium text-[#1D1D1F]">
                                    <div>{{ order.customer_name }}</div>
                                    <div class="text-[11px] text-[#86868B] font-mono">{{ order.customer_phone }}</div>
                                </td>
                                <td class="py-4 px-6 text-[#86868B]">
                                    {{ order.created_at }}
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="getStatusBadgeVariant(order.status)">
                                        {{ order.status }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="getStatusBadgeVariant(order.payment_status)">
                                        {{ order.payment_status }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6 text-right font-bold text-[#111111]">
                                    <div>${{ order.total_amount }}</div>
                                    <div class="text-[10px] text-[#86868B] font-normal">៛{{ order.total_khr }}</div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/admin/orders/${order.id}/invoice`" target="_blank" title="Print Invoice">
                                            <Button variant="ghost" size="sm" class="p-1.5 text-[#86868B] hover:text-[#111111]">
                                                <Printer class="w-3.5 h-3.5" />
                                            </Button>
                                        </Link>
                                        <Link :href="`/admin/orders/${order.id}`">
                                            <Button variant="secondary" size="sm">
                                                <span>Manage</span>
                                                <Eye class="w-3.5 h-3.5 ml-1" />
                                            </Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.links.length > 3" class="p-4 border-t border-[#F5F5F7] flex items-center justify-between">
                    <p class="text-xs text-[#86868B]">
                        Showing {{ orders.data.length }} of {{ orders.total }} orders
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="link in orders.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 rounded-lg text-xs font-semibold transition-colors',
                                link.active ? 'bg-[#111111] text-white' : 'text-[#86868B] hover:bg-[#F5F5F7]',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
