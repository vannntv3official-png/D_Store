<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, Package, FolderTree, ShoppingCart, 
    RotateCcw, Warehouse, Megaphone, Award, Users, 
    Settings, ExternalLink, LogOut, History,
    PanelLeftClose, PanelLeft, Search, Menu, X, 
    Clock, ChevronDown, Check, ArrowRight, ShieldCheck, 
    Sparkles, Command
} from 'lucide-vue-next';

const page = usePage();
const auth = computed(() => (page.props as any).auth || {});
const flash = computed(() => (page.props as any).flash || {});
const adminBadges = computed(() => (page.props as any).adminBadges || {});
const exchangeRate = computed(() => (page.props as any).localization?.exchange_rate || 4100);

const currentUrl = computed(() => page.url);

// Sidebar collapse state
const isCollapsed = ref(false);
const isMobileOpen = ref(false);

onMounted(() => {
    try {
        const saved = localStorage.getItem('dstore_admin_sidebar_collapsed');
        if (saved !== null) {
            isCollapsed.value = saved === 'true';
        }
    } catch (e) {
        // localStorage unavailable
    }
});

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
    try {
        localStorage.setItem('dstore_admin_sidebar_collapsed', String(isCollapsed.value));
    } catch (e) {}
};

// Phnom Penh Live Clock
const currentTime = ref('');
let timerInterval: any = null;

const updateClock = () => {
    const d = new Date();
    currentTime.value = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Phnom_Penh',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).format(d);
};

