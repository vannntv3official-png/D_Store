<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import ProductCard from '@/Components/ProductCard.vue';
import { useTrans } from '@/Composables/useTrans';
import { ShieldCheck, Truck, RotateCcw, Star, Plus, Minus, ShoppingBag, Check } from 'lucide-vue-next';

interface Variant {
    id: number;
    sku: string;
    barcode?: string | null;
    price: string;
    price_khr: number;
    compare_at_price?: string | null;
    available_stock: number;
    is_in_stock: boolean;
    image_url?: string | null;
    attributes: Array<{
        attribute_id: number;
        attribute_name: string;
        value: string;
    }>;
}

interface Props {
    product: {
        id: number;
        name_en: string;
        name_km: string;
        slug: string;
        description_en?: string | null;
        description_km?: string | null;
        short_description_en?: string | null;
        short_description_km?: string | null;
        base_price: string;
        base_price_khr: number;
        compare_at_price?: string | null;
        brand?: { name: string; slug: string } | null;
        category?: { name_en: string; name_km: string; slug: string } | null;
        images: Array<{
            id: number;
            image_url: string;
            alt_text?: string | null;
            is_primary: boolean;
        }>;
        variants: Variant[];
        warranty?: string | null;
        rating_cache: number;
        review_count: number;
    };
    reviews: Array<{
        id: number;
        user_name: string;
        rating: number;
        title: string;
        comment: string;
        is_verified: boolean;
        created_at: string;
    }>;
    relatedProducts: any[];
    canReview: boolean;
}

const props = defineProps<Props>();
const { t, currentLocale } = useTrans();

const selectedVariantIndex = ref(0);
const selectedVariant = computed(() => props.product.variants[selectedVariantIndex.value] || props.product.variants[0]);

const selectedImage = ref(props.product.images[0]?.image_url || '/images/placeholder.svg');
const quantity = ref(1);

watch(selectedVariant, (v) => {
    if (v?.image_url) {
        selectedImage.value = v.image_url;
    }
    if (v && v.available_stock > 0 && quantity.value > v.available_stock) {
        quantity.value = v.available_stock;
    }
});

const isSubmitting = ref(false);

const localizedTitle = computed(() => {
    return currentLocale.value === 'km' && props.product.name_km
        ? props.product.name_km
        : props.product.name_en;
});

const localizedDescription = computed(() => {
    return currentLocale.value === 'km' && props.product.description_km
        ? props.product.description_km
        : (props.product.description_en || '');
});

const incrementQty = () => {
    if (quantity.value < selectedVariant.value.available_stock) {
        quantity.value++;
    }
};

const decrementQty = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const addToCart = () => {
    isSubmitting.value = true;
    router.post('/cart', {
        variant_id: selectedVariant.value.id,
        quantity: quantity.value,
    }, {
        preserveScroll: true,
        onFinish: () => { isSubmitting.value = false; },
    });
};

const buyNow = () => {
    router.post('/cart', {
        variant_id: selectedVariant.value.id,
        quantity: quantity.value,
    }, {
        onSuccess: () => {
            router.visit('/checkout');
        },
    });
};

// Review Form State
const newReview = ref({
    rating: 5,
    title: '',
    comment: '',
});
const isSubmittingReview = ref(false);

const submitReview = () => {
    isSubmittingReview.value = true;
    router.post(`/products/${props.product.slug}/reviews`, newReview.value, {
        preserveScroll: true,
        onSuccess: () => {
            newReview.value = { rating: 5, title: '', comment: '' };
        },
        onFinish: () => {
            isSubmittingReview.value = false;
        },
    });
};
</script>

