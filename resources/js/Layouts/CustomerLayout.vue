<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useTrans } from '@/Composables/useTrans';
import { 
    Search, ShoppingBag, Heart, User as UserIcon, Menu, X, 
    Globe, DollarSign, ChevronDown, Check, ShieldCheck, Home, LayoutGrid,
    Phone, MapPin, SlidersHorizontal, RotateCcw, LogOut, Package, Award, Sparkles, Store
} from 'lucide-vue-next';

const { t, currentLocale, currentCurrency, exchangeRate, formatPrice, setLocale, setCurrency } = useTrans();
const page = usePage();

const auth = computed(() => (page.props as any).auth || {});
const cart = computed(() => (page.props as any).cart || { item_count: 0, subtotal_usd: '0.00', subtotal_khr: 0 });
const flash = computed(() => (page.props as any).flash || {});
const settings = computed(() => (page.props as any).settings || {});
const currentUrl = computed(() => page.url);

const isMobileMenuOpen = ref(false);
const searchQuery = ref('');
const isUserDropdownOpen = ref(false);
const searchInputRef = ref<HTMLInputElement | null>(null);
const userDropdownRef = ref<HTMLElement | null>(null);

const userInitials = computed(() => {
    if (!auth.value?.user?.name) return 'D';
    const parts = auth.value.user.name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return auth.value.user.name.substring(0, 2).toUpperCase();
});

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.visit(`/shop?q=${encodeURIComponent(searchQuery.value.trim())}`);
        isMobileMenuOpen.value = false;
    }
};

const clearSearch = () => {
    searchQuery.value = '';
    searchInputRef.value?.focus();
};

const logout = () => {
    router.post('/logout');
    isUserDropdownOpen.value = false;
};