onMounted(() => {
    updateClock();
    timerInterval = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// Command Palette (Spotlight / Raycast style)
const isCommandPaletteOpen = ref(false);
const commandQuery = ref('');
const commandInputRef = ref<HTMLInputElement | null>(null);

const openCommandPalette = () => {
    isCommandPaletteOpen.value = true;
    commandQuery.value = '';
    setTimeout(() => {
        commandInputRef.value?.focus();
    }, 50);
};

const closeCommandPalette = () => {
    isCommandPaletteOpen.value = false;
};

// Keyboard shortcut listener: Cmd+K / Ctrl+K
const handleGlobalKeydown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (isCommandPaletteOpen.value) {
            closeCommandPalette();
        } else {
            openCommandPalette();
        }
    } else if (e.key === 'Escape' && isCommandPaletteOpen.value) {
        closeCommandPalette();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

// User profile dropdown toggle
const isUserMenuOpen = ref(false);

interface NavItem {
    name: string;
    href: string;
    icon: any;
    badgeKey?: string;
    badgeColor?: string;
}

interface NavSection {
    title: string;
    items: NavItem[];
}

const navSections: NavSection[] = [
    {
        title: 'Overview',
        items: [
            { name: 'Dashboard', href: '/admin', icon: LayoutDashboard },
        ],
    },
    {
        title: 'Operations',
        items: [
            { name: 'Orders', href: '/admin/orders', icon: ShoppingCart, badgeKey: 'pending_orders', badgeColor: 'bg-amber-500 text-white' },
            { name: 'Refunds', href: '/admin/refunds', icon: RotateCcw, badgeKey: 'pending_refunds', badgeColor: 'bg-rose-500 text-white' },
            { name: 'Inventory Hub', href: '/admin/inventory', icon: Warehouse, badgeKey: 'low_stock', badgeColor: 'bg-rose-500 text-white' },
            { name: 'Stock Audit', href: '/admin/inventory/movements', icon: History },
        ],
    },
    {
        title: 'Catalog',
        items: [
            { name: 'Products', href: '/admin/products', icon: Package },
            { name: 'Categories', href: '/admin/categories', icon: FolderTree },
        ],
    },
    {
        title: 'Growth & CRM',
        items: [
            { name: 'Marketing & Banners', href: '/admin/marketing', icon: Megaphone },
            { name: 'Loyalty Rewards', href: '/admin/loyalty', icon: Award },
        ],
    },
    {
        title: 'Administration',
        items: [
            { name: 'Staff & Roles', href: '/admin/staff', icon: Users },
            { name: 'Store Settings', href: '/admin/settings', icon: Settings },
        ],
    },
];

// Quick jump search list
const searchableRoutes = [
    { name: 'Dashboard', category: 'Navigation', href: '/admin', icon: LayoutDashboard },
    { name: 'Products Catalog', category: 'Catalog', href: '/admin/products', icon: Package },
    { name: 'Add New Product', category: 'Catalog', href: '/admin/products/create', icon: Package },
    { name: 'Categories', category: 'Catalog', href: '/admin/categories', icon: FolderTree },
    { name: 'Orders & Shipments', category: 'Operations', href: '/admin/orders', icon: ShoppingCart },
    { name: 'Customer Refunds', category: 'Operations', href: '/admin/refunds', icon: RotateCcw },
    { name: 'Inventory & Stock Management', category: 'Operations', href: '/admin/inventory', icon: Warehouse },
    { name: 'Stock Movement Audit Logs', category: 'Operations', href: '/admin/inventory/movements', icon: History },
    { name: 'Marketing Banners & Promos', category: 'Growth', href: '/admin/marketing', icon: Megaphone },
    { name: 'Customer Loyalty & Points', category: 'Growth', href: '/admin/loyalty', icon: Award },
    { name: 'Staff Accounts & Permissions', category: 'Admin', href: '/admin/staff', icon: Users },
    { name: 'Store Settings & Gateways', category: 'Admin', href: '/admin/settings', icon: Settings },
    { name: 'View Customer Storefront', category: 'Store', href: '/', icon: ExternalLink },
];

const filteredCommands = computed(() => {
    if (!commandQuery.value.trim()) return searchableRoutes;
    const q = commandQuery.value.toLowerCase();
    return searchableRoutes.filter(
        (r) => r.name.toLowerCase().includes(q) || r.category.toLowerCase().includes(q)
    );
});

const navigateTo = (url: string) => {
    closeCommandPalette();
    isMobileOpen.value = false;
    router.visit(url);
};

const logout = () => {
    router.post('/admin/logout');
};

const userInitials = computed(() => {
    const name = auth.value?.user?.name || 'Staff User';
    return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase();
});

const userRole = computed(() => {
    const roles = auth.value?.user?.roles || [];
    if (roles.includes('super_admin')) return 'Super Administrator';
    if (roles.includes('admin')) return 'Administrator';
    if (roles.includes('inventory_manager')) return 'Inventory Manager';
    if (roles.length > 0) return roles[0];
    return 'Store Staff';
});
</script>

<template>
    <div class="min-h-screen flex bg-[#F4F5F7] text-[#1D1D1F] font-sans antialiased">
        <!-- Mobile Sidebar Backdrop Overlay -->
        <div
            v-if="isMobileOpen"
            @click="isMobileOpen = false"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden transition-opacity duration-200"
        ></div>

        <!-- Sidebar (Desktop & Mobile Off-canvas) -->
        <aside
            class="fixed inset-y-0 left-0 z-50 lg:static bg-[#111111] text-white flex flex-col shrink-0 border-r border-white/10 transition-all duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
            :class="[
                isCollapsed ? 'lg:w-20' : 'lg:w-64',
                isMobileOpen ? 'translate-x-0 w-72 shadow-2xl' : '-translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- Brand Portal Header -->
            <div class="h-16 px-4 flex items-center justify-between border-b border-white/10">
                <Link
                    href="/admin"
                    class="flex items-center gap-2.5 transition-transform duration-150 active:scale-95"
                    :class="[isCollapsed ? 'lg:justify-center lg:w-full' : '']"
                >
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-white to-[#E5E5EA] flex items-center justify-center text-[#111111] font-black text-xs shadow-md">
                        D
                    </div>
                    <div v-if="!isCollapsed || isMobileOpen" class="flex items-center gap-1.5 overflow-hidden">
                        <span class="text-sm font-black tracking-tight text-white">D STORE</span>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest px-1.5 py-0.5 rounded bg-white/15 text-white/90">
                            PRO
                        </span>
                    </div>
                </Link>

                <!-- Mobile Close button -->
                <button
                    type="button"
                    @click="isMobileOpen = false"
                    class="lg:hidden p-1.5 rounded-lg text-[#86868B] hover:text-white hover:bg-white/10 cursor-pointer"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Quick Action: Search trigger -->
            <div class="p-3 border-b border-white/5">
                <button
                    type="button"
                    @click="openCommandPalette"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs text-[#86868B] hover:text-white transition-all cursor-pointer group"
                    :class="[isCollapsed ? 'lg:justify-center lg:px-2' : '']"
                    title="Quick Jump (Ctrl+K / ⌘K)"
                >
                    <Search class="w-3.5 h-3.5 text-[#86868B] group-hover:text-white transition-colors" />
                    <span v-if="!isCollapsed || isMobileOpen" class="flex-1 text-left text-[11px] truncate">
                        Quick Jump...
                    </span>
                    <kbd
                        v-if="!isCollapsed || isMobileOpen"
                        class="hidden sm:inline-flex items-center px-1.5 py-0.5 rounded bg-white/10 text-[9px] font-mono text-[#86868B]"
                    >
                        ⌘K
                    </kbd>
                </button>
            </div>

            <!-- Hierarchical Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto custom-scrollbar">
                <div v-for="section in navSections" :key="section.title" class="space-y-1">
                    <!-- Section Title (hidden when collapsed on desktop) -->
                    <div
                        v-if="!isCollapsed || isMobileOpen"
                        class="px-3 pb-1 text-[10px] font-extrabold tracking-wider text-[#86868B] uppercase"
                    >
                        {{ section.title }}
                    </div>
                    <div v-else class="h-2"></div>

                    <!-- Items -->
                    <Link
                        v-for="item in section.items"
                        :key="item.name"
                        :href="item.href"
                        @click="isMobileOpen = false"
                        class="group relative flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150 active:scale-[0.98]"
                        :class="[
                            currentUrl === item.href || (item.href !== '/admin' && currentUrl.startsWith(item.href))
                                ? 'bg-white/15 text-white font-bold shadow-sm ring-1 ring-white/20'
                                : 'text-[#86868B] hover:text-white hover:bg-white/5',
                            isCollapsed ? 'lg:justify-center lg:px-2' : ''
                        ]"
                        :title="isCollapsed ? item.name : undefined"
                    >
                        <component
                            :is="item.icon"
                            class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110"
                            :class="[
                                currentUrl === item.href || (item.href !== '/admin' && currentUrl.startsWith(item.href))
                                    ? 'text-white'
                                    : 'text-[#86868B] group-hover:text-white'
                            ]"
                        />
                        <span v-if="!isCollapsed || isMobileOpen" class="flex-1 truncate">
                            {{ item.name }}
                        </span>

                        <!-- Badge counter (if item has a badge and count > 0) -->
                        <span
                            v-if="item.badgeKey && adminBadges[item.badgeKey] > 0"
                            class="shrink-0 text-[10px] font-black px-1.5 py-0.5 rounded-full"
                            :class="[
                                item.badgeColor || 'bg-amber-500 text-white',
                                isCollapsed && !isMobileOpen ? 'lg:absolute lg:top-1 lg:right-1 lg:w-2 lg:h-2 lg:p-0 lg:rounded-full' : ''
                            ]"
                        >
                            <span v-if="!isCollapsed || isMobileOpen">
                                {{ adminBadges[item.badgeKey] > 99 ? '99+' : adminBadges[item.badgeKey] }}
                            </span>
                        </span>
                    </Link>
                </div>
            </nav>

            <!-- Bottom: Collapse Toggle (Desktop only) -->
            <div class="hidden lg:flex items-center px-4 py-2 border-t border-white/5">
                <button
                    type="button"
                    @click="toggleCollapse"
                    class="w-full flex items-center gap-2.5 py-2 px-2.5 rounded-xl text-[11px] text-[#86868B] hover:text-white hover:bg-white/5 transition-colors cursor-pointer"
                    :class="[isCollapsed ? 'justify-center' : '']"
                    :title="isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
                >
                    <PanelLeftClose v-if="!isCollapsed" class="w-4 h-4 shrink-0" />
                    <PanelLeft v-else class="w-4 h-4 shrink-0" />
                    <span v-if="!isCollapsed" class="font-medium">Collapse View</span>
                </button>
            </div>

            <!-- Staff Footer Profile -->
            <div class="p-3 border-t border-white/10 bg-black/40">
                <div class="flex items-center gap-2.5">
                    <div class="relative shrink-0">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#0071E3] to-[#4338CA] flex items-center justify-center text-white font-black text-xs shadow-sm">
                            {{ userInitials }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-[#111111]" title="Online"></span>
                    </div>

                    <div v-if="!isCollapsed || isMobileOpen" class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-white truncate leading-tight">
                            {{ auth.user?.name || 'Staff' }}
                        </p>
                        <p class="text-[10px] text-[#86868B] truncate leading-tight mt-0.5">
                            {{ userRole }}
                        </p>
                    </div>

                    <!-- Logout button -->
                    <button
                        v-if="!isCollapsed || isMobileOpen"
                        type="button"
                        @click="logout"
                        class="p-1.5 rounded-lg text-[#86868B] hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
                        title="Sign Out"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Top Admin Header Bar -->
            <header class="h-16 bg-white border-b border-[#E5E5EA] px-4 sm:px-8 flex items-center justify-between sticky top-0 z-30 shadow-xs">
                <!-- Left: Hamburger toggle on mobile & Title Slot -->
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        type="button"
                        @click="isMobileOpen = true"
                        class="lg:hidden p-2 rounded-xl text-[#111111] hover:bg-[#F5F5F7] transition-colors cursor-pointer"
                        title="Open Menu"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <div class="min-w-0">
                        <h1 class="text-base sm:text-lg font-black text-[#111111] tracking-tight truncate">
                            <slot name="header">Administrative Portal</slot>
                        </h1>
                    </div>
                </div>

                <!-- Right: Status Indicators & Quick Actions -->
                <div class="flex items-center gap-2 sm:gap-4">
                    <!-- Quick Search Trigger -->
                    <button
                        type="button"
                        @click="openCommandPalette"
                        class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[#F5F5F7] hover:bg-[#EAEAEA] border border-[#E5E5EA] text-xs text-[#86868B] transition-colors cursor-pointer"
                    >
                        <Search class="w-3.5 h-3.5" />
                        <span class="text-[11px] font-medium text-[#555]">Search...</span>
                        <kbd class="px-1.5 py-0.5 rounded bg-white text-[9px] font-mono border border-[#E5E5EA]">⌘K</kbd>
                    </button>

                    <!-- Real-time Exchange Rate Pill -->
                    <div class="hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#F5F5F7] border border-[#E5E5EA] text-[11px] font-semibold text-[#1D1D1F]">
                        <span class="text-[#86868B]">Rate:</span>
                        <span>$1 = ៛{{ exchangeRate.toLocaleString() }}</span>
                    </div>

                    <!-- Live Phnom Penh Clock -->
                    <div class="hidden lg:flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#F5F5F7] border border-[#E5E5EA] text-[11px] font-mono text-[#555]">
                        <Clock class="w-3 h-3 text-[#0071E3]" />
                        <span>{{ currentTime }} ICT</span>
                    </div>

                    <!-- Storefront Live Link Pill -->
                    <Link
                        href="/"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#F5F5F7] hover:bg-[#EAEAEA] border border-[#E5E5EA] text-xs font-semibold text-[#111111] transition-all hover:scale-[1.02] active:scale-95"
                    >
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="hidden sm:inline">Storefront</span>
                        <ExternalLink class="w-3.5 h-3.5 text-[#86868B]" />
                    </Link>

                    <!-- Staff User Dropdown -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="isUserMenuOpen = !isUserMenuOpen"
                            class="flex items-center gap-2 p-1 pl-2 rounded-xl hover:bg-[#F5F5F7] transition-colors cursor-pointer"
                        >
                            <div class="w-7 h-7 rounded-lg bg-[#111111] text-white flex items-center justify-center font-bold text-xs">
                                {{ userInitials }}
                            </div>
                            <ChevronDown class="w-3.5 h-3.5 text-[#86868B]" />
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-if="isUserMenuOpen"
                            @click.away="isUserMenuOpen = false"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-[#E5E5EA] p-2 z-50 animate-in fade-in zoom-in-95 duration-100"
                        >
                            <div class="p-3 border-b border-[#F5F5F7] space-y-0.5">
                                <p class="text-xs font-bold text-[#111111] truncate">{{ auth.user?.name }}</p>
                                <p class="text-[11px] text-[#86868B] truncate">{{ auth.user?.email }}</p>
                                <span class="inline-block mt-1 text-[10px] font-bold bg-[#F5F5F7] text-[#111111] px-2 py-0.5 rounded-md">
                                    {{ userRole }}
                                </span>
                            </div>

                            <div class="py-1 space-y-0.5">
                                <Link
                                    href="/admin/settings"
                                    @click="isUserMenuOpen = false"
                                    class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-[#1D1D1F] hover:bg-[#F5F5F7] transition-colors"
                                >
                                    <Settings class="w-3.5 h-3.5 text-[#86868B]" />
                                    <span>Store Settings</span>
                                </Link>
                                <Link
                                    href="/"
                                    target="_blank"
                                    @click="isUserMenuOpen = false"
                                    class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-[#1D1D1F] hover:bg-[#F5F5F7] transition-colors"
                                >
                                    <ExternalLink class="w-3.5 h-3.5 text-[#86868B]" />
                                    <span>Live Customer Store</span>
                                </Link>
                            </div>

                            <div class="pt-1 border-t border-[#F5F5F7]">
                                <button
                                    type="button"
                                    @click="logout"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                >
                                    <LogOut class="w-3.5 h-3.5 text-rose-600" />
                                    <span>Sign Out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Alert Messages -->
            <div v-if="flash.success || flash.error" class="px-4 sm:px-8 mt-6">
                <div
                    v-if="flash.success"
                    class="p-4 rounded-2xl bg-[#DCFCE7] border border-[#BBF7D0] text-[#166534] text-xs font-semibold flex items-center gap-2 shadow-xs"
                >
                    <Check class="w-4 h-4 text-[#16A34A] shrink-0" />
                    <span>{{ flash.success }}</span>
                </div>
                <div
                    v-if="flash.error"
                    class="p-4 rounded-2xl bg-[#FEE2E2] border border-[#FECACA] text-[#991B1B] text-xs font-semibold flex items-center gap-2 shadow-xs"
                >
                    <X class="w-4 h-4 text-[#DC2626] shrink-0" />
                    <span>{{ flash.error }}</span>
                </div>
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-8">
                <slot />
            </main>
        </div>

        <!-- Global Command Palette Modal (⌘K) -->
        <div
            v-if="isCommandPaletteOpen"
            class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150"
            @click.self="closeCommandPalette"
        >
            <div class="w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-[#E5E5EA] overflow-hidden animate-in zoom-in-95 duration-150">
                <!-- Search Input Bar -->
                <div class="p-4 border-b border-[#F5F5F7] flex items-center gap-3">
                    <Search class="w-5 h-5 text-[#86868B] shrink-0" />
                    <input
                        ref="commandInputRef"
                        v-model="commandQuery"
                        type="text"
                        placeholder="Type a section name or action (e.g. Orders, Products, Restock)..."
                        class="flex-1 bg-transparent text-sm text-[#111111] placeholder-[#86868B] outline-none"
                    />
                    <kbd class="px-2 py-0.5 rounded bg-[#F5F5F7] text-[10px] font-mono text-[#86868B] border border-[#E5E5EA]">
                        ESC
                    </kbd>
                </div>

                <!-- Results List -->
                <div class="max-h-80 overflow-y-auto p-2 custom-scrollbar">
                    <div v-if="filteredCommands.length === 0" class="py-8 text-center text-xs text-[#86868B]">
                        No matching administrative pages found.
                    </div>

                    <div v-else class="space-y-1">
                        <button
                            v-for="(item, idx) in filteredCommands"
                            :key="idx"
                            type="button"
                            @click="navigateTo(item.href)"
                            class="w-full flex items-center justify-between p-3 rounded-2xl hover:bg-[#F5F5F7] transition-colors cursor-pointer text-left group"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-xl bg-[#F5F5F7] group-hover:bg-white text-[#111111] flex items-center justify-center transition-colors">
                                    <component :is="item.icon" class="w-4 h-4" />
                                </div>
                                <div class="truncate">
                                    <p class="text-xs font-bold text-[#111111] truncate">{{ item.name }}</p>
                                    <p class="text-[10px] text-[#86868B]">{{ item.category }}</p>
                                </div>
                            </div>
                            <ArrowRight class="w-3.5 h-3.5 text-[#86868B] group-hover:text-[#111111] transition-transform group-hover:translate-x-1" />
                        </button>
                    </div>
                </div>

                <!-- Footer tip -->
                <div class="p-3 bg-[#FBFBFC] border-t border-[#F5F5F7] flex items-center justify-between text-[11px] text-[#86868B]">
                    <span>Navigation Shortcut</span>
                    <span class="flex items-center gap-1 font-mono text-[10px]">
                        <span>Use</span>
                        <kbd class="px-1 py-0.5 rounded bg-white border border-[#E5E5EA]">Enter</kbd>
                        <span>to jump</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>