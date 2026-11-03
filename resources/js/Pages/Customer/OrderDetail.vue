<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import { useTrans } from '@/Composables/useTrans';
import { Package, Truck, CheckCircle2, Clock, ArrowLeft } from 'lucide-vue-next';

interface Props {
    order: {
        id: number;
        order_number: string;
        guest_token?: string | null;
        status: string;
        payment_status: string;
        currency: string;
        subtotal: string;
        discount: string;
        shipping_fee: string;
        total: string;
        total_khr: number;
        created_at: string;
        shipping_address?: any;
        items: any[];
        timeline: any[];
        shipments: any[];
        payment?: any;
    };
    isGuest: boolean;
}

const props = defineProps<Props>();
const { t } = useTrans();
</script>

<template>
    <CustomerLayout>
        <Head :title="'Order #' + order.order_number + ' — D STORE'" />

        <div class="max-w-5xl mx-auto px-4 sm:px-8 py-12">
            <!-- Back Navigation -->
            <div class="mb-6">
                <Link
                    :href="isGuest ? '/shop' : '/account/orders'"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#86868B] hover:text-[#111111]"
                >
                    <ArrowLeft class="w-3.5 h-3.5" />
                    <span>{{ isGuest ? 'Back to Shop' : 'Back to Orders' }}</span>
                </Link>
            </div>

            <!-- Header Card -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-black text-[#111111]">
                            Order #{{ order.order_number }}
                        </h1>
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#111111] text-white">
                            {{ order.status }}
                        </span>
                    </div>
                    <p class="text-xs text-[#86868B] mt-1">
                        Placed on {{ order.created_at }} • Payment: <span class="capitalize font-semibold text-[#111111]">{{ order.payment_status }}</span>
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-2xl font-black text-[#111111]">${{ order.total }}</p>
                    <p class="text-xs text-[#86868B]">៛{{ order.total_khr.toLocaleString() }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left 2 Cols: Order Items & Timeline -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Line Items -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111] pb-3 border-b border-[#F5F5F7]">
                            Items in this order
                        </h2>
                        <div class="divide-y divide-[#F5F5F7]">
                            <div
                                v-for="(item, idx) in order.items"
                                :key="idx"
                                class="py-4 flex items-center gap-4"
                            >
                                <div class="w-16 h-16 rounded-xl bg-[#F5F5F7] p-2 flex items-center justify-center shrink-0">
                                    <img :src="item.image_url" :alt="item.name_en" class="h-full w-full object-contain" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold text-[#111111] truncate">{{ item.name_en }}</h4>
                                    <p class="text-[11px] text-[#86868B] font-mono">SKU: {{ item.sku }}</p>
                                    <p class="text-xs text-[#1D1D1F] mt-1">Qty: {{ item.quantity }} × ${{ item.unit_price }}</p>
                                </div>
                                <div class="text-xs font-bold text-[#111111]">
                                    ${{ item.subtotal }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Timeline -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111] pb-3 border-b border-[#F5F5F7]">
                            Fulfillment Journey
                        </h2>
                        <div v-if="order.timeline && order.timeline.length > 0" class="space-y-4 pl-4 border-l-2 border-[#111111]/10">
                            <div
                                v-for="(tItem, idx) in order.timeline"
                                :key="idx"
                                class="relative pl-4"
                            >
                                <div class="absolute -left-[23px] top-1 w-3 h-3 rounded-full bg-[#111111]"></div>
                                <p class="text-xs font-bold text-[#111111] uppercase tracking-wide">
                                    Status: {{ tItem.to_status || tItem.to }}
                                </p>
                                <p v-if="tItem.comment" class="text-xs text-[#86868B] mt-0.5">{{ tItem.comment }}</p>
                                <p class="text-[10px] text-[#86868B] mt-0.5">{{ tItem.created_at }}</p>
                            </div>
                        </div>
                        <p v-else class="text-xs text-[#86868B]">
                            Order received and queued for packing.
                        </p>
                    </div>
                </div>

                <!-- Right 1 Col: Shipping & Payment Summary -->
                <div class="space-y-6">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6 text-xs">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111]">
                            Delivery Destination
                        </h2>
                        <div v-if="order.shipping_address" class="space-y-1 text-[#1D1D1F] leading-relaxed">
                            <p class="font-bold">{{ order.shipping_address.recipient_name }}</p>
                            <p>{{ order.shipping_address.phone }}</p>
                            <p>{{ order.shipping_address.street_address }}</p>
                            <p>{{ order.shipping_address.district }}, {{ order.shipping_address.province }}</p>
                        </div>

                        <!-- Shipments -->
                        <div v-if="order.shipments && order.shipments.length > 0" class="pt-4 border-t border-[#F5F5F7]">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[#111111] mb-2">Shipment Tracking</h3>
                            <div v-for="ship in order.shipments" :key="ship.id" class="p-3 bg-[#F5F5F7] rounded-xl space-y-1">
                                <p class="font-semibold">{{ ship.carrier_name || 'D STORE Dispatch' }}</p>
                                <p class="font-mono text-[11px] text-[#0071E3]">{{ ship.tracking_number }}</p>
                                <p class="text-[10px] text-[#86868B] capitalize">Status: {{ ship.status }}</p>
                            </div>
                        </div>

                        <!-- Totals Breakdown -->
                        <div class="pt-4 border-t border-[#F5F5F7] space-y-2">
                            <div class="flex justify-between text-[#86868B]">
                                <span>Subtotal</span>
                                <span class="font-bold text-[#111111]">${{ order.subtotal }}</span>
                            </div>
                            <div v-if="parseFloat(order.discount) > 0" class="flex justify-between text-[#16A34A] font-semibold">
                                <span>Discount</span>
                                <span>-${{ order.discount }}</span>
                            </div>
                            <div class="flex justify-between text-[#86868B]">
                                <span>Delivery Fee</span>
                                <span class="font-bold text-[#111111]">${{ order.shipping_fee }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-[#E5E5EA] text-sm font-black text-[#111111]">
                                <span>Total</span>
                                <span>${{ order.total }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
