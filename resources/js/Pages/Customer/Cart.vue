<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import { useTrans } from '@/Composables/useTrans';
import { Trash2, Plus, Minus, ArrowRight, Tag, ShoppingBag, ShieldCheck } from 'lucide-vue-next';

interface CartItem {
    id: number;
    variant_id: number;
    product_name_en: string;
    product_name_km: string;
    product_slug: string;
    variant_sku: string;
    image_url: string;
    unit_price: string;
    unit_price_khr: number;
    quantity: number;
    available_stock: number;
    subtotal: string;
    subtotal_khr: number;
}

interface Summary {
    subtotal: string;
    subtotal_khr: number;
    discount: string;
    discount_khr: number;
    total: string;
    total_khr: number;
    coupon_code?: string | null;
    coupon_discount?: string | null;
}

interface Props {
    items: CartItem[];
    summary: Summary;
}

const props = defineProps<Props>();
const { t, currentLocale } = useTrans();

const couponInput = ref('');
const isApplyingCoupon = ref(false);

const updateQty = (item: CartItem, newQty: number) => {
    if (newQty < 1 || newQty > item.available_stock) return;

    router.put(`/cart/${item.id}`, { quantity: newQty }, {
        preserveScroll: true,
    });
};

const removeItem = (item: CartItem) => {
    router.delete(`/cart/${item.id}`, {
        preserveScroll: true,
    });
};

const applyCoupon = () => {
    if (!couponInput.value.trim()) return;

    isApplyingCoupon.value = true;
    router.post('/cart/coupon', { code: couponInput.value.trim() }, {
        preserveScroll: true,
        onFinish: () => {
            isApplyingCoupon.value = false;
            couponInput.value = '';
        },
    });
};

const removeCoupon = () => {
    router.delete('/cart/coupon', {
        preserveScroll: true,
    });
};
</script>