const handleClickOutside = (event: MouseEvent) => {
    if (userDropdownRef.value && !userDropdownRef.value.contains(event.target as Node)) {
        isUserDropdownOpen.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        isUserDropdownOpen.value = false;
        isMobileMenuOpen.value = false;
    }
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchInputRef.value?.focus();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#F5F5F7] text-[#1D1D1F] selection:bg-[#111111] selection:text-white">
        <!-- Top Luxury Utility Ribbon (NBC Live Rate, Flagship Store & Segmented Controls) -->
        <div class="bg-[#0B0B0E] text-white/70 text-xs border-b border-white/[0.08] relative z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 py-2 flex items-center justify-between gap-4">
                <!-- Left: Flagship Store Identity & NBC Live Rate -->
                <div class="flex items-center gap-4 text-[11px]">
                    <div class="hidden sm:flex items-center gap-2 font-medium tracking-wide text-white/90">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-pulse"></span>
                        <span class="font-bold tracking-tight text-white">D STORE</span>
                        <span class="text-white/40">•</span>
                        <span class="text-white/75 truncate">{{ settings.store_address || 'Norodom Blvd, Phnom Penh' }}</span>
                    </div>

                    <!-- Live NBC Rate Badge -->
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/[0.08] border border-white/10 text-[10.5px] text-white/90 font-medium">
                        <span class="text-white/60">NBC Rate:</span>
                        <span class="font-bold text-white">$1 ≈ {{ exchangeRate.toLocaleString() }} KHR</span>
                    </div>
                </div>

                <!-- Right: Concierge Hotline & Segmented Pill Switchers -->
                <div class="flex items-center gap-3 sm:gap-4 ml-auto">
                    <!-- Concierge Hotline -->
                    <a
                        :href="`tel:${settings.store_phone || '+85523888999'}`"
                        class="hidden md:inline-flex items-center gap-1.5 text-[11px] text-white/70 hover:text-white transition-colors"
                    >
                        <Phone class="w-3 h-3 text-[#10B981]" />
                        <span>Concierge: {{ settings.store_phone || '+855 23 888 999' }}</span>
                    </a>

                    <span class="hidden md:inline text-white/20">|</span>

                    <!-- Segmented Currency Switcher -->
                    <div class="inline-flex items-center p-0.5 rounded-lg bg-white/[0.08] border border-white/10">
                        <button
                            type="button"
                            @click="setCurrency('USD')"
                            class="px-2 py-0.5 rounded-md text-[10.5px] font-bold transition-all duration-150 cursor-pointer touch-manipulation"
                            :class="currentCurrency === 'USD' ? 'bg-white text-[#111111] shadow-2xs' : 'text-white/70 hover:text-white'"
                        >
                            USD ($)
                        </button>
                        <button
                            type="button"
                            @click="setCurrency('KHR')"
                            class="px-2 py-0.5 rounded-md text-[10.5px] font-bold transition-all duration-150 cursor-pointer touch-manipulation"
                            :class="currentCurrency === 'KHR' ? 'bg-white text-[#111111] shadow-2xs' : 'text-white/70 hover:text-white'"
                        >
                            KHR (៛)
                        </button>
                    </div>

                    <!-- Segmented Language Switcher -->
                    <div class="inline-flex items-center p-0.5 rounded-lg bg-white/[0.08] border border-white/10">
                        <button
                            type="button"
                            @click="setLocale('km')"
                            class="px-2 py-0.5 rounded-md text-[10.5px] font-bold transition-all duration-150 cursor-pointer touch-manipulation"
                            :class="currentLocale === 'km' ? 'bg-white text-[#111111] shadow-2xs' : 'text-white/70 hover:text-white'"
                        >
                            ភាសាខ្មែរ
                        </button>
                        <button
                            type="button"
                            @click="setLocale('en')"
                            class="px-2 py-0.5 rounded-md text-[10.5px] font-bold transition-all duration-150 cursor-pointer touch-manipulation"
                            :class="currentLocale === 'en' ? 'bg-white text-[#111111] shadow-2xs' : 'text-white/70 hover:text-white'"
                        >
                            English
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Luxury Header Bar -->
        <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-2xl border-b border-black/[0.06] shadow-[0_4px_24px_-4px_rgba(0,0,0,0.03)] transition-all pt-[env(safe-area-inset-top)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 h-20 flex items-center justify-between gap-4">
                <!-- Mobile Menu Trigger -->
                <button
                    type="button"
                    class="lg:hidden w-10 h-10 rounded-xl flex items-center justify-center text-[#111111] hover:bg-[#F5F5F7] transition-all touch-manipulation cursor-pointer active:scale-95"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                    aria-label="Toggle navigation menu"
                >
                    <Menu v-if="!isMobileMenuOpen" class="w-5 h-5" />
                    <X v-else class="w-5 h-5" />
                </button>

                <!-- Luxury Brand Emblem & Title -->
                <Link href="/" class="flex items-center gap-3 group shrink-0">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#111111] via-[#222226] to-[#38383F] text-white flex items-center justify-center font-black text-xl tracking-tighter shadow-md ring-1 ring-black/10 transition-transform duration-200 group-hover:scale-105">
                        D
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl font-black tracking-tight text-[#111111] group-hover:text-black transition-colors">
                                D STORE
                            </span>
                            <span class="text-[9px] font-extrabold uppercase tracking-widest px-1.5 py-0.5 rounded-md bg-[#111111] text-white leading-none">
                                KH
                            </span>
                        </div>
                        <span class="text-[9.5px] font-semibold uppercase tracking-widest text-[#86868B] group-hover:text-[#111111] transition-colors">
                            Luxury Flagship
                        </span>
                    </div>
                </Link>

                <!-- Desktop Navigation Pill Menu -->
                <nav class="hidden lg:flex items-center gap-1 text-xs font-semibold">
                    <Link
                        href="/"
                        class="px-3.5 py-2 rounded-xl transition-all duration-150"
                        :class="currentUrl === '/' ? 'bg-[#111111] text-white shadow-xs' : 'text-[#48484A] hover:bg-[#F5F5F7] hover:text-[#111111]'"
                    >
                        {{ t('nav.home') }}
                    </Link>
                    <Link
                        href="/shop"
                        class="px-3.5 py-2 rounded-xl transition-all duration-150"
                        :class="currentUrl === '/shop' ? 'bg-[#111111] text-white shadow-xs' : 'text-[#48484A] hover:bg-[#F5F5F7] hover:text-[#111111]'"
                    >
                        {{ t('nav.shop') }}
                    </Link>
                    <Link
                        href="/shop?category=smartphones"
                        class="px-3.5 py-2 rounded-xl transition-all duration-150"
                        :class="currentUrl.includes('category=smartphones') ? 'bg-[#111111] text-white shadow-xs' : 'text-[#48484A] hover:bg-[#F5F5F7] hover:text-[#111111]'"
                    >
                        Smartphones
                    </Link>
                    <Link
                        href="/shop?category=electronics"
                        class="px-3.5 py-2 rounded-xl transition-all duration-150"
                        :class="currentUrl.includes('category=electronics') ? 'bg-[#111111] text-white shadow-xs' : 'text-[#48484A] hover:bg-[#F5F5F7] hover:text-[#111111]'"
                    >
                        Electronics
                    </Link>
                    <Link
                        href="/shop?category=fashion-clothing"
                        class="px-3.5 py-2 rounded-xl transition-all duration-150"
                        :class="currentUrl.includes('category=fashion-clothing') ? 'bg-[#111111] text-white shadow-xs' : 'text-[#48484A] hover:bg-[#F5F5F7] hover:text-[#111111]'"
                    >
                        Fashion
                    </Link>
                    <Link
                        href="/pages/about-us"
                        class="px-3.5 py-2 rounded-xl transition-all duration-150"
                        :class="currentUrl.startsWith('/pages/about') ? 'bg-[#111111] text-white shadow-xs' : 'text-[#48484A] hover:bg-[#F5F5F7] hover:text-[#111111]'"
                    >
                        {{ t('nav.about') }}
                    </Link>
                </nav>

                <!-- Expanding Luxury Search Bar -->
                <div class="hidden md:flex items-center relative">
                    <form @submit.prevent="handleSearch" class="relative flex items-center transition-all duration-300 w-44 lg:w-56 focus-within:w-64 lg:focus-within:w-76">
                        <Search class="w-4 h-4 text-[#86868B] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('nav.search_placeholder')"
                            class="w-full pl-9 pr-9 py-2 bg-[#F5F5F7] hover:bg-[#EFEFF2] focus:bg-white rounded-full text-xs text-[#1D1D1F] border border-[#E5E5EA] focus:border-[#111111] focus:ring-2 focus:ring-black/5 focus:outline-none transition-all placeholder-[#86868B]"
                        />
                        <!-- Clear button if text entered -->
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded-full hover:bg-black/10 text-[#86868B] hover:text-[#111111] cursor-pointer"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                        <!-- Keyboard shortcut indicator if empty -->
                        <span
                            v-else
                            class="hidden xl:inline-flex items-center px-1.5 py-0.5 text-[9.5px] font-bold text-[#86868B] bg-white border border-[#E5E5EA] rounded shadow-2xs absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                        >
                            ⌘K
                        </span>
                    </form>
                </div>

                <!-- Right Actions: Wishlist, Shopping Bag, User Account -->
                <div class="flex items-center gap-2 sm:gap-2.5">
                    <!-- Wishlist Icon -->
                    <Link
                        v-if="auth.user"
                        href="/account/wishlist"
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-[#1D1D1F] hover:text-black hover:bg-[#F5F5F7] transition-all touch-manipulation relative active:scale-95"
                        :aria-label="t('nav.wishlist')"
                    >
                        <Heart class="w-5 h-5" />
                    </Link>

                    <!-- Shopping Bag -->
                    <Link
                        href="/cart"
                        class="group flex items-center gap-2 h-10 px-3 rounded-xl hover:bg-[#F5F5F7] transition-all touch-manipulation relative active:scale-95 text-[#1D1D1F] hover:text-black"
                        :aria-label="t('nav.cart')"
                    >
                        <div class="relative flex items-center justify-center">
                            <ShoppingBag class="w-5 h-5 transition-transform group-hover:scale-105" />
                            <span
                                v-if="cart.item_count > 0"
                                class="absolute -top-1.5 -right-2 min-w-4 h-4 px-1 bg-[#111111] text-white text-[9.5px] font-black rounded-full flex items-center justify-center shadow-xs animate-pulse"
                            >
                                {{ cart.item_count }}
                            </span>
                        </div>
                        <!-- Cart Subtotal Preview for Desktop -->
                        <span v-if="cart.item_count > 0" class="hidden sm:inline text-xs font-bold text-[#111111]">
                            {{ formatPrice(cart.subtotal_usd) }}
                        </span>
                    </Link>

                    <!-- User Account Dropdown -->
                    <div ref="userDropdownRef" class="relative">
                        <!-- Logged In Button: Avatar + Name + Chevron -->
                        <button
                            v-if="auth.user"
                            type="button"
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-2 p-1.5 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-[#F5F5F7] transition-all active:scale-95 cursor-pointer select-none"
                            :class="isUserDropdownOpen ? 'bg-[#F5F5F7]' : ''"
                        >
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#111111] via-[#222226] to-[#3E3E46] text-white font-bold text-xs flex items-center justify-center shadow-xs ring-2 ring-white">
                                {{ userInitials }}
                            </div>
                            <span class="text-xs font-bold text-[#111111] max-w-[95px] truncate hidden sm:inline">
                                {{ auth.user.name }}
                            </span>
                            <ChevronDown class="w-3.5 h-3.5 text-[#86868B] transition-transform duration-200 hidden sm:block" :class="isUserDropdownOpen ? 'rotate-180 text-[#111111]' : ''" />
                        </button>

                        <!-- Guest Buttons: Sign In / Register -->
                        <div v-else class="flex items-center gap-1.5">
                            <Link
                                href="/login"
                                class="px-3 py-2 text-xs font-bold text-[#111111] hover:bg-[#F5F5F7] rounded-xl transition-all cursor-pointer"
                            >
                                {{ t('nav.login') }}
                            </Link>
                            <Link
                                href="/register"
                                class="hidden sm:inline-flex items-center px-3.5 py-2 text-xs font-bold text-white bg-[#111111] hover:bg-black rounded-xl shadow-xs transition-all active:scale-95 cursor-pointer"
                            >
                                {{ t('nav.register') }}
                            </Link>
                        </div>

                        <!-- Floating Luxury Dropdown Card -->
                        <div
                            v-if="isUserDropdownOpen"
                            class="absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-2xl border border-[#E5E5EA] py-2 z-50 origin-top-right transform transition-all duration-150 ease-out animate-in fade-in zoom-in-95"
                        >
                            <template v-if="auth.user">
                                <!-- Profile Header Card -->
                                <div class="px-4 py-3 border-b border-[#F5F5F7] bg-gradient-to-b from-[#FAFAFA] to-white rounded-t-2xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#111111] to-[#383840] text-white font-bold text-sm flex items-center justify-center shadow-xs">
                                            {{ userInitials }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-[#111111] truncate">{{ auth.user.name }}</p>
                                            <p class="text-[10.5px] text-[#86868B] truncate">{{ auth.user.phone || auth.user.email }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <div class="py-1">
                                    <Link
                                        href="/account/profile"
                                        @click="isUserDropdownOpen = false"
                                        class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-[#1D1D1F] hover:bg-[#F5F5F7] transition-colors"
                                    >
                                        <UserIcon class="w-4 h-4 text-[#86868B]" />
                                        <span>{{ t('account.profile') }}</span>
                                    </Link>
                                    <Link
                                        href="/account/orders"
                                        @click="isUserDropdownOpen = false"
                                        class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-[#1D1D1F] hover:bg-[#F5F5F7] transition-colors"
                                    >
                                        <Package class="w-4 h-4 text-[#86868B]" />
                                        <span>{{ t('nav.orders') }}</span>
                                    </Link>
                                    <Link
                                        href="/account/loyalty"
                                        @click="isUserDropdownOpen = false"
                                        class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-[#1D1D1F] hover:bg-[#F5F5F7] transition-colors"
                                    >
                                        <Award class="w-4 h-4 text-[#86868B]" />
                                        <span>{{ t('nav.loyalty') }}</span>
                                    </Link>
                                    <Link
                                        href="/account/wishlist"
                                        @click="isUserDropdownOpen = false"
                                        class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-[#1D1D1F] hover:bg-[#F5F5F7] transition-colors"
                                    >
                                        <Heart class="w-4 h-4 text-[#86868B]" />
                                        <span>{{ t('nav.wishlist') }}</span>
                                    </Link>
                                    <Link
                                        v-if="auth.is_staff"
                                        href="/admin"
                                        @click="isUserDropdownOpen = false"
                                        class="flex items-center gap-2.5 px-4 py-2 text-xs font-bold text-[#0071E3] hover:bg-[#EFF6FF] transition-colors"
                                    >
                                        <ShieldCheck class="w-4 h-4 text-[#0071E3]" />
                                        <span>{{ t('nav.admin') }}</span>
                                    </Link>
                                </div>

                                <!-- Logout -->
                                <div class="border-t border-[#F5F5F7] pt-1">
                                    <button
                                        type="button"
                                        @click="logout"
                                        class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-[#DC2626] hover:bg-[#FEE2E2] transition-colors cursor-pointer"
                                    >
                                        <LogOut class="w-4 h-4 text-[#DC2626]" />
                                        <span>{{ t('nav.logout') }}</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Slide-Down Drawer -->
            <div v-if="isMobileMenuOpen" class="lg:hidden bg-white/98 backdrop-blur-2xl border-t border-[#E5E5EA] px-5 py-5 space-y-5 animate-in slide-in-from-top-4 duration-200">
                <!-- Mobile Search Bar -->
                <form @submit.prevent="handleSearch" class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="t('nav.search_placeholder')"
                        class="w-full pl-9 pr-9 py-2.5 bg-[#F5F5F7] rounded-xl text-xs text-[#1D1D1F] border border-[#E5E5EA] focus:border-[#111111] focus:bg-white focus:outline-none"
                    />
                    <Search class="w-4 h-4 text-[#86868B] absolute left-3 top-3" />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="absolute right-3 top-3 p-0.5 rounded-full hover:bg-black/10 text-[#86868B]"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </form>

                <!-- Quick Category Chips -->
                <div class="space-y-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#86868B]">
                        {{ t('nav.categories') }}
                    </span>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            href="/shop?category=smartphones"
                            @click="isMobileMenuOpen = false"
                            class="px-3 py-1.5 rounded-lg bg-[#F5F5F7] hover:bg-[#111111] hover:text-white text-xs font-medium transition-colors"
                        >
                            Smartphones
                        </Link>
                        <Link
                            href="/shop?category=electronics"
                            @click="isMobileMenuOpen = false"
                            class="px-3 py-1.5 rounded-lg bg-[#F5F5F7] hover:bg-[#111111] hover:text-white text-xs font-medium transition-colors"
                        >
                            Electronics
                        </Link>
                        <Link
                            href="/shop?category=fashion-clothing"
                            @click="isMobileMenuOpen = false"
                            class="px-3 py-1.5 rounded-lg bg-[#F5F5F7] hover:bg-[#111111] hover:text-white text-xs font-medium transition-colors"
                        >
                            Fashion
                        </Link>
                        <Link
                            href="/shop?category=vehicles-automotive"
                            @click="isMobileMenuOpen = false"
                            class="px-3 py-1.5 rounded-lg bg-[#F5F5F7] hover:bg-[#111111] hover:text-white text-xs font-medium transition-colors"
                        >
                            Vehicles
                        </Link>
                    </div>
                </div>

                <!-- Main Navigation Links -->
                <nav class="flex flex-col space-y-1 text-sm font-semibold divide-y divide-[#F5F5F7]">
                    <Link
                        href="/"
                        @click="isMobileMenuOpen = false"
                        class="py-2.5 flex items-center justify-between text-[#111111] hover:text-[#0071E3]"
                    >
                        <span>{{ t('nav.home') }}</span>
                    </Link>
                    <Link
                        href="/shop"
                        @click="isMobileMenuOpen = false"
                        class="py-2.5 flex items-center justify-between text-[#111111] hover:text-[#0071E3]"
                    >
                        <span>{{ t('nav.shop') }}</span>
                    </Link>
                    <Link
                        href="/pages/about-us"
                        @click="isMobileMenuOpen = false"
                        class="py-2.5 flex items-center justify-between text-[#111111] hover:text-[#0071E3]"
                    >
                        <span>{{ t('nav.about') }}</span>
                    </Link>
                    <Link
                        href="/contact"
                        @click="isMobileMenuOpen = false"
                        class="py-2.5 flex items-center justify-between text-[#111111] hover:text-[#0071E3]"
                    >
                        <span>{{ t('nav.contact') }}</span>
                    </Link>
                    <Link
                        v-if="!auth.user"
                        href="/login"
                        @click="isMobileMenuOpen = false"
                        class="py-2.5 flex items-center justify-between text-[#111111] font-bold"
                    >
                        <span>{{ t('nav.login') }}</span>
                    </Link>
                </nav>

                <!-- Mobile Language & Currency Quick Switcher -->
                <div class="pt-2 border-t border-[#E5E5EA] flex items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] text-[#86868B]">Currency:</span>
                        <button
                            type="button"
                            @click="setCurrency('USD')"
                            class="px-2 py-1 rounded text-xs font-bold"
                            :class="currentCurrency === 'USD' ? 'bg-[#111111] text-white' : 'bg-[#F5F5F7] text-[#1D1D1F]'"
                        >
                            USD ($)
                        </button>
                        <button
                            type="button"
                            @click="setCurrency('KHR')"
                            class="px-2 py-1 rounded text-xs font-bold"
                            :class="currentCurrency === 'KHR' ? 'bg-[#111111] text-white' : 'bg-[#F5F5F7] text-[#1D1D1F]'"
                        >
                            KHR (៛)
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="setLocale('km')"
                            class="px-2 py-1 rounded text-xs font-bold"
                            :class="currentLocale === 'km' ? 'bg-[#111111] text-white' : 'bg-[#F5F5F7] text-[#1D1D1F]'"
                        >
                            ខ្មែរ
                        </button>
                        <button
                            type="button"
                            @click="setLocale('en')"
                            class="px-2 py-1 rounded text-xs font-bold"
                            :class="currentLocale === 'en' ? 'bg-[#111111] text-white' : 'bg-[#F5F5F7] text-[#1D1D1F]'"
                        >
                            EN
                        </button>
                    </div>
                </div>
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
