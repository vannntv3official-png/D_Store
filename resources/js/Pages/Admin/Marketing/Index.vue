<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import Dialog from '@/Components/Dialog.vue';
import Badge from '@/Components/Badge.vue';
import { Plus, Tag, Image, Trash2, Power, Zap, Pencil, ExternalLink, Eye } from 'lucide-vue-next';

interface CouponItem {
    id: number;
    code: string;
    type: string;
    value: string;
    min_spend: string;
    usage_count: number;
    usage_limit: string | number;
    is_active: boolean;
    expires_at: string;
}

interface BannerItem {
    id: number;
    title_en: string;
    title_km: string;
    subtitle_en?: string;
    subtitle_km?: string;
    link_url: string;
    image_url: string;
    display_order: number;
    is_active: boolean;
}

interface Props {
    coupons: CouponItem[];
    banners: BannerItem[];
    flashSales: any[];
}

defineProps<Props>();

const activeTab = ref<'coupons' | 'banners' | 'flash'>('coupons');

// Coupon Modal
const showCouponModal = ref(false);
const isSubmittingCoupon = ref(false);
const couponErrors = ref<Record<string, string>>({});
const couponForm = reactive({
    code: '',
    discount_type: 'percentage',
    discount_value: '',
    min_spend: '',
    usage_limit: '',
    expires_at: '',
    is_active: true,
});

const submitCoupon = () => {
    isSubmittingCoupon.value = true;
    couponErrors.value = {};

    router.post('/admin/marketing/coupons', couponForm, {
        onSuccess: () => {
            showCouponModal.value = false;
            couponForm.code = '';
            couponForm.discount_value = '';
            couponForm.min_spend = '';
            couponForm.usage_limit = '';
            couponForm.expires_at = '';
        },
        onError: (err) => {
            couponErrors.value = err;
        },
        onFinish: () => {
            isSubmittingCoupon.value = false;
        },
    });
};

const toggleCoupon = (id: number) => {
    router.post(`/admin/marketing/coupons/${id}/toggle`);
};

// Banner Modal
const showBannerModal = ref(false);
const isSubmittingBanner = ref(false);
const bannerErrors = ref<Record<string, string>>({});
interface BannerFormData {
    title_en: string;
    title_km: string;
    subtitle_en: string;
    subtitle_km: string;
    link_url: string;
    image_url: string;
    display_order: number;
    is_active: boolean;
}

const bannerForm = ref<BannerFormData>({
    title_en: '',
    title_km: '',
    subtitle_en: '',
    subtitle_km: '',
    link_url: '/shop',
    image_url: '',
    display_order: 1,
    is_active: true,
});

// Edit Banner Modal
const showEditBannerModal = ref(false);
const editingBannerId = ref<number | null>(null);
const isSubmittingEditBanner = ref(false);
const editBannerErrors = ref<Record<string, string>>({});

const editBannerForm = ref<BannerFormData>({
    title_en: '',
    title_km: '',
    subtitle_en: '',
    subtitle_km: '',
    link_url: '/shop',
    image_url: '',
    display_order: 1,
    is_active: true,
});

const openEditBanner = (b: BannerItem) => {
    editingBannerId.value = b.id;
    editBannerForm.value = {
        title_en: b.title_en,
        title_km: b.title_km,
        subtitle_en: b.subtitle_en || '',
        subtitle_km: b.subtitle_km || '',
        link_url: b.link_url,
        image_url: b.image_url,
        display_order: b.display_order,
        is_active: b.is_active,
    };
    editBannerErrors.value = {};
    showEditBannerModal.value = true;
};

