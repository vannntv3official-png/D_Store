<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import Textarea from '@/Components/Textarea.vue';
import Dialog from '@/Components/Dialog.vue';
import Badge from '@/Components/Badge.vue';
import { Award, Plus, SlidersHorizontal, Gift, Search, AlertCircle } from 'lucide-vue-next';

interface LoyaltyAccountItem {
    id: number;
    user_id: number;
    customer_name: string;
    customer_phone?: string | null;
    customer_email?: string | null;
    spendable_balance: number;
    current_balance: number;
    lifetime_earned: number;
    points_debt: number;
    updated_at: string;
}

interface RewardItem {
    id: number;
    name_en: string;
    name_km: string;
    points_cost: number;
    discount_type: string;
    discount_value: string;
    min_spend_amount: string;
    is_active: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    accounts: {
        data: LoyaltyAccountItem[];
        links: PaginationLink[];
        total: number;
    };
    rewards: RewardItem[];
    filters: {
        q?: string;
    };
}

const props = defineProps<Props>();

const activeTab = ref<'accounts' | 'rewards'>('accounts');
const search = ref(props.filters.q || '');

// Adjust Points Modal
const showAdjustModal = ref(false);
const activeAccount = ref<LoyaltyAccountItem | null>(null);
const isSubmittingAdjust = ref(false);
const adjustErrors = ref<Record<string, string>>({});
const adjustForm = reactive({
    account_id: 0,
    points: 0,
    reason: '',
});

const openAdjust = (acc: LoyaltyAccountItem) => {
    activeAccount.value = acc;
    adjustForm.account_id = acc.id;
    adjustForm.points = 0;
    adjustForm.reason = '';
    adjustErrors.value = {};
    showAdjustModal.value = true;
};

const submitAdjust = () => {
    isSubmittingAdjust.value = true;
    adjustErrors.value = {};

    router.post('/admin/loyalty/adjust', adjustForm, {
        onSuccess: () => {
            showAdjustModal.value = false;
        },
        onError: (err) => {
            adjustErrors.value = err;
        },
        onFinish: () => {
            isSubmittingAdjust.value = false;
        },
    });
};

// Create Reward Modal
const showRewardModal = ref(false);
const isSubmittingReward = ref(false);
const rewardErrors = ref<Record<string, string>>({});
const rewardForm = reactive({
    name_en: '',
    name_km: '',
    description_en: '',
    description_km: '',
    points_cost: 100,
    discount_type: 'fixed',
    discount_value: '5.00',
    min_spend_amount: '',
    is_active: true,
});

const submitReward = () => {
    isSubmittingReward.value = true;
    rewardErrors.value = {};

    router.post('/admin/loyalty/rewards', rewardForm, {
        onSuccess: () => {
            showRewardModal.value = false;
            rewardForm.name_en = '';
            rewardForm.name_km = '';
            rewardForm.description_en = '';
            rewardForm.description_km = '';
        },
        onError: (err) => {
            rewardErrors.value = err;
        },
        onFinish: () => {
            isSubmittingReward.value = false;
        },
    });
};

const applySearch = () => {
    router.get(
        '/admin/loyalty',
        { q: search.value || undefined },
        { preserveState: true, replace: true }
    );
};
</script>