<template>
    <CustomerLayout>
        <Head :title="t('cart.title')" />

        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-12">
            <h1 class="text-3xl font-black text-[#111111] tracking-tight mb-8">
                {{ t('cart.title') }}
            </h1>

            <!-- If Cart is Empty -->
            <div
                v-if="items.length === 0"
                class="bg-white rounded-3xl p-12 text-center border border-[#E5E5EA] max-w-lg mx-auto space-y-4 my-12"
            >
                <div class="w-16 h-16 bg-[#F5F5F7] rounded-full mx-auto flex items-center justify-center text-[#86868B]">
                    <ShoppingBag class="w-8 h-8" />
                </div>
                <h2 class="text-lg font-bold text-[#111111]">{{ t('cart.empty') }}</h2>
                <p class="text-xs text-[#86868B] max-w-sm mx-auto leading-relaxed">
                    {{ t('cart.empty_desc') }}
                </p>
                <div class="pt-2">
                    <Link href="/shop">
                        <Button variant="primary">
                            {{ t('cart.start_shopping') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Two-Column Cart Items + Summary -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Items Table / List -->
                <div class="lg:col-span-2 space-y-4">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="bg-white rounded-2xl p-5 border border-[#E5E5EA] flex flex-col sm:flex-row items-center gap-5 transition-shadow hover:shadow-sm"
                    >
                        <!-- Thumbnail -->
                        <Link :href="`/products/${item.product_slug}`" class="w-20 h-20 sm:w-24 sm:h-24 bg-[#F5F5F7] rounded-xl p-2 shrink-0 flex items-center justify-center overflow-hidden">
                            <img :src="item.image_url" :alt="item.product_name_en" class="h-full w-full object-contain" />
                        </Link>

                        <!-- Item Info -->
                        <div class="flex-1 min-w-0 text-center sm:text-left">
                            <h3 class="text-sm font-bold text-[#111111] truncate">
                                <Link :href="`/products/${item.product_slug}`" class="hover:text-[#0071E3]">
                                    {{ currentLocale === 'km' && item.product_name_km ? item.product_name_km : item.product_name_en }}
                                </Link>
                            </h3>
                            <p class="text-[11px] text-[#86868B] font-mono mt-0.5">
                                SKU: {{ item.variant_sku }}
                            </p>
                            <p class="text-xs font-semibold text-[#111111] mt-1">
                                ${{ item.unit_price }}
                                <span class="text-[10px] text-[#86868B] font-normal">({{ '៛' + item.unit_price_khr.toLocaleString() }})</span>
                            </p>
                        </div>

                        <!-- Quantity Selector -->
                        <div class="flex items-center rounded-xl border border-[#E5E5EA] bg-[#F5F5F7] p-1">
                            <button
                                type="button"
                                @click="updateQty(item, item.quantity - 1)"
                                :disabled="item.quantity <= 1"
                                class="p-1 text-[#86868B] hover:text-[#111111] disabled:opacity-30 cursor-pointer"
                            >
                                <Minus class="w-3.5 h-3.5" />
                            </button>
                            <span class="w-8 text-center text-xs font-bold text-[#111111]">
                                {{ item.quantity }}
                            </span>
                            <button
                                type="button"
                                @click="updateQty(item, item.quantity + 1)"
                                :disabled="item.quantity >= item.available_stock"
                                class="p-1 text-[#86868B] hover:text-[#111111] disabled:opacity-30 cursor-pointer"
                            >
                                <Plus class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Line Total & Delete -->
                        <div class="text-center sm:text-right shrink-0">
                            <div class="text-sm font-bold text-[#111111]">
                                ${{ item.subtotal }}
                            </div>
                            <div class="text-[10px] text-[#86868B]">
                                ៛{{ item.subtotal_khr.toLocaleString() }}
                            </div>
                            <button
                                type="button"
                                @click="removeItem(item)"
                                class="mt-2 text-xs text-[#DC2626] hover:underline flex items-center justify-center sm:justify-end gap-1 font-medium cursor-pointer"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                                <span>Remove</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Card -->
                <div class="space-y-6">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                        <h2 class="text-base font-bold text-[#111111]">
                            {{ t('cart.summary') }}
                        </h2>

                        <!-- Promo Code Input -->
                        <div class="space-y-2">
                            <div v-if="!summary.coupon_code" class="flex gap-2">
                                <input
                                    v-model="couponInput"
                                    type="text"
                                    placeholder="Enter promo code"
                                    class="flex-1 px-3.5 py-2.5 bg-[#F5F5F7] rounded-xl text-xs uppercase font-mono tracking-wider border border-[#E5E5EA] focus:outline-none focus:border-[#111111]"
                                />
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    :loading="isApplyingCoupon"
                                    @click="applyCoupon"
                                >
                                    {{ t('cart.apply_coupon') }}
                                </Button>
                            </div>
                            <div v-else class="flex items-center justify-between p-3 rounded-xl bg-[#DCFCE7] border border-[#BBF7D0] text-xs">
                                <div class="flex items-center gap-1.5 text-[#166534] font-semibold">
                                    <Tag class="w-3.5 h-3.5" />
                                    <span>{{ summary.coupon_code }} ({{ summary.coupon_discount }})</span>
                                </div>
                                <button
                                    type="button"
                                    @click="removeCoupon"
                                    class="text-xs text-[#DC2626] hover:underline cursor-pointer"
                                >
                                    {{ t('cart.remove_coupon') }}
                                </button>
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="space-y-3 pt-4 border-t border-[#F5F5F7] text-xs">
                            <div class="flex justify-between text-[#86868B]">
                                <span>{{ t('cart.subtotal') }}</span>
                                <span class="font-semibold text-[#111111]">${{ summary.subtotal }}</span>
                            </div>

                            <div v-if="parseFloat(summary.discount) > 0" class="flex justify-between text-[#16A34A] font-semibold">
                                <span>{{ t('cart.discount') }}</span>
                                <span>-${{ summary.discount }}</span>
                            </div>

                            <div class="flex justify-between text-[#86868B]">
                                <span>{{ t('cart.shipping_estimate') }}</span>
                                <span class="text-[#16A34A] font-semibold">Calculated at Checkout</span>
                            </div>

                            <div class="flex justify-between pt-4 border-t border-[#E5E5EA] text-base font-extrabold text-[#111111]">
                                <span>{{ t('cart.total') }}</span>
                                <div class="text-right">
                                    <div>${{ summary.total }}</div>
                                    <div class="text-xs font-semibold text-[#86868B]">
                                        ៛{{ summary.total_khr.toLocaleString() }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Checkout Button -->
                        <Link href="/checkout" class="block w-full">
                            <Button variant="primary" size="lg" class="w-full">
                                <span>{{ t('cart.proceed_to_checkout') }}</span>
                                <ArrowRight class="w-4 h-4 ml-1" />
                            </Button>
                        </Link>

                        <div class="flex items-center justify-center gap-2 text-[11px] text-[#86868B]">
                            <ShieldCheck class="w-3.5 h-3.5 text-[#16A34A]" />
                            <span>Official NBC Bakong KHQR & ABA Secured</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
