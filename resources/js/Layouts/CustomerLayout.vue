<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useTrans } from '@/Composables/useTrans';
import { 
    Search, ShoppingBag, Heart, User as UserIcon, Menu, X, 
    Globe, DollarSign, ChevronDown, Check, ShieldCheck, Home, LayoutGrid 
} from 'lucide-vue-next';

const { t, currentLocale, currentCurrency, setLocale, setCurrency } = useTrans();
const page = usePage();

const auth = computed(() => (page.props as any).auth || {});
const cart = computed(() => (page.props as any).cart || { item_count: 0 });
const flash = computed(() => (page.props as any).flash || {});

const isMobileMenuOpen = ref(false);
const searchQuery = ref('');
const isUserDropdownOpen = ref(false);

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.visit(`/shop?q=${encodeURIComponent(searchQuery.value.trim())}`);
    }
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#F5F5F7] text-[#1D1D1F] selection:bg-[#111111] selection:text-white">
        <!-- Top Utility Bar -->
        <div class="bg-[#111111] text-[#86868B] text-xs py-2 px-4 sm:px-8 border-b border-white/10">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="hidden sm:flex items-center gap-6">
                    <span class="text-white font-medium tracking-wide">D STORE CAMBODIA</span>
                    <span class="text-white/60">Official Flagship Store • Norodom Blvd, Phnom Penh</span>
                </div>

                <div class="flex items-center gap-4 ml-auto">
                    <!-- Currency Switcher -->
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="setCurrency('USD')"
                            class="px-1.5 py-0.5 rounded text-[11px] font-semibold transition-colors cursor-pointer"
                            :class="currentCurrency === 'USD' ? 'text-white bg-white/20' : 'text-[#86868B] hover:text-white'"
                        >
                            USD ($)
                        </button>
                        <span class="text-white/20">|</span>
                        <button
                            type="button"
                            @click="setCurrency('KHR')"
                            class="px-1.5 py-0.5 rounded text-[11px] font-semibold transition-colors cursor-pointer"
                            :class="currentCurrency === 'KHR' ? 'text-white bg-white/20' : 'text-[#86868B] hover:text-white'"
                        >
                            KHR (៛)
                        </button>
                    </div>

                    <span class="text-white/20">|</span>

                    <!-- Language Switcher -->
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="setLocale('km')"
                            class="px-1.5 py-0.5 rounded text-[11px] font-semibold transition-colors cursor-pointer"
                            :class="currentLocale === 'km' ? 'text-white bg-white/20' : 'text-[#86868B] hover:text-white'"
                        >
                            ភាសាខ្មែរ
                        </button>
                        <span class="text-white/20">|</span>
                        <button
                            type="button"
                            @click="setLocale('en')"
                            class="px-1.5 py-0.5 rounded text-[11px] font-semibold transition-colors cursor-pointer"
                            :class="currentLocale === 'en' ? 'text-white bg-white/20' : 'text-[#86868B] hover:text-white'"
                        >
                            English
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Luxury Header -->
        <header class="sticky top-0 z-40 bg-white/85 backdrop-blur-xl saturate-180 border-b border-black/5 transition-all pt-[env(safe-area-inset-top)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 h-18 flex items-center justify-between gap-4">
                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    class="lg:hidden p-2 text-[#1D1D1F] hover:text-black focus:outline-none touch-manipulation cursor-pointer active:scale-95"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                >
                    <Menu v-if="!isMobileMenuOpen" class="w-6 h-6" />
                    <X v-else class="w-6 h-6" />
                </button>

                <!-- Brand Logo -->
                <Link href="/" class="flex items-center gap-2 group">
                    <span class="text-xl font-black tracking-tight text-[#111111] group-hover:opacity-80 transition-opacity">
                        D STORE
                    </span>
                    <span class="text-[10px] font-semibold uppercase tracking-widest px-2 py-0.5 rounded-full bg-[#111111] text-white">
                        KH
                    </span>
                </Link>

                <!-- Desktop Navigation Links -->
                <nav class="hidden lg:flex items-center gap-8 text-sm font-medium text-[#1D1D1F]">
                    <Link href="/shop" class="hover:text-[#0071E3] transition-colors">
                        {{ t('nav.shop') }}
                    </Link>
                    <Link href="/shop?category=smartphones" class="hover:text-[#0071E3] transition-colors">
                        Smartphones
                    </Link>
                    <Link href="/shop?category=electronics" class="hover:text-[#0071E3] transition-colors">
                        Electronics
                    </Link>
                    <Link href="/shop?category=fashion-clothing" class="hover:text-[#0071E3] transition-colors">
                        Fashion
                    </Link>
                    <Link href="/shop?category=vehicles-automotive" class="hover:text-[#0071E3] transition-colors">
                        Vehicles
                    </Link>
                    <Link href="/pages/about-us" class="hover:text-[#0071E3] transition-colors">
                        {{ t('nav.about') }}
                    </Link>
                </nav>

                <!-- Search Input -->
                <div class="hidden md:flex flex-1 max-w-xs relative">
                    <form @submit.prevent="handleSearch" class="w-full">
                        <input
                            v-model="searchQuery"
                            type="search"
                            :placeholder="t('nav.search_placeholder')"
                            class="w-full pl-9 pr-4 py-2 bg-[#F5F5F7] rounded-full text-xs text-[#1D1D1F] border border-transparent focus:border-[#111111] focus:bg-white focus:outline-none transition-all placeholder-[#86868B]"
                        />
                        <Search class="w-4 h-4 text-[#86868B] absolute left-3 top-2.5" />
                    </form>
                </div>

                <!-- Right Utility Icons -->
                <div class="flex items-center gap-3">
                    <!-- Wishlist -->
                    <Link
                        v-if="auth.user"
                        href="/account/wishlist"
                        class="p-2 text-[#1D1D1F] hover:text-black hover:bg-[#F5F5F7] rounded-full transition-[transform,background-color,color] duration-150 active:scale-95 touch-manipulation relative"
                        :aria-label="t('nav.wishlist')"
                    >
                        <Heart class="w-5 h-5" />
                    </Link>

                    <!-- Cart Bag -->
                    <Link
                        href="/cart"
                        class="p-2 text-[#1D1D1F] hover:text-black hover:bg-[#F5F5F7] rounded-full transition-[transform,background-color,color] duration-150 active:scale-95 touch-manipulation relative"
                        :aria-label="t('nav.cart')"
                    >
                        <ShoppingBag class="w-5 h-5" />
                        <span
                            v-if="cart.item_count > 0"
                            class="absolute top-0 right-0 w-4 h-4 bg-[#111111] text-white text-[10px] font-bold rounded-full flex items-center justify-center animate-pulse"
                        >
                            {{ cart.item_count }}
                        </span>
                    </Link>

                    <!-- User Account Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-1.5 p-2 text-[#1D1D1F] hover:text-black hover:bg-[#F5F5F7] rounded-full transition-[transform,background-color,color] duration-150 active:scale-95 touch-manipulation cursor-pointer"
                        >
                            <UserIcon class="w-5 h-5" />
                            <span v-if="auth.user" class="text-xs font-semibold max-w-[80px] truncate hidden sm:inline">
                                {{ auth.user.name }}
                            </span>
                        </button>

                        <!-- Dropdown Menu (Origin-aware entrance) -->
                        <div
                            v-if="isUserDropdownOpen"
                            @click="isUserDropdownOpen = false"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-[#E5E5EA] py-2 z-50 origin-top-right transform transition-[transform,opacity] duration-150 ease-out animate-in fade-in zoom-in-95"
                        >
                            <template v-if="auth.user">
                                <div class="px-4 py-2 border-b border-[#F5F5F7]">
                                    <p class="text-xs font-semibold text-[#111111]">{{ auth.user.name }}</p>
                                    <p class="text-[11px] text-[#86868B] truncate">{{ auth.user.phone || auth.user.email }}</p>
                                </div>
                                <Link href="/account/profile" class="block px-4 py-2 text-xs text-[#1D1D1F] hover:bg-[#F5F5F7]">
                                    {{ t('account.profile') }}
                                </Link>
                                <Link href="/account/orders" class="block px-4 py-2 text-xs text-[#1D1D1F] hover:bg-[#F5F5F7]">
                                    {{ t('nav.orders') }}
                                </Link>
                                <Link href="/account/loyalty" class="block px-4 py-2 text-xs text-[#1D1D1F] hover:bg-[#F5F5F7]">
                                    {{ t('nav.loyalty') }}
                                </Link>
                                <Link v-if="auth.is_staff" href="/admin" class="block px-4 py-2 text-xs font-semibold text-[#0071E3] hover:bg-[#F5F5F7]">
                                    {{ t('nav.admin') }}
                                </Link>
                                <div class="border-t border-[#F5F5F7] mt-1 pt-1">
                                    <button
                                        type="button"
                                        @click="logout"
                                        class="w-full text-left px-4 py-2 text-xs text-[#DC2626] hover:bg-[#FEE2E2]"
                                    >
                                        {{ t('nav.logout') }}
                                    </button>
                                </div>
                            </template>

                            <template v-else>
                                <Link href="/login" class="block px-4 py-2 text-xs font-semibold text-[#111111] hover:bg-[#F5F5F7]">
                                    {{ t('nav.login') }}
                                </Link>
                                <Link href="/register" class="block px-4 py-2 text-xs text-[#1D1D1F] hover:bg-[#F5F5F7]">
                                    {{ t('nav.register') }}
                                </Link>
                                <div class="border-t border-[#F5F5F7] mt-1 pt-1">
                                    <Link href="/admin/login" class="block px-4 py-2 text-[11px] text-[#86868B] hover:text-[#111111]">
                                        {{ t('nav.admin') }}
                                    </Link>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div v-if="isMobileMenuOpen" class="lg:hidden bg-white border-t border-[#E5E5EA] px-6 py-4 space-y-4">
                <form @submit.prevent="handleSearch" class="relative">
                    <input
                        v-model="searchQuery"
                        type="search"
                        :placeholder="t('nav.search_placeholder')"
                        class="w-full pl-9 pr-4 py-2.5 bg-[#F5F5F7] rounded-xl text-xs text-[#1D1D1F] border border-transparent focus:border-[#111111] focus:bg-white focus:outline-none"
                    />
                    <Search class="w-4 h-4 text-[#86868B] absolute left-3 top-3" />
                </form>

                <nav class="flex flex-col space-y-2 text-sm font-medium">
                    <Link href="/shop" class="py-2 hover:text-[#0071E3]">
                        {{ t('nav.shop') }}
                    </Link>
                    <Link href="/shop?category=smartphones" class="py-2 hover:text-[#0071E3]">
                        Smartphones
                    </Link>
                    <Link href="/shop?category=electronics" class="py-2 hover:text-[#0071E3]">
                        Electronics
                    </Link>
                    <Link href="/shop?category=fashion-clothing" class="py-2 hover:text-[#0071E3]">
                        Fashion
                    </Link>
                    <Link href="/shop?category=vehicles-automotive" class="py-2 hover:text-[#0071E3]">
                        Vehicles & Motorcycles
                    </Link>
                    <Link href="/pages/about-us" class="py-2 hover:text-[#0071E3]">
                        {{ t('nav.about') }}
                    </Link>
                    <Link href="/contact" class="py-2 hover:text-[#0071E3]">
                        {{ t('nav.contact') }}
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Flash Messages Notification Banner -->
        <div v-if="flash.success || flash.error || flash.warning" class="max-w-7xl mx-auto px-4 sm:px-8 mt-4 w-full">
            <div
                v-if="flash.success"
                class="p-4 rounded-2xl bg-[#DCFCE7] border border-[#BBF7D0] text-[#166534] text-xs font-semibold flex items-center justify-between"
            >
                <span>{{ flash.success }}</span>
            </div>
            <div
                v-if="flash.error"
                class="p-4 rounded-2xl bg-[#FEE2E2] border border-[#FECACA] text-[#991B1B] text-xs font-semibold flex items-center justify-between"
            >
                <span>{{ flash.error }}</span>
            </div>
            <div
                v-if="flash.warning"
                class="p-4 rounded-2xl bg-[#FEF3C7] border border-[#FDE68A] text-[#92400E] text-xs font-semibold flex items-center justify-between"
            >
                <span>{{ flash.warning }}</span>
            </div>
        </div>

        <!-- Main Content Slot (with mobile bottom padding for native dock) -->
        <main class="flex-1 pb-20 md:pb-0">
            <slot />
        </main>

        <!-- Native Mobile Bottom Tab Navigation Dock (iOS / Android) -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-xl border-t border-[#E5E5EA] px-2 pt-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] shadow-lg flex items-center justify-around select-none">
            <Link
                href="/"
                class="flex flex-col items-center gap-1 py-1 px-3 text-[10px] font-semibold transition-colors touch-manipulation active:scale-95"
                :class="page.url === '/' ? 'text-[#111111]' : 'text-[#86868B] hover:text-[#111111]'"
            >
                <Home class="w-5 h-5" :class="page.url === '/' ? 'stroke-[2.5]' : 'stroke-2'" />
                <span>Home</span>
            </Link>

            <Link
                href="/shop"
                class="flex flex-col items-center gap-1 py-1 px-3 text-[10px] font-semibold transition-colors touch-manipulation active:scale-95"
                :class="page.url.startsWith('/shop') ? 'text-[#111111]' : 'text-[#86868B] hover:text-[#111111]'"
            >
                <LayoutGrid class="w-5 h-5" :class="page.url.startsWith('/shop') ? 'stroke-[2.5]' : 'stroke-2'" />
                <span>Shop</span>
            </Link>

            <Link
                href="/cart"
                class="flex flex-col items-center gap-1 py-1 px-3 text-[10px] font-semibold transition-colors touch-manipulation active:scale-95 relative"
                :class="page.url.startsWith('/cart') ? 'text-[#111111]' : 'text-[#86868B] hover:text-[#111111]'"
            >
                <div class="relative">
                    <ShoppingBag class="w-5 h-5" :class="page.url.startsWith('/cart') ? 'stroke-[2.5]' : 'stroke-2'" />
                    <span
                        v-if="cart.item_count > 0"
                        class="absolute -top-1.5 -right-2 min-w-4 h-4 px-1 bg-[#111111] text-white text-[9px] font-bold rounded-full flex items-center justify-center animate-pulse"
                    >
                        {{ cart.item_count }}
                    </span>
                </div>
                <span>Cart</span>
            </Link>

            <Link
                v-if="auth.user"
                href="/account/wishlist"
                class="flex flex-col items-center gap-1 py-1 px-3 text-[10px] font-semibold transition-colors touch-manipulation active:scale-95"
                :class="page.url.startsWith('/account/wishlist') ? 'text-[#111111]' : 'text-[#86868B] hover:text-[#111111]'"
            >
                <Heart class="w-5 h-5" :class="page.url.startsWith('/account/wishlist') ? 'stroke-[2.5]' : 'stroke-2'" />
                <span>Wishlist</span>
            </Link>

            <Link
                :href="auth.user ? '/account/profile' : '/login'"
                class="flex flex-col items-center gap-1 py-1 px-3 text-[10px] font-semibold transition-colors touch-manipulation active:scale-95"
                :class="page.url.startsWith('/account') || page.url.startsWith('/login') ? 'text-[#111111]' : 'text-[#86868B] hover:text-[#111111]'"
            >
                <UserIcon class="w-5 h-5" :class="page.url.startsWith('/account') || page.url.startsWith('/login') ? 'stroke-[2.5]' : 'stroke-2'" />
                <span>{{ auth.user ? 'Account' : 'Sign In' }}</span>
            </Link>
        </nav>

        <!-- Luxury Apple-Inspired Footer -->
        <footer class="bg-white border-t border-[#E5E5EA] mt-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 py-16">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                    <!-- Column 1: Brand & Slogan -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-black tracking-tight text-[#111111]">D STORE</span>
                            <span class="text-[10px] font-semibold uppercase tracking-widest px-2 py-0.5 rounded-full bg-[#111111] text-white">
                                KH
                            </span>
                        </div>
                        <p class="text-xs text-[#86868B] leading-relaxed">
                            {{ t('app.slogan') }}
                        </p>
                        <p class="text-xs text-[#86868B] leading-relaxed">
                            Phnom Penh, Kingdom of Cambodia.<br />
                            Official Customer Hotline: +855 23 888 999
                        </p>
                    </div>

                    <!-- Column 2: Curated Collections -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                            {{ t('nav.categories') }}
                        </h4>
                        <ul class="space-y-2 text-xs text-[#86868B]">
                            <li><Link href="/shop?category=smartphones" class="hover:text-[#111111]">Smartphones & Flagships</Link></li>
                            <li><Link href="/shop?category=electronics" class="hover:text-[#111111]">Audio & Premium Electronics</Link></li>
                            <li><Link href="/shop?category=fashion-clothing" class="hover:text-[#111111]">Fashion & Haute Couture</Link></li>
                            <li><Link href="/shop?category=vehicles-automotive" class="hover:text-[#111111]">Vehicles & Motorcycles</Link></li>
                        </ul>
                    </div>

                    <!-- Column 3: Customer Concierge -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                            Client Concierge
                        </h4>
                        <ul class="space-y-2 text-xs text-[#86868B]">
                            <li><Link href="/pages/faq" class="hover:text-[#111111]">{{ t('nav.faq') }}</Link></li>
                            <li><Link href="/contact" class="hover:text-[#111111]">{{ t('nav.contact') }}</Link></li>
                            <li><Link href="/cart" class="hover:text-[#111111]">{{ t('nav.cart') }}</Link></li>
                            <li><Link href="/account/orders" class="hover:text-[#111111]">{{ t('nav.orders') }}</Link></li>
                        </ul>
                    </div>

                    <!-- Column 4: Verified Payments & Compliance -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#111111]">
                            Verified Banking
                        </h4>
                        <p class="text-xs text-[#86868B] leading-relaxed">
                            Licensed transactions via NBC Bakong KHQR, ABA PayWay, and Direct Bank Transfer in USD & KHR.
                        </p>
                        <div class="flex items-center gap-2 pt-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-[#F5F5F7] text-[11px] font-semibold text-[#111111] border border-[#E5E5EA]">
                                <ShieldCheck class="w-3.5 h-3.5 text-[#16A34A]" />
                                NBC Bakong KHQR
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-[#F5F5F7] text-[11px] font-semibold text-[#111111] border border-[#E5E5EA]">
                                ABA PayWay
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-16 pt-8 border-t border-[#E5E5EA] flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] text-[#86868B]">
                    <p>© 2026 D STORE Co., Ltd. All rights reserved. Phnom Penh, Cambodia.</p>
                    <div class="flex items-center gap-6">
                        <Link href="/pages/privacy-policy" class="hover:text-[#111111]">Privacy Policy</Link>
                        <Link href="/pages/terms-and-conditions" class="hover:text-[#111111]">Terms of Service</Link>
                        <Link href="/admin/login" class="hover:text-[#111111]">Merchant Portal</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
