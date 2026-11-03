<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import { ShieldCheck, QrCode, CreditCard, Lock, CheckCircle2, ArrowLeft, Zap, Smartphone, ExternalLink } from 'lucide-vue-next';

interface Props {
    reference: string;
    order: {
        order_number: string;
        guest_token?: string | null;
        grand_total: string;
        grand_total_khr: number;
        currency: string;
        customer_name?: string | null;
        customer_phone?: string | null;
        items: Array<{
            name_en: string;
            quantity: number;
            unit_price: string;
            subtotal: string;
        }>;
    };
    payway: {
        merchant_id: string;
        merchant_name: string;
        tran_id: string;
        deeplink: string;
        qr_code_payload?: string | null;
        qr_image_url?: string | null;
        hash?: string | null;
        req_time?: string | null;
    };
    returnUrl: string;
}

const props = defineProps<Props>();

const activeTab = ref<'aba_pay' | 'cards'>('aba_pay');
const isProcessing = ref(false);

// Card simulation form fields
const cardNumber = ref('4000 1234 5678 9010');
const cardExpiry = ref('12/28');
const cardCvv = ref('789');
const cardHolder = ref(props.order.customer_name || 'VALUED CUSTOMER');

const simulateSuccess = () => {
    isProcessing.value = true;
    router.post(`/checkout/payment/payway/${props.reference}/simulate`, {}, {
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#F4F6F9] font-sans antialiased text-[#1F2937]">
        <Head :title="`PayWay by ABA Bank — ${order.order_number}`" />

        <!-- Top Header: ABA PayWay Brand -->
        <header class="bg-[#005a9c] text-white shadow-md">
            <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center font-black text-[#005a9c] text-sm tracking-tight shadow">
                        ABA
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-base tracking-wide text-white">PayWay</span>
                            <span class="text-[10px] bg-[#00BCD4] text-white font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Sandbox</span>
                        </div>
                        <p class="text-[11px] text-cyan-100">{{ payway.merchant_name }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs text-cyan-100 bg-[#00477d] px-3 py-1.5 rounded-xl border border-cyan-400/20">
                    <Lock class="w-3.5 h-3.5 text-cyan-300" />
                    <span class="hidden sm:inline">256-Bit SSL Encrypted</span>
                </div>
            </div>
        </header>

        <!-- Main Checkout Box -->
        <main class="max-w-4xl mx-auto px-4 py-8 sm:py-12">
            <!-- Sandbox Banner Notice -->
            <div class="mb-6 p-4 bg-[#EFF6FF] border border-[#BFDBFE] rounded-2xl flex items-center justify-between text-xs text-[#1E40AF]">
                <div class="flex items-center gap-2.5">
                    <Zap class="w-4 h-4 text-[#2563EB] shrink-0" />
                    <span><strong>Sandbox Simulation Mode:</strong> Real funds will not be charged. You can test instant approval with one click.</span>
                </div>
                <button
                    type="button"
                    :disabled="isProcessing"
                    @click="simulateSuccess"
                    class="bg-[#2563EB] hover:bg-[#1D4ED8] text-white font-bold px-3 py-1.5 rounded-xl shadow-sm text-xs cursor-pointer transition-colors shrink-0"
                >
                    <span v-if="isProcessing">Processing...</span>
                    <span v-else>Fast-Approve (Sandbox)</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                <!-- Left Column: Payment Details & Methods -->
                <div class="md:col-span-7 space-y-6">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E7EB] shadow-sm space-y-6">
                        <!-- Channel Switcher Tabs -->
                        <div class="grid grid-cols-2 gap-2 p-1.5 bg-[#F3F4F6] rounded-2xl">
                            <button
                                type="button"
                                @click="activeTab = 'aba_pay'"
                                class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
                                :class="[
                                    activeTab === 'aba_pay'
                                        ? 'bg-white text-[#005a9c] shadow-sm'
                                        : 'text-[#6B7280] hover:text-[#111827]'
                                ]"
                            >
                                <Smartphone class="w-4 h-4" />
                                <span>ABA Mobile (QR)</span>
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'cards'"
                                class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
                                :class="[
                                    activeTab === 'cards'
                                        ? 'bg-white text-[#005a9c] shadow-sm'
                                        : 'text-[#6B7280] hover:text-[#111827]'
                                ]"
                            >
                                <CreditCard class="w-4 h-4" />
                                <span>Debit / Credit Card</span>
                            </button>
                        </div>

                        <!-- Tab 1: ABA Mobile QR -->
                        <div v-if="activeTab === 'aba_pay'" class="space-y-6 text-center">
                            <div class="p-6 bg-gradient-to-b from-[#005a9c] to-[#034475] rounded-3xl text-white shadow-xl space-y-4 max-w-sm mx-auto">
                                <div class="text-[11px] font-black tracking-widest text-[#38BDF8] uppercase">
                                    Scan to Pay with ABA Mobile
                                </div>

                                <div class="w-56 h-56 bg-white rounded-2xl mx-auto p-4 flex flex-col items-center justify-center border shadow-inner">
                                    <QrCode class="w-40 h-40 text-[#005a9c]" />
                                    <span class="text-[9px] font-mono text-gray-500 truncate max-w-full mt-1">
                                        {{ payway.tran_id }}
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <div class="text-xl font-black text-white">
                                        ${{ order.grand_total }}
                                    </div>
                                    <div class="text-xs font-semibold text-cyan-200">
                                        ៛{{ order.grand_total_khr.toLocaleString() }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 max-w-sm mx-auto">
                                <a
                                    :href="payway.deeplink"
                                    class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-[#005a9c] hover:bg-[#00477d] text-white font-bold text-xs transition-colors shadow-sm"
                                >
                                    <ExternalLink class="w-4 h-4" />
                                    <span>Tap to Pay with ABA Mobile App</span>
                                </a>

                                <button
                                    type="button"
                                    :disabled="isProcessing"
                                    @click="simulateSuccess"
                                    class="w-full py-3 px-4 rounded-2xl border-2 border-[#10B981] bg-[#ECFDF5] hover:bg-[#D1FAE5] text-[#065F46] font-bold text-xs transition-colors cursor-pointer"
                                >
                                    <span v-if="isProcessing">Verifying payment...</span>
                                    <span v-else>Confirm & Simulate Successful Scan</span>
                                </button>
                            </div>

                            <p class="text-[11px] text-[#6B7280]">
                                Open your ABA Mobile app, tap <strong>Scan QR</strong>, and point camera at the QR code.
                            </p>
                        </div>

                        <!-- Tab 2: Credit / Debit Card -->
                        <div v-else class="space-y-5">
                            <div class="flex items-center justify-between pb-3 border-b border-[#F3F4F6]">
                                <span class="text-xs font-bold text-[#111827]">Accepted Card Brands</span>
                                <div class="flex items-center gap-2 text-[10px] font-bold text-[#4B5563]">
                                    <span class="px-2 py-0.5 bg-[#F3F4F6] rounded">Visa</span>
                                    <span class="px-2 py-0.5 bg-[#F3F4F6] rounded">Mastercard</span>
                                    <span class="px-2 py-0.5 bg-[#F3F4F6] rounded">UnionPay</span>
                                    <span class="px-2 py-0.5 bg-[#F3F4F6] rounded">JCB</span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <Input
                                    v-model="cardNumber"
                                    label="Card Number"
                                    placeholder="4000 1234 5678 9010"
                                    required
                                />
                                <div class="grid grid-cols-2 gap-4">
                                    <Input
                                        v-model="cardExpiry"
                                        label="Expiration Date (MM/YY)"
                                        placeholder="12/28"
                                        required
                                    />
                                    <Input
                                        v-model="cardCvv"
                                        label="CVV / CVC"
                                        placeholder="123"
                                        type="password"
                                        maxlength="4"
                                        required
                                    />
                                </div>
                                <Input
                                    v-model="cardHolder"
                                    label="Cardholder Name"
                                    placeholder="Full name as printed on card"
                                    required
                                />
                            </div>

                            <Button
                                variant="primary"
                                size="lg"
                                class="w-full text-xs font-bold bg-[#005a9c] hover:bg-[#00477d] mt-2 cursor-pointer"
                                :disabled="isProcessing"
                                @click="simulateSuccess"
                            >
                                <span v-if="isProcessing">Authorizing Card...</span>
                                <span v-else>Pay ${{ order.grand_total }} via Card</span>
                            </Button>

                            <p class="text-[11px] text-[#6B7280] text-center">
                                Your card details are securely encrypted and processed by ABA Bank PayWay PCI-DSS Level 1 Gateway.
                            </p>
                        </div>
                    </div>

                    <!-- Return link -->
                    <div class="text-center">
                        <Link
                            :href="returnUrl"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#6B7280] hover:text-[#111827] transition-colors"
                        >
                            <ArrowLeft class="w-3.5 h-3.5" />
                            <span>Cancel and Return to D STORE</span>
                        </Link>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="md:col-span-5 space-y-6">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E7EB] shadow-sm space-y-5 text-xs">
                        <div class="flex items-center justify-between pb-3 border-b border-[#F3F4F6]">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[#111827]">Order Summary</h3>
                            <span class="font-mono text-[11px] text-[#6B7280]">{{ order.order_number }}</span>
                        </div>

                        <div class="space-y-3 max-h-60 overflow-y-auto">
                            <div
                                v-for="(item, idx) in order.items"
                                :key="idx"
                                class="flex items-center justify-between text-xs py-1.5 border-b border-[#F9FAFB]"
                            >
                                <div class="max-w-[70%]">
                                    <p class="font-bold text-[#111827] truncate">{{ item.name_en }}</p>
                                    <p class="text-[10px] text-[#6B7280]">{{ item.quantity }} × ${{ item.unit_price }}</p>
                                </div>
                                <span class="font-semibold text-[#111827]">${{ item.subtotal }}</span>
                            </div>
                        </div>

                        <div class="space-y-2 pt-3 border-t border-[#F3F4F6]">
                            <div class="flex justify-between font-mono text-[11px] text-[#6B7280]">
                                <span>Transaction Ref:</span>
                                <span class="font-bold text-[#111827]">{{ payway.tran_id }}</span>
                            </div>
                            <div class="flex justify-between font-mono text-[11px] text-[#6B7280]">
                                <span>Merchant ID:</span>
                                <span class="font-bold text-[#111827]">{{ payway.merchant_id }}</span>
                            </div>
                            <div class="flex justify-between pt-3 border-t border-[#E5E7EB] text-sm font-black text-[#111827]">
                                <span>Total to Pay</span>
                                <div class="text-right">
                                    <div>${{ order.grand_total }}</div>
                                    <div class="text-xs font-semibold text-[#6B7280]">
                                        ៛{{ order.grand_total_khr.toLocaleString() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Badge -->
                    <div class="p-4 bg-white rounded-2xl border border-[#E5E7EB] flex items-center gap-3 text-xs text-[#4B5563]">
                        <ShieldCheck class="w-6 h-6 text-[#10B981] shrink-0" />
                        <p class="text-[11px] leading-relaxed">
                            Certified 3-D Secure 2.0 (3DS2) protection enabled for fraud prevention.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>