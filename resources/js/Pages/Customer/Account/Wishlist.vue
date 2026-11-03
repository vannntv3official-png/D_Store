<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import AccountNav from '@/Components/AccountNav.vue';
import Button from '@/Components/Button.vue';
import Badge from '@/Components/Badge.vue';
import { useTrans } from '@/Composables/useTrans';
import { Heart, Trash2, ShoppingBag, ArrowRight } from 'lucide-vue-next';

interface WishlistProduct {
    id: number;
    name_en: string;
    name_km: string;
    slug: string;
    brand?: string | null;
    base_price: string;
    base_price_khr: string;
    primary_image: string;
    in_stock: boolean;
}

interface Props {
    products: WishlistProduct[];
}

defineProps<Props>();
const { t, currentLocale } = useTrans();

const removeFromWishlist = (id: number) => {
    router.post(`/account/wishlist/${id}`);
};
</script>

<template>
    <CustomerLayout>
        <Head title="Wishlist — D STORE" />

        <div class="max-w-6xl mx-auto px-4 sm:px-8 py-12">
            <div class="mb-4">
                <h1 class="text-3xl font-black text-[#111111] tracking-tight">
                    {{ t('account.my_wishlist') }}
                </h1>
                <p class="text-xs text-[#86868B] mt-1">Saved items you love, reserved for your future shopping convenience.</p>
            </div>

            <AccountNav />

            <!-- Empty State -->
            <div v-if="products.length === 0" class="bg-white rounded-3xl p-12 text-center border border-[#E5E5EA] space-y-4">
                <Heart class="w-12 h-12 text-[#86868B] mx-auto" />
                <h3 class="text-base font-bold text-[#111111]">Your Wishlist is Empty</h3>
                <p class="text-xs text-[#86868B]">Explore D STORE's luxury catalog and tap the heart icon on any product to save it here.</p>
                <Link href="/shop">
                    <Button variant="primary">Explore Collection</Button>
                </Link>
            </div>

            <!-- Wishlist Grid -->
            <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div
                    v-for="item in products"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-[#E5E5EA] overflow-hidden flex flex-col justify-between group hover:shadow-md transition-all duration-200"
                >
                    <div class="relative aspect-square bg-[#F5F5F7] overflow-hidden">
                        <img
                            :src="item.primary_image"
                            :alt="item.name_en"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                        <button
                            @click="removeFromWishlist(item.id)"
                            class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 backdrop-blur-md text-[#DC2626] flex items-center justify-center shadow-sm hover:scale-110 transition-transform"
                            title="Remove from Wishlist"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <p v-if="item.brand" class="text-[10px] font-bold uppercase tracking-wider text-[#86868B]">
                                {{ item.brand }}
                            </p>
                            <Link :href="`/product/${item.slug}`" class="block mt-1">
                                <h3 class="text-xs font-bold text-[#111111] line-clamp-2 hover:text-[#0071E3] transition-colors">
                                    {{ currentLocale === 'km' ? item.name_km : item.name_en }}
                                </h3>
                            </Link>
                        </div>

                        <div class="mt-4 pt-3 border-t border-[#F5F5F7]">
                            <div class="flex items-baseline justify-between mb-2">
                                <div>
                                    <span class="text-sm font-black text-[#111111]">${{ item.base_price }}</span>
                                    <span class="text-[10px] text-[#86868B] block">៛{{ item.base_price_khr }}</span>
                                </div>
                                <Badge :variant="item.in_stock ? 'success' : 'danger'" size="sm">
                                    {{ item.in_stock ? 'In Stock' : 'Out of Stock' }}
                                </Badge>
                            </div>

                            <Link :href="`/product/${item.slug}`" class="block w-full">
                                <Button variant="secondary" size="sm" class="w-full flex items-center justify-center gap-1.5">
                                    <ShoppingBag class="w-3.5 h-3.5" />
                                    <span>View Product</span>
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
