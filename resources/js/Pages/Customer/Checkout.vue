<script setup lang="ts">
import { reactive, ref, computed, watch, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import Textarea from '@/Components/Textarea.vue';
import { useTrans } from '@/Composables/useTrans';
import { ShieldCheck, Truck, QrCode, CreditCard, Banknote, Landmark, Award } from 'lucide-vue-next';

interface CartItem {
    id: number;
    name_en: string;
    name_km: string;
    sku: string;
    quantity: number;
    price: string;
    price_khr: number;
    subtotal: string;
    subtotal_khr: number;
}

interface ShippingMethod {
    id: number;
    zone_id: number;
    name_en: string;
    name_km: string;
    estimated_days: string;
    price: string;
    price_khr: number;
}

interface Props {
    cartItems: CartItem[];
    pricing: {
        subtotal: string;
        subtotal_khr: number;
        discount: string;
        discount_khr: number;
        total: string;
        total_khr: number;
        coupon_code?: string | null;
    };
    shippingMethods: ShippingMethod[];
    shippingZones: any[];
    savedAddresses: any[];
    loyaltyBalance: number;
    initialIdempotencyKey: string;
}

const props = defineProps<Props>();
const { t, currentLocale, currentCurrency, exchangeRate } = useTrans();

const form = reactive({
    recipient_name: props.savedAddresses[0]?.recipient_name || '',
    phone: props.savedAddresses[0]?.phone || '',
    email: '',
    province: props.savedAddresses[0]?.province || 'Phnom Penh',
    district: props.savedAddresses[0]?.district || 'Daun Penh',
    commune: props.savedAddresses[0]?.commune || '',
    street_address: props.savedAddresses[0]?.street_address || '',
    delivery_notes: '',
    shipping_method_id: props.shippingMethods[0]?.id || 1,
    payment_method: 'khqr_sandbox',
    points_to_redeem: 0,
    idempotency_key: props.initialIdempotencyKey,
});

const generateIdempotencyKey = () => {
    return typeof crypto !== 'undefined' && crypto.randomUUID
        ? crypto.randomUUID()
        : 'idemp-' + Date.now() + '-' + Math.random().toString(36).substring(2, 9);
};

onMounted(() => {
    if (!form.idempotency_key) {
        form.idempotency_key = generateIdempotencyKey();
    }
});

watch(() => form.payment_method, () => {
    form.idempotency_key = generateIdempotencyKey();
});

const isPlacingOrder = ref(false);
const errors = ref<Record<string, string>>({});

// Cambodia Provinces allowlist
const provinceOptions = [
    { value: 'Phnom Penh', label: 'Phnom Penh (រាជធានីភ្នំពេញ)' },
    { value: 'Kandal', label: 'Kandal (ខេត្តកណ្តាល)' },
    { value: 'Siem Reap', label: 'Siem Reap (ខេត្តសៀមរាប)' },
    { value: 'Battambang', label: 'Battambang (ខេត្តបាត់ដំបង)' },
    { value: 'Preah Sihanouk', label: 'Preah Sihanouk (ខេត្តព្រះសីហនុ)' },
    { value: 'Kampot', label: 'Kampot (ខេត្តកំពត)' },
    { value: 'Kampong Cham', label: 'Kampong Cham (ខេត្តកំពង់ចាម)' },
    { value: 'Takeo', label: 'Takeo (ខេត្តតាកែវ)' },
    { value: 'Prey Veng', label: 'Prey Veng (ខេត្តព្រៃវែង)' },
    { value: 'Svay Rieng', label: 'Svay Rieng (ខេត្តស្វាយរៀង)' },
];

const selectedShippingMethod = computed(() => {
    return props.shippingMethods.find((sm) => sm.id === Number(form.shipping_method_id)) || props.shippingMethods[0];
});

const calculatedGrandTotalUsd = computed(() => {
    const subtotal = parseFloat(props.pricing.subtotal);
    const discount = parseFloat(props.pricing.discount);
    const shipping = parseFloat(selectedShippingMethod.value?.price || '0');
    const loyaltyDiscount = (form.points_to_redeem * 0.01); // 100 points = $1.00

    return Math.max(0, subtotal - discount + shipping - loyaltyDiscount).toFixed(2);
});

const calculatedGrandTotalKhr = computed(() => {
    const usd = parseFloat(calculatedGrandTotalUsd.value);
    return Math.round((usd * exchangeRate.value) / 100) * 100;
});

const submitOrder = () => {
    isPlacingOrder.value = true;
    errors.value = {};

    router.post('/checkout', form, {
        onError: (err) => {
            errors.value = err;
            isPlacingOrder.value = false;
            form.idempotency_key = generateIdempotencyKey();
        },
        onFinish: () => {
            isPlacingOrder.value = false;
        },
    });
};
</script>

<template>
    <CustomerLayout>
        <Head :title="t('checkout.title')" />

        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-12">
            <h1 class="text-3xl font-black text-[#111111] tracking-tight mb-8">
                {{ t('checkout.title') }}
            </h1>

            <form @submit.prevent="submitOrder" class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                <!-- Left 7 Columns: Delivery & Payment Details -->
                <div class="lg:col-span-7 space-y-8">
                    <!-- Contact Information Card -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-5">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                            <span>1.</span>
                            {{ t('checkout.contact_information') }}
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input
                                v-model="form.recipient_name"
                                :label="t('checkout.full_name')"
                                placeholder="e.g. Sokha Chan"
                                :error="errors.recipient_name"
                                required
                            />
                            <Input
                                v-model="form.phone"
                                :label="t('checkout.phone_number')"
                                placeholder="012 345 678"
                                :error="errors.phone"
                                required
                            />
                        </div>

                        <Input
                            v-model="form.email"
                            type="email"
                            :label="t('checkout.email_address')"
                            placeholder="Optional order tracking receipt"
                            :error="errors.email"
                        />
                    </div>

                    <!-- Delivery Address Card -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-5">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                            <span>2.</span>
                            {{ t('checkout.delivery_address') }}
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Select
                                v-model="form.province"
                                :label="t('checkout.province_city')"
                                :options="provinceOptions"
                                :error="errors.province"
                                required
                            />
                            <Input
                                v-model="form.district"
                                :label="t('checkout.district')"
                                placeholder="Khan or District"
                                :error="errors.district"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input
                                v-model="form.commune"
                                :label="t('checkout.commune')"
                                placeholder="Sangkat or Commune (optional)"
                                :error="errors.commune"
                            />
                            <Input
                                v-model="form.street_address"
                                :label="t('checkout.street_address')"
                                placeholder="House/Street/Building"
                                :error="errors.street_address"
                                required
                            />
                        </div>

                        <Textarea
                            v-model="form.delivery_notes"
                            :label="t('checkout.notes')"
                            placeholder="Special instructions for driver (e.g. gate code or landmark)"
                            :rows="2"
                        />
                    </div>

                    <!-- Shipping Method Card -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-5">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                            <span>3.</span>
                            {{ t('checkout.delivery_method') }}
                        </h2>

                        <div class="space-y-3">
                            <label
                                v-for="method in shippingMethods"
                                :key="method.id"
                                class="flex items-center justify-between p-4 rounded-2xl border transition-all cursor-pointer"
                                :class="[
                                    form.shipping_method_id === method.id
                                        ? 'border-[#111111] bg-[#F5F5F7] ring-1 ring-[#111111]'
                                        : 'border-[#E5E5EA] hover:border-[#86868B]',
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        type="radio"
                                        :value="method.id"
                                        v-model="form.shipping_method_id"
                                        class="text-[#111111] focus:ring-[#111111]"
                                    />
                                    <div>
                                        <p class="text-xs font-bold text-[#111111]">
                                            {{ currentLocale === 'km' && method.name_km ? method.name_km : method.name_en }}
                                        </p>
                                        <p class="text-[11px] text-[#86868B]">
                                            {{ method.estimated_days }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold text-[#111111]">${{ method.price }}</p>
                                    <p class="text-[10px] text-[#86868B]">៛{{ method.price_khr.toLocaleString() }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Payment Method Card -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-5">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                            <span>4.</span>
                            {{ t('checkout.payment_method') }}
                        </h2>

                        <div class="space-y-3">
                            <!-- Bakong KHQR (NBC) -->
                            <label
                                class="flex items-start gap-4 p-4 rounded-2xl border transition-all cursor-pointer"
                                :class="[
                                    form.payment_method === 'khqr_sandbox'
                                        ? 'border-[#111111] bg-[#F5F5F7] ring-1 ring-[#111111]'
                                        : 'border-[#E5E5EA] hover:border-[#86868B]',
                                ]"
                            >
                                <input
                                    type="radio"
                                    value="khqr_sandbox"
                                    v-model="form.payment_method"
                                    class="mt-1 text-[#111111] focus:ring-[#111111]"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <QrCode class="w-4 h-4 text-[#0071E3]" />
                                        <span class="text-xs font-bold text-[#111111]">{{ t('checkout.payment_khqr') }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#DCFCE7] text-[#166534]">Instant</span>
                                    </div>
                                    <p class="text-xs text-[#86868B] mt-1 leading-relaxed">
                                        {{ t('checkout.payment_khqr_desc') }}
                                    </p>
                                </div>
                            </label>

                            <!-- ABA PayWay -->
                            <label
                                class="flex items-start gap-4 p-4 rounded-2xl border transition-all cursor-pointer"
                                :class="[
                                    form.payment_method === 'aba_payway_sandbox'
                                        ? 'border-[#111111] bg-[#F5F5F7] ring-1 ring-[#111111]'
                                        : 'border-[#E5E5EA] hover:border-[#86868B]',
                                ]"
                            >
                                <input
                                    type="radio"
                                    value="aba_payway_sandbox"
                                    v-model="form.payment_method"
                                    class="mt-1 text-[#111111] focus:ring-[#111111]"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <CreditCard class="w-4 h-4 text-[#0071E3]" />
                                        <span class="text-xs font-bold text-[#111111]">{{ t('checkout.payment_payway') }}</span>
                                    </div>
                                    <p class="text-xs text-[#86868B] mt-1 leading-relaxed">
                                        {{ t('checkout.payment_payway_desc') }}
                                    </p>
                                </div>
                            </label>

                            <!-- Direct Bank Transfer -->
                            <label
                                class="flex items-start gap-4 p-4 rounded-2xl border transition-all cursor-pointer"
                                :class="[
                                    form.payment_method === 'bank_transfer'
                                        ? 'border-[#111111] bg-[#F5F5F7] ring-1 ring-[#111111]'
                                        : 'border-[#E5E5EA] hover:border-[#86868B]',
                                ]"
                            >
                                <input
                                    type="radio"
                                    value="bank_transfer"
                                    v-model="form.payment_method"
                                    class="mt-1 text-[#111111] focus:ring-[#111111]"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <Landmark class="w-4 h-4 text-[#111111]" />
                                        <span class="text-xs font-bold text-[#111111]">{{ t('checkout.payment_bank') }}</span>
                                    </div>
                                    <p class="text-xs text-[#86868B] mt-1 leading-relaxed">
                                        {{ t('checkout.payment_bank_desc') }}
                                    </p>
                                </div>
                            </label>

                            <!-- Cash on Delivery -->
                            <label
                                class="flex items-start gap-4 p-4 rounded-2xl border transition-all cursor-pointer"
                                :class="[
                                    form.payment_method === 'cod'
                                        ? 'border-[#111111] bg-[#F5F5F7] ring-1 ring-[#111111]'
                                        : 'border-[#E5E5EA] hover:border-[#86868B]',
                                ]"
                            >
                                <input
                                    type="radio"
                                    value="cod"
                                    v-model="form.payment_method"
                                    class="mt-1 text-[#111111] focus:ring-[#111111]"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <Banknote class="w-4 h-4 text-[#111111]" />
                                        <span class="text-xs font-bold text-[#111111]">{{ t('checkout.payment_cod') }}</span>
                                    </div>
                                    <p class="text-xs text-[#86868B] mt-1 leading-relaxed">
                                        {{ t('checkout.payment_cod_desc') }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Right 5 Columns: Order Summary & Placement -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6 sticky top-24">
                        <h2 class="text-base font-bold text-[#111111]">
                            {{ t('checkout.order_summary') }}
                        </h2>

                        <!-- Line Items Condensed -->
                        <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                            <div
                                v-for="item in cartItems"
                                :key="item.id"
                                class="flex items-center justify-between text-xs py-2 border-b border-[#F5F5F7]"
                            >
                                <div class="truncate pr-3">
                                    <p class="font-bold text-[#111111] truncate">
                                        {{ currentLocale === 'km' && item.name_km ? item.name_km : item.name_en }}
                                    </p>
                                    <p class="text-[11px] text-[#86868B]">
                                        Qty: {{ item.quantity }} × ${{ item.price }}
                                    </p>
                                </div>
                                <span class="font-bold text-[#111111] shrink-0">${{ item.subtotal }}</span>
                            </div>
                        </div>

                        <!-- Loyalty Points Redemption -->
                        <div v-if="loyaltyBalance > 0" class="p-4 rounded-2xl bg-[#F5F5F7] border border-[#E5E5EA] space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold text-[#111111]">
                                <span class="flex items-center gap-1.5">
                                    <Award class="w-4 h-4 text-[#0071E3]" />
                                    Redeem D-Rewards
                                </span>
                                <span>{{ loyaltyBalance }} pts available</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model.number="form.points_to_redeem"
                                    type="range"
                                    min="0"
                                    :max="loyaltyBalance"
                                    step="50"
                                    class="w-full"
                                />
                                <span class="text-xs font-bold text-[#0071E3] shrink-0">
                                    -{{ form.points_to_redeem }} pts (${{ (form.points_to_redeem * 0.01).toFixed(2) }})
                                </span>
                            </div>
                        </div>

                        <!-- Authoritative Totals -->
                        <div class="space-y-3 pt-4 border-t border-[#F5F5F7] text-xs">
                            <div class="flex justify-between text-[#86868B]">
                                <span>Subtotal</span>
                                <span class="font-bold text-[#111111]">${{ pricing.subtotal }}</span>
                            </div>
                            <div v-if="parseFloat(pricing.discount) > 0" class="flex justify-between text-[#16A34A] font-semibold">
                                <span>Promo Discount</span>
                                <span>-${{ pricing.discount }}</span>
                            </div>
                            <div v-if="form.points_to_redeem > 0" class="flex justify-between text-[#0071E3] font-semibold">
                                <span>Loyalty Points Discount</span>
                                <span>-${{ (form.points_to_redeem * 0.01).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-[#86868B]">
                                <span>Delivery Fee</span>
                                <span class="font-bold text-[#111111]">${{ selectedShippingMethod.price }}</span>
                            </div>

                            <div class="flex justify-between pt-4 border-t border-[#E5E5EA] text-lg font-extrabold text-[#111111]">
                                <span>{{ t('cart.total') }}</span>
                                <div class="text-right">
                                    <div>${{ calculatedGrandTotalUsd }}</div>
                                    <div class="text-xs font-semibold text-[#86868B]">
                                        ៛{{ calculatedGrandTotalKhr.toLocaleString() }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Place Order Button -->
                        <Button
                            type="submit"
                            variant="primary"
                            size="lg"
                            class="w-full text-sm font-bold"
                            :loading="isPlacingOrder"
                        >
                            {{ isPlacingOrder ? t('checkout.placing_order') : t('checkout.place_order') }}
                        </Button>

                        <div class="flex items-center justify-center gap-2 text-[11px] text-[#86868B] text-center">
                            <ShieldCheck class="w-4 h-4 text-[#16A34A] shrink-0" />
                            <span>100% Authorized Payment Security & NBC Protection</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </CustomerLayout>
</template>