<template>
    <CustomerLayout>
        <Head :title="localizedTitle + ' — ' + (product.brand?.name || 'D STORE')" />

        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-12">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs text-[#86868B] mb-8">
                <Link href="/" class="hover:text-[#111111]">{{ t('nav.home') }}</Link>
                <span>/</span>
                <Link href="/shop" class="hover:text-[#111111]">{{ t('nav.shop') }}</Link>
                <span>/</span>
                <span class="text-[#111111] font-semibold truncate">{{ localizedTitle }}</span>
            </nav>

            <!-- Main Product Two-Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">
                <!-- Left: Media Gallery -->
                <div class="space-y-4">
                    <div class="relative aspect-square w-full rounded-3xl bg-white border border-[#E5E5EA] overflow-hidden flex items-center justify-center p-8 shadow-sm">
                        <img
                            :src="selectedImage"
                            :alt="localizedTitle"
                            class="h-full w-full object-contain transition-all duration-300"
                        />
                    </div>

                    <!-- Thumbnails -->
                    <div v-if="product.images.length > 1" class="flex items-center gap-3 overflow-x-auto pb-2">
                        <button
                            v-for="img in product.images"
                            :key="img.id"
                            type="button"
                            @click="selectedImage = img.image_url"
                            class="relative w-20 h-20 rounded-2xl bg-white border p-2 flex items-center justify-center overflow-hidden transition-all cursor-pointer"
                            :class="selectedImage === img.image_url ? 'border-[#111111] shadow-md ring-2 ring-[#111111]' : 'border-[#E5E5EA] hover:border-[#86868B]'"
                        >
                            <img :src="img.image_url" :alt="localizedTitle" class="h-full w-full object-contain" />
                        </button>
                    </div>
                </div>

                <!-- Right: Product Purchase Details -->
                <div class="space-y-8">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-[#0071E3] mb-1">
                            {{ product.brand?.name || 'D STORE' }}
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-[#111111] tracking-tight">
                            {{ localizedTitle }}
                        </h1>
                        <p class="text-xs text-[#86868B] mt-1 font-mono">
                            {{ t('product.sku') }}: {{ selectedVariant.sku }}
                        </p>
                    </div>

                    <!-- Pricing & Stock -->
                    <div class="space-y-1 pb-6 border-b border-[#E5E5EA]">
                        <div class="flex items-baseline gap-3">
                            <span class="text-3xl font-extrabold text-[#111111]">
                                ${{ selectedVariant.price }}
                            </span>
                            <span
                                v-if="selectedVariant.compare_at_price"
                                class="text-lg text-[#86868B] line-through"
                            >
                                ${{ selectedVariant.compare_at_price }}
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-[#86868B]">
                            ៛{{ selectedVariant.price_khr.toLocaleString() }}
                        </p>

                        <!-- Stock Status Badge -->
                        <div class="pt-3">
                            <span
                                v-if="selectedVariant.available_stock > 5"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#DCFCE7] text-[#166534]"
                            >
                                <Check class="w-3.5 h-3.5" />
                                {{ t('product.in_stock') }}
                            </span>
                            <span
                                v-else-if="selectedVariant.available_stock > 0"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#FEF3C7] text-[#92400E]"
                            >
                                {{ t('product.low_stock', { count: selectedVariant.available_stock }) }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#FEE2E2] text-[#991B1B]"
                            >
                                {{ t('product.out_of_stock') }}
                            </span>
                        </div>
                    </div>

                    <!-- Variant Selection Pills -->
                    <div v-if="product.variants.length > 1" class="space-y-3">
                        <label class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                            Select Edition / Model
                        </label>
                        <div class="flex flex-wrap gap-2.5">
                            <button
                                v-for="(v, idx) in product.variants"
                                :key="v.id"
                                type="button"
                                @click="selectedVariantIndex = idx"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold border transition-all cursor-pointer flex items-center gap-2"
                                :class="[
                                    selectedVariantIndex === idx
                                        ? 'bg-[#111111] text-white border-[#111111] shadow-md'
                                        : 'bg-white text-[#1D1D1F] border-[#E5E5EA] hover:border-[#86868B]',
                                ]"
                            >
                                <span>{{ v.sku }}</span>
                                <span class="opacity-70 font-normal">(${{ v.price }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quantity & Add to Cart -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <!-- Qty Counter -->
                            <div class="flex items-center rounded-xl border border-[#E5E5EA] bg-white p-1">
                                <button
                                    type="button"
                                    @click="decrementQty"
                                    :disabled="quantity <= 1"
                                    class="p-2 text-[#86868B] hover:text-[#111111] disabled:opacity-30 cursor-pointer"
                                >
                                    <Minus class="w-4 h-4" />
                                </button>
                                <span class="w-10 text-center font-bold text-sm text-[#111111]">
                                    {{ quantity }}
                                </span>
                                <button
                                    type="button"
                                    @click="incrementQty"
                                    :disabled="quantity >= selectedVariant.available_stock"
                                    class="p-2 text-[#86868B] hover:text-[#111111] disabled:opacity-30 cursor-pointer"
                                >
                                    <Plus class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Add to Bag -->
                            <Button
                                variant="primary"
                                size="lg"
                                class="flex-1"
                                :disabled="!selectedVariant.is_in_stock"
                                :loading="isSubmitting"
                                @click="addToCart"
                            >
                                <ShoppingBag class="w-4 h-4" />
                                <span>{{ t('product.add_to_cart') }}</span>
                            </Button>
                        </div>

                        <!-- Buy Now Button -->
                        <Button
                            variant="secondary"
                            size="lg"
                            class="w-full"
                            :disabled="!selectedVariant.is_in_stock"
                            @click="buyNow"
                        >
                            {{ t('product.buy_now') }}
                        </Button>
                    </div>

                    <!-- Guarantee Badges -->
                    <div class="grid grid-cols-2 gap-4 pt-6 border-t border-[#E5E5EA] text-xs text-[#86868B]">
                        <div class="flex items-center gap-2.5">
                            <Truck class="w-4 h-4 text-[#111111]" />
                            <span>Phnom Penh Next-Day Delivery</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <ShieldCheck class="w-4 h-4 text-[#111111]" />
                            <span>Official Distributor Warranty</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description & Specifications Tabs -->
            <div class="mt-20 pt-12 border-t border-[#E5E5EA] space-y-12">
                <div class="max-w-3xl space-y-4">
                    <h2 class="text-xl font-bold text-[#111111]">
                        Product Overview
                    </h2>
                    <div class="prose prose-sm text-[#1D1D1F] leading-relaxed" v-html="localizedDescription"></div>
                </div>

                <!-- Verified Reviews -->
                <div class="pt-12 border-t border-[#E5E5EA] space-y-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-[#111111]">
                            {{ t('product.customer_reviews') }} ({{ reviews.length }})
                        </h3>
                    </div>

                    <!-- Reviews List -->
                    <div v-if="reviews.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div
                            v-for="rev in reviews"
                            :key="rev.id"
                            class="bg-white p-6 rounded-2xl border border-[#E5E5EA] space-y-2.5 shadow-sm"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-[#111111]">{{ rev.user_name }}</span>
                                <div class="flex text-amber-400">
                                    <Star v-for="i in rev.rating" :key="i" class="w-3.5 h-3.5 fill-current" />
                                </div>
                            </div>
                            <h4 class="text-xs font-bold text-[#1D1D1F]">{{ rev.title }}</h4>
                            <p class="text-xs text-[#86868B] leading-relaxed">{{ rev.comment }}</p>
                            <div class="text-[10px] text-[#86868B] pt-2">
                                {{ rev.created_at }}
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-[#86868B]">
                        No verified reviews yet. Be the first to share your experience.
                    </p>

                    <!-- Submit Review Form -->
                    <div v-if="canReview" class="bg-white p-8 rounded-3xl border border-[#E5E5EA] space-y-4 max-w-xl">
                        <h4 class="text-sm font-bold text-[#111111]">
                            {{ t('product.write_review') }}
                        </h4>
                        <form @submit.prevent="submitReview" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#86868B] mb-1">Rating</label>
                                <select v-model="newReview.rating" class="px-3 py-2 bg-[#F5F5F7] rounded-xl text-xs border border-[#E5E5EA]">
                                    <option :value="5">5 Stars — Exceptional</option>
                                    <option :value="4">4 Stars — Very Good</option>
                                    <option :value="3">3 Stars — Average</option>
                                    <option :value="2">2 Stars — Fair</option>
                                    <option :value="1">1 Star — Poor</option>
                                </select>
                            </div>
                            <div>
                                <input
                                    v-model="newReview.title"
                                    type="text"
                                    placeholder="Headline / Title"
                                    required
                                    class="w-full px-4 py-2.5 bg-[#F5F5F7] rounded-xl text-xs border border-[#E5E5EA]"
                                />
                            </div>
                            <div>
                                <textarea
                                    v-model="newReview.comment"
                                    rows="3"
                                    placeholder="Write your feedback..."
                                    required
                                    class="w-full px-4 py-2.5 bg-[#F5F5F7] rounded-xl text-xs border border-[#E5E5EA]"
                                ></textarea>
                            </div>
                            <Button type="submit" variant="primary" :loading="isSubmittingReview">
                                Submit Verified Review
                            </Button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Complementary / Related Products -->
            <div v-if="relatedProducts.length > 0" class="mt-24 pt-12 border-t border-[#E5E5EA]">
                <h3 class="text-2xl font-bold text-[#111111] mb-8">
                    {{ t('product.related_products') }}
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <ProductCard
                        v-for="rel in relatedProducts"
                        :key="rel.id"
                        :product="rel"
                    />
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