const submitBanner = () => {
    isSubmittingBanner.value = true;
    bannerErrors.value = {};

    router.post('/admin/marketing/banners', bannerForm.value, {
        onSuccess: () => {
            showBannerModal.value = false;
            bannerForm.value.title_en = '';
            bannerForm.value.title_km = '';
            bannerForm.value.subtitle_en = '';
            bannerForm.value.subtitle_km = '';
            bannerForm.value.image_url = '';
        },
        onError: (err) => {
            bannerErrors.value = err;
        },
        onFinish: () => {
            isSubmittingBanner.value = false;
        },
    });
};

const submitEditBanner = () => {
    if (!editingBannerId.value) return;
    isSubmittingEditBanner.value = true;
    editBannerErrors.value = {};

    router.put(`/admin/marketing/banners/${editingBannerId.value}`, editBannerForm.value, {
        onSuccess: () => {
            showEditBannerModal.value = false;
            editingBannerId.value = null;
        },
        onError: (err) => {
            editBannerErrors.value = err;
        },
        onFinish: () => {
            isSubmittingEditBanner.value = false;
        },
    });
};

const toggleBanner = (id: number) => {
    router.post(`/admin/marketing/banners/${id}/toggle`);
};

const deleteBanner = (id: number) => {
    if (confirm('Delete this showcase banner?')) {
        router.delete(`/admin/marketing/banners/${id}`);
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Marketing & Campaigns — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Marketing, Coupons & Banners</h1>
                    <p class="text-xs text-[#86868B] mt-1">Promotional discounts, homepage hero showcases, and flash sale event configuration.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        v-if="activeTab === 'coupons'"
                        variant="primary"
                        size="sm"
                        @click="showCouponModal = true"
                        class="flex items-center gap-1.5"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>Create Coupon</span>
                    </Button>
                    <Button
                        v-if="activeTab === 'banners'"
                        variant="primary"
                        size="sm"
                        @click="showBannerModal = true"
                        class="flex items-center gap-1.5"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>Add Showcase Banner</span>
                    </Button>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center gap-2 border-b border-[#E5E5EA] pb-3">
                <button
                    @click="activeTab = 'coupons'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
                        activeTab === 'coupons'
                            ? 'bg-[#111111] text-white shadow-sm'
                            : 'text-[#86868B] hover:text-[#111111] hover:bg-white'
                    ]"
                >
                    <Tag class="w-3.5 h-3.5" />
                    <span>Promo Coupons ({{ coupons.length }})</span>
                </button>
                <button
                    @click="activeTab = 'banners'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
                        activeTab === 'banners'
                            ? 'bg-[#111111] text-white shadow-sm'
                            : 'text-[#86868B] hover:text-[#111111] hover:bg-white'
                    ]"
                >
                    <Image class="w-3.5 h-3.5" />
                    <span>Showcase Banners ({{ banners.length }})</span>
                </button>
                <button
                    @click="activeTab = 'flash'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
                        activeTab === 'flash'
                            ? 'bg-[#111111] text-white shadow-sm'
                            : 'text-[#86868B] hover:text-[#111111] hover:bg-white'
                    ]"
                >
                    <Zap class="w-3.5 h-3.5" />
                    <span>Flash Sales ({{ flashSales.length }})</span>
                </button>
            </div>

            <!-- Coupons Tab -->
            <div v-if="activeTab === 'coupons'" class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                <th class="py-3.5 px-6">Coupon Code</th>
                                <th class="py-3.5 px-6">Discount Value</th>
                                <th class="py-3.5 px-6">Min Spend</th>
                                <th class="py-3.5 px-6">Redemptions</th>
                                <th class="py-3.5 px-6">Expiry</th>
                                <th class="py-3.5 px-6">Status</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F5F5F7] text-xs">
                            <tr v-if="coupons.length === 0">
                                <td colspan="7" class="py-12 text-center text-[#86868B]">
                                    No promotional coupons configured yet.
                                </td>
                            </tr>
                            <tr
                                v-for="cp in coupons"
                                :key="cp.id"
                                class="hover:bg-[#FBFBFC] transition-colors"
                            >
                                <td class="py-4 px-6 font-mono font-bold text-[#111111]">
                                    {{ cp.code }}
                                </td>
                                <td class="py-4 px-6 font-bold text-emerald-600">
                                    {{ cp.value }}
                                </td>
                                <td class="py-4 px-6 text-[#1D1D1F]">
                                    {{ cp.min_spend }}
                                </td>
                                <td class="py-4 px-6 text-[#86868B]">
                                    {{ cp.usage_count }} / {{ cp.usage_limit }}
                                </td>
                                <td class="py-4 px-6 text-[#86868B]">
                                    {{ cp.expires_at }}
                                </td>
                                <td class="py-4 px-6">
                                    <Badge :variant="cp.is_active ? 'success' : 'neutral'">
                                        {{ cp.is_active ? 'Active' : 'Disabled' }}
                                    </Badge>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        @click="toggleCoupon(cp.id)"
                                        class="text-xs"
                                    >
                                        <Power class="w-3.5 h-3.5 mr-1" />
                                        <span>{{ cp.is_active ? 'Deactivate' : 'Activate' }}</span>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Banners Tab -->
            <div v-if="activeTab === 'banners'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-if="banners.length === 0" class="col-span-full bg-white rounded-3xl p-12 text-center border border-[#E5E5EA]">
                    <p class="text-xs text-[#86868B]">No hero showcase banners created yet.</p>
                </div>
                <div
                    v-for="b in banners"
                    :key="b.id"
                    class="bg-white rounded-3xl border border-[#E5E5EA] overflow-hidden shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow"
                >
                    <div class="aspect-[16/9] bg-[#0A0A0C] relative overflow-hidden group">
                        <img 
                            :src="b.image_url" 
                            :alt="b.title_en" 
                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                            @error="(e: any) => e.target.style.display = 'none'" 
                        />
                        
                        <!-- Status Badge -->
                        <span
                            :class="[
                                'absolute top-3 left-3 px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-xs',
                                b.is_active ? 'bg-emerald-600 text-white' : 'bg-[#111111]/80 backdrop-blur-md text-white'
                            ]"
                        >
                            {{ b.is_active ? 'ACTIVE' : 'DRAFT' }}
                        </span>

                        <!-- Order Badge -->
                        <span class="absolute top-3 right-3 px-2 py-0.5 rounded-md bg-black/60 backdrop-blur-md text-[10px] font-mono font-bold text-white border border-white/10">
                            Order: #{{ b.display_order }}
                        </span>

                        <!-- Overlay Text Preview -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                            <a :href="b.link_url" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white text-[#111111] text-xs font-bold hover:bg-[#F5F5F7] shadow-sm">
                                <span>Preview Link</span>
                                <ExternalLink class="w-3.5 h-3.5" />
                            </a>
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-sm font-bold text-[#111111]">{{ b.title_en }}</h3>
                                <span class="text-xs font-bold text-[#86868B]">#{{ b.id }}</span>
                            </div>
                            <p class="text-xs text-[#86868B] mt-0.5 font-khmer">{{ b.title_km }}</p>
                            <p v-if="b.subtitle_en" class="text-xs text-[#1D1D1F] mt-2 line-clamp-2">{{ b.subtitle_en }}</p>
                            <p v-if="b.subtitle_km" class="text-xs text-[#86868B] font-khmer line-clamp-2 mt-0.5">{{ b.subtitle_km }}</p>
                        </div>

                        <div class="pt-3 border-t border-[#F5F5F7] flex items-center justify-between gap-2">
                            <!-- Toggle Active Button -->
                            <button
                                type="button"
                                @click="toggleBanner(b.id)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
                                :class="b.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                <Power class="w-3.5 h-3.5" />
                                <span>{{ b.is_active ? 'Active' : 'Draft' }}</span>
                            </button>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="openEditBanner(b)"
                                    class="p-1.5 rounded-lg text-[#86868B] hover:text-[#111111] hover:bg-[#F5F5F7] transition-colors cursor-pointer"
                                    title="Edit banner"
                                >
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="deleteBanner(b.id)"
                                    class="p-1.5 rounded-lg text-[#86868B] hover:text-[#DC2626] hover:bg-[#FEE2E2] transition-colors cursor-pointer"
                                    title="Delete banner"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flash Sales Tab -->
            <div v-if="activeTab === 'flash'" class="bg-white rounded-3xl p-8 border border-[#E5E5EA] shadow-sm text-center">
                <Zap class="w-10 h-10 text-amber-500 mx-auto mb-3" />
                <h3 class="text-sm font-bold text-[#111111]">Automated Flash Sales</h3>
                <p class="text-xs text-[#86868B] mt-1 max-w-md mx-auto">
                    Time-limited flash campaigns automatically apply promotional prices on checkout and revert when the timer elapses.
                </p>
                <div v-if="flashSales.length > 0" class="mt-6 space-y-3 max-w-md mx-auto text-left">
                    <div v-for="fs in flashSales" :key="fs.id" class="p-3 bg-[#FBFBFC] rounded-xl border border-[#E5E5EA] text-xs">
                        <p class="font-bold text-[#111111]">{{ fs.title }}</p>
                        <p class="text-[11px] text-[#86868B]">Ends: {{ fs.ends_at }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Coupon Modal -->
        <Dialog :open="showCouponModal" @close="showCouponModal = false" title="Create Promotional Coupon">
            <form @submit.prevent="submitCoupon" class="space-y-4">
                <Input
                    v-model="couponForm.code"
                    label="Promo Code (uppercase)"
                    placeholder="e.g. LUXURY2026"
                    :error="couponErrors.code"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <Select
                        v-model="couponForm.discount_type"
                        label="Discount Type"
                        :options="[
                            { value: 'percentage', label: 'Percentage (%)' },
                            { value: 'fixed', label: 'Fixed Amount ($)' }
                        ]"
                        required
                    />
                    <Input
                        v-model="couponForm.discount_value"
                        type="number"
                        step="0.01"
                        label="Discount Value"
                        placeholder="e.g. 15 or 25.00"
                        :error="couponErrors.discount_value"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input
                        v-model="couponForm.min_spend"
                        type="number"
                        step="0.01"
                        label="Min Spend ($)"
                        placeholder="e.g. 50.00"
                    />
                    <Input
                        v-model="couponForm.usage_limit"
                        type="number"
                        label="Max Usages (Optional)"
                        placeholder="e.g. 100"
                    />
                </div>

                <Input
                    v-model="couponForm.expires_at"
                    type="date"
                    label="Expiration Date (Optional)"
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button type="button" variant="secondary" @click="showCouponModal = false">
                        Cancel
                    </Button>
                    <Button type="submit" variant="primary" :loading="isSubmittingCoupon">
                        Publish Coupon
                    </Button>
                </div>
            </form>
        </Dialog>

        <!-- Create Banner Modal -->
        <Dialog :open="showBannerModal" @close="showBannerModal = false" title="Add Hero Showcase Banner">
            <form @submit.prevent="submitBanner" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="bannerForm.title_en"
                        label="Title (English)"
                        placeholder="e.g. The New Titanium Era"
                        :error="bannerErrors.title_en"
                        required
                    />
                    <Input
                        v-model="bannerForm.title_km"
                        label="Title (Khmer / ខ្មែរ)"
                        placeholder="e.g. យុគសម័យថ្មីនៃទីតានីញ៉ូម"
                        :error="bannerErrors.title_km"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="bannerForm.subtitle_en"
                        label="Subtitle (English)"
                        placeholder="e.g. Precision forged for extraordinary strength"
                    />
                    <Input
                        v-model="bannerForm.subtitle_km"
                        label="Subtitle (Khmer)"
                        placeholder="e.g. ភាពរឹងមាំ និងប្រណិតភាព"
                    />
                </div>

                <Input
                    v-model="bannerForm.image_url"
                    label="Image URL"
                    placeholder="https://... or /images/banner.jpg"
                    :error="bannerErrors.image_url"
                    required
                />

                <!-- Live Image Preview -->
                <div v-if="bannerForm.image_url" class="rounded-2xl overflow-hidden aspect-[16/9] bg-[#0A0A0C] relative border border-[#E5E5EA]">
                    <img :src="bannerForm.image_url" class="w-full h-full object-cover" @error="(e: any) => e.target.style.display = 'none'" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-4 flex flex-col justify-end text-white">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white/70">Live Banner Preview</span>
                        <h4 class="text-sm font-bold text-white">{{ bannerForm.title_en || 'Banner Headline Preview' }}</h4>
                        <p class="text-xs text-white/80 font-khmer">{{ bannerForm.title_km }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input
                        v-model="bannerForm.link_url"
                        label="Target Link Destination"
                        placeholder="/shop"
                        required
                    />
                    <Input
                        v-model="bannerForm.display_order"
                        type="number"
                        label="Sequence Order"
                        placeholder="1"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button type="button" variant="secondary" @click="showBannerModal = false">
                        Cancel
                    </Button>
                    <Button type="submit" variant="primary" :loading="isSubmittingBanner">
                        Save Banner
                    </Button>
                </div>
            </form>
        </Dialog>

        <!-- Edit Banner Modal -->
        <Dialog :open="showEditBannerModal" @close="showEditBannerModal = false" title="Edit Showcase Banner">
            <form @submit.prevent="submitEditBanner" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="editBannerForm.title_en"
                        label="Title (English)"
                        placeholder="e.g. The New Titanium Era"
                        :error="editBannerErrors.title_en"
                        required
                    />
                    <Input
                        v-model="editBannerForm.title_km"
                        label="Title (Khmer / ខ្មែរ)"
                        placeholder="e.g. យុគសម័យថ្មីនៃទីតានីញ៉ូម"
                        :error="editBannerErrors.title_km"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="editBannerForm.subtitle_en"
                        label="Subtitle (English)"
                        placeholder="e.g. Precision forged for extraordinary strength"
                    />
                    <Input
                        v-model="editBannerForm.subtitle_km"
                        label="Subtitle (Khmer)"
                        placeholder="e.g. ភាពរឹងមាំ និងប្រណិតភាព"
                    />
                </div>

                <Input
                    v-model="editBannerForm.image_url"
                    label="Image URL"
                    placeholder="https://... or /images/banner.jpg"
                    :error="editBannerErrors.image_url"
                    required
                />

                <!-- Image Live Preview -->
                <div v-if="editBannerForm.image_url" class="rounded-2xl overflow-hidden aspect-[16/9] bg-[#0A0A0C] relative border border-[#E5E5EA]">
                    <img :src="editBannerForm.image_url" class="w-full h-full object-cover" @error="(e: any) => e.target.style.display = 'none'" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-4 flex flex-col justify-end text-white">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white/70">Live Banner Preview</span>
                        <h4 class="text-sm font-bold text-white">{{ editBannerForm.title_en || 'Banner Headline Preview' }}</h4>
                        <p class="text-xs text-white/80 font-khmer">{{ editBannerForm.title_km }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <Input
                        v-model="editBannerForm.link_url"
                        label="Target Link Destination"
                        placeholder="/shop"
                        required
                    />
                    <Input
                        v-model="editBannerForm.display_order"
                        type="number"
                        label="Sequence Order"
                        placeholder="1"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button type="button" variant="secondary" @click="showEditBannerModal = false">
                        Cancel
                    </Button>
                    <Button type="submit" variant="primary" :loading="isSubmittingEditBanner">
                        Update Banner
                    </Button>
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
