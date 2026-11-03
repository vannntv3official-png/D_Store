<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import { useTrans } from '@/Composables/useTrans';
import AccountNav from '@/Components/AccountNav.vue';
import { ShoppingBag, ArrowRight } from 'lucide-vue-next';

interface Props {
    orders: {
        data: any[];
        links: any[];
        total: number;
    };
}

defineProps<Props>();
const { t } = useTrans();
</script>

<template>
    <CustomerLayout>
        <Head title="Order History — D STORE" />

        <div class="max-w-5xl mx-auto px-4 sm:px-8 py-12">
            <h1 class="text-3xl font-black text-[#111111] tracking-tight mb-4">
                {{ t('account.my_orders') }}
            </h1>
            <AccountNav />

            <div v-if="orders.data.length === 0" class="bg-white rounded-3xl p-12 text-center border border-[#E5E5EA] space-y-4">
                <ShoppingBag class="w-12 h-12 text-[#86868B] mx-auto" />
                <h3 class="text-base font-bold text-[#111111]">No Orders Yet</h3>
                <p class="text-xs text-[#86868B]">When you place an order with D STORE, it will appear here.</p>
                <Link href="/shop">
                    <Button variant="primary">Start Shopping</Button>
                </Link>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="order in orders.data"
                    :key="order.id"
                    class="bg-white rounded-2xl p-6 border border-[#E5E5EA] shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-sm font-bold text-[#111111]">
                                Order #{{ order.order_number }}
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-[#111111] text-white">
                                {{ order.status }}
                            </span>
                        </div>
                        <p class="text-xs text-[#86868B] mt-1">
                            {{ order.created_at }} • {{ order.items?.length || 1 }} item(s) • Payment: <span class="capitalize">{{ order.payment_status }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-6 w-full sm:w-auto justify-between sm:justify-end">
                        <div class="text-right">
                            <p class="text-base font-bold text-[#111111]">${{ order.total }}</p>
                            <p class="text-[10px] text-[#86868B]">៛{{ order.total_khr.toLocaleString() }}</p>
                        </div>
                        <Link :href="`/account/orders/${order.order_number}`">
                            <Button variant="secondary" size="sm">
                                <span>Details</span>
                                <ArrowRight class="w-3.5 h-3.5 ml-1" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
