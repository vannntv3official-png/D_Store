<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import AccountNav from '@/Components/AccountNav.vue';
import Button from '@/Components/Button.vue';
import Badge from '@/Components/Badge.vue';
import { useTrans } from '@/Composables/useTrans';
import { Award, Gift, ArrowUpRight, ArrowDownLeft, AlertCircle, Sparkles, CheckCircle2 } from 'lucide-vue-next';

interface Transaction {
    id: number;
    points: number;
    type: string;
    description: string;
    created_at: string;
}

interface Reward {
    id: number;
    name_en: string;
    name_km: string;
    description_en?: string;
    description_km?: string;
    points_required: number;
    reward_type: string;
    reward_value: string;
}

interface Props {
    balance: number;
    currentBalance: number;
    lifetimeEarned: number;
    pointsDebt: number;
    transactions: Transaction[];
    rewards: Reward[];
}

const props = defineProps<Props>();
const { t, currentLocale } = useTrans();

const redeemingId = ref<number | null>(null);

const redeem = (rewardId: number) => {
    if (confirm('Are you sure you want to redeem this reward with your loyalty points?')) {
        redeemingId.value = rewardId;
        router.post(`/account/loyalty/redeem/${rewardId}`, {}, {
            onFinish: () => {
                redeemingId.value = null;
            },
        });
    }
};
</script>

<template>
    <CustomerLayout>
        <Head title="Loyalty & Rewards — D STORE" />

        <div class="max-w-5xl mx-auto px-4 sm:px-8 py-12">
            <div class="mb-4">
                <h1 class="text-3xl font-black text-[#111111] tracking-tight">
                    {{ t('account.loyalty_points') }}
                </h1>
                <p class="text-xs text-[#86868B] mt-1">Earn points on every purchase and unlock exclusive member privileges.</p>
            </div>

            <AccountNav />

            <!-- Points Debt Warning if applicable -->
            <div
                v-if="pointsDebt > 0"
                class="mb-8 p-4 rounded-2xl bg-[#FFF5F5] border border-[#DC2626]/20 flex items-start gap-3"
            >
                <AlertCircle class="w-5 h-5 text-[#DC2626] shrink-0 mt-0.5" />
                <div>
                    <h4 class="text-xs font-bold text-[#DC2626] uppercase tracking-wider">Points Reconciliation Notice</h4>
                    <p class="text-xs text-[#1D1D1F] mt-1">
                        You have a temporary points deficit of <strong>{{ pointsDebt }} points</strong> due to a refunded order. Points earned from your next purchases will automatically reconcile this balance.
                    </p>
                </div>
            </div>

            <!-- Balance Hero Card -->
            <div class="bg-gradient-to-br from-[#111111] to-[#222222] text-white rounded-3xl p-8 sm:p-10 shadow-xl mb-12 relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-white text-[11px] font-bold uppercase tracking-wider">
                            <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                            <span>D STORE Privilege Member</span>
                        </div>
                        <p class="text-xs text-[#86868B] uppercase tracking-wider">Available Spendable Points</p>
                        <div class="flex items-baseline gap-3">
                            <h2 class="text-5xl sm:text-6xl font-black tracking-tight text-white">
                                {{ balance.toLocaleString() }}
                            </h2>
                            <span class="text-lg font-bold text-[#86868B]">PTS</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t md:border-t-0 md:border-l border-white/10 pt-4 md:pt-0 md:pl-8">
                        <div>
                            <p class="text-[11px] text-[#86868B] uppercase tracking-wider">Lifetime Earned</p>
                            <p class="text-xl font-bold text-white mt-1">{{ lifetimeEarned.toLocaleString() }} pts</p>
                        </div>
                        <div>
                            <p class="text-[11px] text-[#86868B] uppercase tracking-wider">Redemption Value</p>
                            <p class="text-xl font-bold text-emerald-400 mt-1">${{ (balance * 0.01).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rewards Store Section -->
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-black text-[#111111] tracking-tight">Available Rewards</h2>
                        <p class="text-xs text-[#86868B]">Exchange your accumulated points for vouchers and store credit.</p>
                    </div>
                </div>

                <div v-if="rewards.length === 0" class="bg-white rounded-2xl p-8 text-center border border-[#E5E5EA]">
                    <p class="text-xs text-[#86868B]">No reward vouchers currently active. Check back soon!</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="reward in rewards"
                        :key="reward.id"
                        class="bg-white rounded-2xl p-6 border border-[#E5E5EA] shadow-sm flex flex-col justify-between hover:border-[#111111] transition-all"
                    >
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-[#F5F5F7] text-[#111111]">
                                    <Award class="w-3.5 h-3.5 text-[#0071E3]" />
                                    {{ reward.points_required }} Points
                                </span>
                                <Badge variant="neutral">{{ reward.reward_type }}</Badge>
                            </div>

                            <h3 class="text-sm font-bold text-[#111111] mb-1">
                                {{ currentLocale === 'km' ? reward.name_km : reward.name_en }}
                            </h3>
                            <p class="text-xs text-[#86868B] leading-relaxed">
                                {{ currentLocale === 'km' ? (reward.description_km || reward.description_en) : reward.description_en }}
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-[#F5F5F7] flex items-center justify-between">
                            <span class="text-xs font-bold text-[#111111]">{{ reward.reward_value }}</span>
                            <Button
                                size="sm"
                                :variant="balance >= reward.points_required ? 'primary' : 'secondary'"
                                :disabled="balance < reward.points_required"
                                :loading="redeemingId === reward.id"
                                @click="redeem(reward.id)"
                            >
                                {{ balance >= reward.points_required ? 'Redeem Voucher' : 'Need More Points' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Points Ledger History -->
            <div>
                <h2 class="text-xl font-black text-[#111111] tracking-tight mb-4">Points History</h2>

                <div v-if="transactions.length === 0" class="bg-white rounded-2xl p-8 text-center border border-[#E5E5EA]">
                    <p class="text-xs text-[#86868B]">No points activity recorded yet.</p>
                </div>

                <div v-else class="bg-white rounded-2xl border border-[#E5E5EA] divide-y divide-[#F5F5F7] overflow-hidden shadow-sm">
                    <div
                        v-for="item in transactions"
                        :key="item.id"
                        class="p-4 sm:p-5 flex items-center justify-between hover:bg-[#FBFBFC] transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'w-9 h-9 rounded-full flex items-center justify-center shrink-0',
                                    item.points > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'
                                ]"
                            >
                                <ArrowUpRight v-if="item.points > 0" class="w-4 h-4" />
                                <ArrowDownLeft v-else class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-[#111111]">{{ item.description }}</p>
                                <p class="text-[11px] text-[#86868B]">{{ item.created_at }} • {{ item.type }}</p>
                            </div>
                        </div>

                        <span
                            :class="[
                                'text-xs font-bold',
                                item.points > 0 ? 'text-emerald-600' : 'text-rose-600'
                            ]"
                        >
                            {{ item.points > 0 ? `+${item.points}` : item.points }} pts
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
