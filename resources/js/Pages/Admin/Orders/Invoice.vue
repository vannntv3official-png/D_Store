<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Button from '@/Components/Button.vue';
import { Printer } from 'lucide-vue-next';

interface OrderItem {
    product_name: string;
    sku: string;
    quantity: number;
    unit_price: string;
    subtotal: string;
}

interface AddressData {
    recipient_name: string;
    phone: string;
    street_address: string;
    district: string;
    commune?: string | null;
    province: string;
}

interface Props {
    order: {
        order_number: string;
        created_at: string;
        subtotal: string;
        discount: string;
        shipping_fee: string;
        total_amount: string;
        total_khr: string;
        customer: {
            name: string;
            email?: string | null;
            phone: string;
        };
        shipping_address?: AddressData | null;
        items: OrderItem[];
        payment_method: string;
    };
    store: {
        name: string;
        address: string;
        phone: string;
        email: string;
    };
}

defineProps<Props>();

const printInvoice = () => {
    window.print();
};
</script>

<template>
    <div class="min-h-screen bg-[#F5F5F7] py-8 sm:py-12 px-4 print:p-0 print:bg-white text-[#1D1D1F]">
        <Head :title="`Invoice #${order.order_number} — D STORE`" />

        <!-- Print Action Button (Hidden on Print) -->
        <div class="max-w-3xl mx-auto mb-6 flex items-center justify-between print:hidden">
            <span class="text-xs text-[#86868B] font-medium">Standard A4 / Thermal Receipt Print Mode</span>
            <Button variant="primary" size="sm" @click="printInvoice" class="flex items-center gap-2">
                <Printer class="w-4 h-4" />
                <span>Print Document</span>
            </Button>
        </div>

        <!-- Invoice Paper Container -->
        <div class="max-w-3xl mx-auto bg-white rounded-3xl p-8 sm:p-14 border border-[#E5E5EA] shadow-sm print:shadow-none print:border-none print:p-0">
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-[#E5E5EA] pb-8 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">{{ store.name }}</h1>
                    <p class="text-xs text-[#86868B] mt-1">{{ store.address }}</p>
                    <p class="text-xs text-[#86868B]">{{ store.phone }} • {{ store.email }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#86868B]">Official Receipt</span>
                    <h2 class="text-xl font-mono font-bold text-[#111111] mt-0.5">#{{ order.order_number }}</h2>
                    <p class="text-xs text-[#86868B] mt-1">{{ order.created_at }}</p>
                    <p class="text-xs font-medium text-[#111111] mt-0.5 uppercase tracking-wider">
                        Payment: {{ order.payment_method }}
                    </p>
                </div>
            </div>

            <!-- Billed / Shipped To -->
            <div class="grid grid-cols-2 gap-8 mb-8 text-xs leading-relaxed">
                <div>
                    <h3 class="font-bold uppercase tracking-wider text-[#86868B] mb-2 text-[10px]">Client</h3>
                    <p class="font-bold text-[#111111]">{{ order.customer.name }}</p>
                    <p class="text-[#86868B]">{{ order.customer.phone }}</p>
                    <p v-if="order.customer.email" class="text-[#86868B]">{{ order.customer.email }}</p>
                </div>

                <div>
                    <h3 class="font-bold uppercase tracking-wider text-[#86868B] mb-2 text-[10px]">Delivery Destination</h3>
                    <div v-if="order.shipping_address">
                        <p class="font-bold text-[#111111]">{{ order.shipping_address.recipient_name }}</p>
                        <p class="text-[#86868B]">{{ order.shipping_address.phone }}</p>
                        <p class="text-[#86868B]">
                            {{ order.shipping_address.street_address }}<br />
                            <span v-if="order.shipping_address.commune">{{ order.shipping_address.commune }}, </span>
                            {{ order.shipping_address.district }}<br />
                            {{ order.shipping_address.province }}, Cambodia
                        </p>
                    </div>
                    <div v-else class="text-[#86868B]">
                        Showroom Pickup
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="w-full text-left border-collapse mb-8 text-xs">
                <thead>
                    <tr class="border-b border-[#E5E5EA] text-[10px] font-bold uppercase tracking-wider text-[#86868B]">
                        <th class="py-3 pr-4">Description</th>
                        <th class="py-3 px-4 text-center">Qty</th>
                        <th class="py-3 px-4 text-right">Unit Price</th>
                        <th class="py-3 pl-4 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F5F5F7]">
                    <tr v-for="(item, idx) in order.items" :key="idx">
                        <td class="py-4 pr-4">
                            <span class="font-bold text-[#111111]">{{ item.product_name }}</span>
                            <span class="block text-[10px] text-[#86868B] font-mono mt-0.5">SKU: {{ item.sku }}</span>
                        </td>
                        <td class="py-4 px-4 text-center font-bold text-[#111111]">
                            {{ item.quantity }}
                        </td>
                        <td class="py-4 px-4 text-right text-[#86868B]">
                            ${{ item.unit_price }}
                        </td>
                        <td class="py-4 pl-4 text-right font-bold text-[#111111]">
                            ${{ item.subtotal }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary -->
            <div class="border-t border-[#E5E5EA] pt-6 flex justify-end">
                <div class="w-64 space-y-2 text-xs">
                    <div class="flex justify-between text-[#86868B]">
                        <span>Subtotal</span>
                        <span class="font-bold text-[#111111]">${{ order.subtotal }}</span>
                    </div>
                    <div v-if="order.discount !== '0.00'" class="flex justify-between text-emerald-600 font-bold">
                        <span>Discounts</span>
                        <span>-${{ order.discount }}</span>
                    </div>
                    <div class="flex justify-between text-[#86868B]">
                        <span>Delivery</span>
                        <span class="font-bold text-[#111111]">${{ order.shipping_fee }}</span>
                    </div>
                    <div class="pt-3 border-t border-[#111111] flex justify-between items-baseline">
                        <span class="text-sm font-black text-[#111111]">Total (USD)</span>
                        <span class="text-base font-black text-[#111111]">${{ order.total_amount }}</span>
                    </div>
                    <div class="flex justify-between items-baseline text-[#86868B]">
                        <span class="text-[11px] font-bold">Settlement (KHR)</span>
                        <span class="text-xs font-bold font-mono">៛{{ order.total_khr }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-14 pt-8 border-t border-[#F5F5F7] text-center text-[11px] text-[#86868B]">
                <p class="font-semibold text-[#111111]">Thank you for choosing D STORE.</p>
                <p class="mt-1">For concierge assistance, please quote your order number when contacting support.</p>
            </div>
        </div>
    </div>
</template>
