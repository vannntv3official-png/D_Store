<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import { useTrans } from '@/Composables/useTrans';
import { CheckCircle2, QrCode, Landmark, Copy, Check, Printer } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    order: {
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
        payment_method: string;
    };
    khqrPayload?: string | null;
    bankDetails: {
        bank_name: string;
        account_name: string;
        account_usd: string;
        account_khr: string;
    };
}

const props = defineProps<Props>();
const { t } = useTrans();

const copied = ref(false);
const copyOrderNumber = () => {
    navigator.clipboard.writeText(props.order.order_number);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
};

const printOrder = () => {
    window.print();
};
</script>

<template>
    <CustomerLayout>
        <Head title="Order Confirmed — D STORE" />

        <div class="max-w-4xl mx-auto px-4 sm:px-8 py-16">
            <!-- Order Success Celebration -->
            <div class="bg-white rounded-3xl p-8 sm:p-12 border border-[#E5E5EA] shadow-sm text-center space-y-4 mb-8">
                <div class="w-16 h-16 bg-[#DCFCE7] text-[#16A34A] rounded-full mx-auto flex items-center justify-center">
                    <CheckCircle2 class="w-10 h-10" />
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#111111] tracking-tight">
                    {{ t('checkout.success') }}
                </h1>
                <p class="text-xs sm:text-sm text-[#86868B] max-w-md mx-auto leading-relaxed">
                    Thank you for your order. We have secured your items and prepared your delivery request.
                </p>

                <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#F5F5F7] rounded-xl text-xs font-mono font-bold text-[#111111]">
                    <span>{{ t('order.order_number') }} {{ order.order_number }}</span>
                    <button type="button" @click="copyOrderNumber" class="text-[#86868B] hover:text-[#111111] cursor-pointer">
                        <Check v-if="copied" class="w-3.5 h-3.5 text-[#16A34A]" />
                        <Copy v-else class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            <!-- Payment Instructions (If KHQR or Bank Transfer) -->
            <div
                v-if="order.payment_method === 'khqr_sandbox' && order.payment_status === 'pending'"
                class="bg-white rounded-3xl p-8 border border-[#E5E5EA] shadow-sm mb-8 space-y-6"
            >
                <div class="flex items-center gap-2.5 pb-4 border-b border-[#F5F5F7]">
                    <QrCode class="w-5 h-5 text-[#0071E3]" />
                    <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111]">
                        {{ t('checkout.payment_khqr') }} (NBC Bakong)
                    </h2>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-8">
                    <!-- KHQR Emulated Code Display -->
                    <div class="p-6 bg-white rounded-2xl border-2 border-[#111111] text-center space-y-3 shadow-lg">
                        <div class="bg-[#111111] text-white text-[11px] font-black uppercase tracking-widest py-1 px-4 rounded-lg">
                            BAKONG KHQR
                        </div>
                        <div class="w-48 h-48 bg-[#F5F5F7] rounded-xl flex flex-col items-center justify-center p-3 text-center border border-[#E5E5EA]">
                            <QrCode class="w-32 h-32 text-[#111111]" />
                            <span class="text-[10px] font-mono text-[#86868B] truncate max-w-full">
                                {{ order.order_number }}
                            </span>
                        </div>
                        <div class="text-xs font-bold text-[#111111]">
                            ${{ order.total }} (៛{{ order.total_khr.toLocaleString() }})
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="flex-1 space-y-3 text-xs text-[#1D1D1F] leading-relaxed">
                        <p class="font-bold text-sm text-[#111111]">
                            {{ t('order.khqr_instructions') }}
                        </p>
                        <ol class="list-decimal pl-4 space-y-1.5 text-[#86868B]">
                            <li>Open your banking app (ABA Mobile, ACLEDA, Wing, Sathapana, etc.).</li>
                            <li>Tap "Scan QR" and point your camera at the Bakong KHQR code.</li>
                            <li>Confirm payment of ${{ order.total }} / ៛{{ order.total_khr.toLocaleString() }}.</li>
                            <li>Your order status will automatically update to Paid.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div
                v-else-if="order.payment_method === 'bank_transfer' && order.payment_status === 'pending'"
                class="bg-white rounded-3xl p-8 border border-[#E5E5EA] shadow-sm mb-8 space-y-6"
            >
                <div class="flex items-center gap-2.5 pb-4 border-b border-[#F5F5F7]">
                    <Landmark class="w-5 h-5 text-[#111111]" />
                    <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111]">
                        {{ t('checkout.payment_bank') }}
                    </h2>
                </div>

                <div class="space-y-3 text-xs leading-relaxed">
                    <p class="font-semibold text-[#111111]">
                        {{ t('order.bank_instructions') }}
                    </p>
                    <div class="p-5 rounded-2xl bg-[#F5F5F7] space-y-2 font-mono text-xs text-[#111111]">
                        <div><span class="text-[#86868B]">Bank:</span> {{ bankDetails.bank_name }}</div>
                        <div><span class="text-[#86868B]">Account Name:</span> {{ bankDetails.account_name }}</div>
                        <div><span class="text-[#86868B]">USD Account:</span> {{ bankDetails.account_usd }}</div>
                        <div><span class="text-[#86868B]">KHR Account:</span> {{ bankDetails.account_khr }}</div>
                        <div><span class="text-[#86868B]">Reference:</span> {{ order.order_number }}</div>
                    </div>
                </div>
            </div>

            <!-- Order Line Items & Summary Card -->
            <div class="bg-white rounded-3xl p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-[#F5F5F7]">
                    <h2 class="text-base font-bold text-[#111111]">Order Details</h2>
                    <Button variant="secondary" size="sm" @click="printOrder">
                        <Printer class="w-3.5 h-3.5 mr-1" />
                        Print
                    </Button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(item, idx) in order.items"
                        :key="idx"
                        class="flex items-center justify-between text-xs py-2 border-b border-[#F5F5F7]"
                    >
                        <div>
                            <p class="font-bold text-[#111111]">{{ item.name_en }}</p>
                            <p class="text-[11px] text-[#86868B]">Qty: {{ item.quantity }} × ${{ item.unit_price }}</p>
                        </div>
                        <span class="font-bold text-[#111111]">${{ item.subtotal }}</span>
                    </div>
                </div>

                <div class="space-y-2 pt-4 text-xs">
                    <div class="flex justify-between text-[#86868B]">
                        <span>Subtotal</span>
                        <span class="font-semibold text-[#111111]">${{ order.subtotal }}</span>
                    </div>
                    <div v-if="parseFloat(order.discount) > 0" class="flex justify-between text-[#16A34A] font-semibold">
                        <span>Discount</span>
                        <span>-${{ order.discount }}</span>
                    </div>
                    <div class="flex justify-between text-[#86868B]">
                        <span>Delivery Fee</span>
                        <span class="font-semibold text-[#111111]">${{ order.shipping_fee }}</span>
                    </div>
                    <div class="flex justify-between pt-4 border-t border-[#E5E5EA] text-base font-black text-[#111111]">
                        <span>Total Amount</span>
                        <div class="text-right">
                            <div>${{ order.total }}</div>
                            <div class="text-xs font-semibold text-[#86868B]">
                                ៛{{ order.total_khr.toLocaleString() }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-[#E5E5EA] flex flex-wrap gap-4 justify-between items-center">
                    <Link href="/shop">
                        <Button variant="secondary">Continue Shopping</Button>
                    </Link>
                    <Link
                        v-if="order.guest_token"
                        :href="`/guest/orders/${order.guest_token}`"
                    >
                        <Button variant="primary">Track Order</Button>
                    </Link>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