<template>
    <AdminLayout>
        <Head title="Loyalty & Privilege Program — D STORE" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Privilege & Loyalty Club</h1>
                    <p class="text-xs text-[#86868B] mt-1">Manage customer points balances, reconciliation debt, and redemption rewards catalog.</p>
                </div>
                <Button
                    v-if="activeTab === 'rewards'"
                    variant="primary"
                    size="sm"
                    @click="showRewardModal = true"
                    class="flex items-center gap-1.5"
                >
                    <Plus class="w-3.5 h-3.5" />
                    <span>Create Reward Voucher</span>
                </Button>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center gap-2 border-b border-[#E5E5EA] pb-3">
                <button
                    @click="activeTab = 'accounts'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
                        activeTab === 'accounts'
                            ? 'bg-[#111111] text-white shadow-sm'
                            : 'text-[#86868B] hover:text-[#111111] hover:bg-white'
                    ]"
                >
                    <Award class="w-3.5 h-3.5" />
                    <span>Customer Balances ({{ accounts.total }})</span>
                </button>
                <button
                    @click="activeTab = 'rewards'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
                        activeTab === 'rewards'
                            ? 'bg-[#111111] text-white shadow-sm'
                            : 'text-[#86868B] hover:text-[#111111] hover:bg-white'
                    ]"
                >
                    <Gift class="w-3.5 h-3.5" />
                    <span>Rewards Catalog ({{ rewards.length }})</span>
                </button>
            </div>

            <!-- Customer Balances Tab -->
            <div v-if="activeTab === 'accounts'" class="space-y-4">
                <!-- Search -->
                <div class="bg-white p-4 rounded-2xl border border-[#E5E5EA] shadow-sm flex items-center gap-3">
                    <div class="relative flex-1">
                        <Search class="w-4 h-4 text-[#86868B] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            v-model="search"
                            @keyup.enter="applySearch"
                            placeholder="Search customer name, phone, or email..."
                            class="w-full pl-9 pr-4 py-2 text-xs bg-[#F5F5F7] border border-transparent rounded-xl focus:bg-white focus:border-[#111111] outline-none transition-all"
                        />
                    </div>
                    <Button variant="secondary" size="sm" @click="applySearch">
                        Search
                    </Button>
                </div>

                <!-- Accounts Table -->
                <div class="bg-white rounded-3xl border border-[#E5E5EA] shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[#FBFBFC] border-b border-[#F5F5F7] text-[11px] font-bold uppercase tracking-wider text-[#86868B]">
                                    <th class="py-3.5 px-6">Customer</th>
                                    <th class="py-3.5 px-6 text-center">Spendable Points</th>
                                    <th class="py-3.5 px-6 text-center">Lifetime Earned</th>
                                    <th class="py-3.5 px-6 text-center">Clawback Debt</th>
                                    <th class="py-3.5 px-6">Last Activity</th>
                                    <th class="py-3.5 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#F5F5F7] text-xs">
                                <tr v-if="accounts.data.length === 0">
                                    <td colspan="6" class="py-12 text-center text-[#86868B]">
                                        No customer loyalty records found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="acc in accounts.data"
                                    :key="acc.id"
                                    class="hover:bg-[#FBFBFC] transition-colors"
                                >
                                    <td class="py-4 px-6 font-medium">
                                        <div class="font-bold text-[#111111]">{{ acc.customer_name }}</div>
                                        <div class="text-[11px] text-[#86868B]">{{ acc.customer_phone || acc.customer_email }}</div>
                                    </td>
                                    <td class="py-4 px-6 text-center font-black text-sm text-[#111111]">
                                        {{ acc.spendable_balance.toLocaleString() }}
                                    </td>
                                    <td class="py-4 px-6 text-center text-[#86868B]">
                                        {{ acc.lifetime_earned.toLocaleString() }}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span
                                            v-if="acc.points_debt > 0"
                                            class="inline-flex items-center gap-1 font-bold text-[#DC2626]"
                                        >
                                            <AlertCircle class="w-3.5 h-3.5" />
                                            {{ acc.points_debt }} pts
                                        </span>
                                        <span v-else class="text-[#86868B] font-mono">0</span>
                                    </td>
                                    <td class="py-4 px-6 text-[#86868B]">
                                        {{ acc.updated_at }}
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            @click="openAdjust(acc)"
                                            class="flex items-center gap-1 ml-auto"
                                        >
                                            <SlidersHorizontal class="w-3.5 h-3.5" />
                                            <span>Adjust Points</span>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="accounts.links.length > 3" class="p-4 border-t border-[#F5F5F7] flex items-center justify-between">
                        <p class="text-xs text-[#86868B]">
                            Showing {{ accounts.data.length }} of {{ accounts.total }} members
                        </p>
                        <div class="flex items-center gap-1">
                            <Link
                                v-for="link in accounts.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 rounded-lg text-xs font-semibold transition-colors',
                                    link.active ? 'bg-[#111111] text-white' : 'text-[#86868B] hover:bg-[#F5F5F7]',
                                    !link.url ? 'opacity-40 pointer-events-none' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rewards Catalog Tab -->
            <div v-if="activeTab === 'rewards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-if="rewards.length === 0" class="col-span-full bg-white rounded-3xl p-12 text-center border border-[#E5E5EA]">
                    <p class="text-xs text-[#86868B]">No loyalty rewards configured yet.</p>
                </div>
                <div
                    v-for="rw in rewards"
                    :key="rw.id"
                    class="bg-white rounded-2xl p-6 border border-[#E5E5EA] shadow-sm flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-[#F5F5F7] text-[#111111]">
                                <Award class="w-3.5 h-3.5 text-[#0071E3]" />
                                {{ rw.points_cost }} PTS
                            </span>
                            <Badge :variant="rw.is_active ? 'success' : 'neutral'">
                                {{ rw.is_active ? 'Active' : 'Disabled' }}
                            </Badge>
                        </div>
                        <h3 class="text-sm font-bold text-[#111111] mt-2">{{ rw.name_en }}</h3>
                        <p class="text-xs text-[#86868B] mt-0.5">{{ rw.name_km }}</p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-[#F5F5F7] flex items-center justify-between text-xs">
                        <span class="text-[#86868B]">Benefit: <strong class="text-[#111111]">{{ rw.discount_value }}</strong></span>
                        <span class="text-[#86868B]">Min: <strong class="text-[#111111]">{{ rw.min_spend_amount }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjust Points Modal -->
        <Dialog :open="showAdjustModal" @close="showAdjustModal = false" title="Staff Loyalty Balance Adjustment">
            <div v-if="activeAccount" class="space-y-4">
                <div class="p-3 bg-[#FBFBFC] rounded-xl border border-[#E5E5EA] text-xs">
                    <p class="font-bold text-[#111111]">{{ activeAccount.customer_name }}</p>
                    <p class="text-[#86868B] mt-0.5">Current Spendable: {{ activeAccount.spendable_balance }} pts</p>
                </div>

                <form @submit.prevent="submitAdjust" class="space-y-4">
                    <Input
                        v-model="adjustForm.points"
                        type="number"
                        label="Points Delta (+ to grant bonus, - to deduct)"
                        placeholder="e.g. 50 or -20"
                        :error="adjustErrors.points"
                        required
                    />

                    <Input
                        v-model="adjustForm.reason"
                        label="Staff Authorization Reason"
                        placeholder="e.g. Customer goodwill bonus, Discretionary reward"
                        :error="adjustErrors.reason"
                        required
                    />

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                        <Button type="button" variant="secondary" @click="showAdjustModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" :loading="isSubmittingAdjust">
                            Confirm Adjustment
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog>

        <!-- Create Reward Modal -->
        <Dialog :open="showRewardModal" @close="showRewardModal = false" title="Create Loyalty Reward Voucher">
            <form @submit.prevent="submitReward" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="rewardForm.name_en"
                        label="Reward Name (English)"
                        placeholder="e.g. $10 Store Voucher"
                        :error="rewardErrors.name_en"
                        required
                    />
                    <Input
                        v-model="rewardForm.name_km"
                        label="Reward Name (Khmer / ខ្មែរ)"
                        placeholder="e.g. ប័ណ្ណបញ្ចុះតម្លៃ ១០ ដុល្លារ"
                        :error="rewardErrors.name_km"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="rewardForm.points_cost"
                        type="number"
                        label="Points Cost"
                        placeholder="e.g. 500"
                        :error="rewardErrors.points_cost"
                        required
                    />
                    <Select
                        v-model="rewardForm.discount_type"
                        label="Discount Type"
                        :options="[
                            { value: 'fixed', label: 'Fixed Cash Voucher ($)' },
                            { value: 'percentage', label: 'Percentage Off (%)' },
                            { value: 'free_shipping', label: 'Free Delivery' }
                        ]"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="rewardForm.discount_value"
                        type="number"
                        step="0.01"
                        label="Discount Value"
                        placeholder="10.00"
                        :error="rewardErrors.discount_value"
                        required
                    />
                    <Input
                        v-model="rewardForm.min_spend_amount"
                        type="number"
                        step="0.01"
                        label="Min Spend ($) (Optional)"
                        placeholder="50.00"
                    />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button type="button" variant="secondary" @click="showRewardModal = false">
                        Cancel
                    </Button>
                    <Button type="submit" variant="primary" :loading="isSubmittingReward">
                        Create Reward
                    </Button>
                </div>
            </form>
        </Dialog>
    </AdminLayout>
</template>
