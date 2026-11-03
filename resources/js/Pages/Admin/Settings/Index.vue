<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Textarea from '@/Components/Textarea.vue';
import { Save, Building, DollarSign, CreditCard, Landmark, Check } from 'lucide-vue-next';

interface StoreSettings {
    store_name: string;
    store_phone: string;
    store_email: string;
    store_address_en: string;
    store_address_km: string;
    exchange_rate_khr_per_usd: number;
    bank_name: string;
    bank_account_name: string;
    bank_account_usd: string;
    bank_account_khr: string;
    payment_cod_enabled: boolean;
    payment_bank_enabled: boolean;
    payment_khqr_enabled: boolean;
    payment_payway_enabled: boolean;
}

interface Props {
    settings: StoreSettings;
}

const props = defineProps<Props>();

const form = reactive({ ...props.settings });
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const submit = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.put('/admin/settings', form, {
        onError: (err) => {
            errors.value = err;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Store Settings — D STORE" />

        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">Store Configuration</h1>
                    <p class="text-xs text-[#86868B] mt-0.5">Manage exchange rate, boutique contact details, and settlement gateways.</p>
                </div>
                <Button variant="primary" @click="submit" :loading="isSubmitting" class="flex items-center gap-2">
                    <Save class="w-4 h-4" />
                    <span>Save Settings</span>
                </Button>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Currency & Exchange Rate -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                        <DollarSign class="w-4 h-4 text-[#0071E3]" />
                        <span>Currency & Exchange Rate (NBC Pegged)</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-end">
                        <Input
                            v-model="form.exchange_rate_khr_per_usd"
                            type="number"
                            label="Exchange Rate (KHR per 1 USD)"
                            placeholder="4100"
                            :error="errors.exchange_rate_khr_per_usd"
                            required
                        />
                        <div class="p-3 bg-[#FBFBFC] rounded-2xl border border-[#E5E5EA] text-xs">
                            <span class="text-[#86868B]">Preview Calculation:</span>
                            <p class="font-bold text-[#111111] mt-0.5">$100.00 USD = ៛{{ (100 * form.exchange_rate_khr_per_usd).toLocaleString() }} KHR</p>
                            <p class="text-[10px] text-[#86868B] mt-0.5">Physical cash rounded to nearest 100 Riels</p>
                        </div>
                    </div>
                </div>

                <!-- Boutique Profile -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                        <Building class="w-4 h-4 text-[#0071E3]" />
                        <span>Store Identity & Showroom Contact</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <Input
                            v-model="form.store_name"
                            label="Store Legal Brand Name"
                            :error="errors.store_name"
                            required
                        />
                        <Input
                            v-model="form.store_phone"
                            label="Customer Service Hotline / Telegram"
                            :error="errors.store_phone"
                            required
                        />
                    </div>

                    <Input
                        v-model="form.store_email"
                        type="email"
                        label="Store Advisory Email"
                        :error="errors.store_email"
                        required
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <Textarea
                            v-model="form.store_address_en"
                            label="Showroom Address (English)"
                            rows="3"
                            :error="errors.store_address_en"
                            required
                        />
                        <Textarea
                            v-model="form.store_address_km"
                            label="Showroom Address (Khmer / ខ្មែរ)"
                            rows="3"
                            :error="errors.store_address_km"
                            required
                        />
                    </div>
                </div>

                <!-- Bank Accounts for Wire Transfer -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                        <Landmark class="w-4 h-4 text-[#0071E3]" />
                        <span>Commercial Bank Settlement Details</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <Input
                            v-model="form.bank_name"
                            label="Bank Name"
                            placeholder="e.g. ABA Bank Cambodia"
                            :error="errors.bank_name"
                            required
                        />
                        <Input
                            v-model="form.bank_account_name"
                            label="Account Holder Name"
                            placeholder="e.g. D STORE CO., LTD."
                            :error="errors.bank_account_name"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <Input
                            v-model="form.bank_account_usd"
                            label="USD Account Number"
                            placeholder="001 234 567"
                            :error="errors.bank_account_usd"
                            required
                        />
                        <Input
                            v-model="form.bank_account_khr"
                            label="KHR Account Number"
                            placeholder="001 234 568"
                            :error="errors.bank_account_khr"
                            required
                        />
                    </div>
                </div>

                <!-- Payment Gateways -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#E5E5EA] shadow-sm space-y-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                        <CreditCard class="w-4 h-4 text-[#0071E3]" />
                        <span>Enabled Payment Adapters</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="p-4 rounded-2xl border border-[#E5E5EA] hover:border-[#111111] transition-colors flex items-center justify-between cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-[#111111] block">Bakong KHQR (NBC)</span>
                                <span class="text-[11px] text-[#86868B]">Dynamic EMVCo QR code instant payment</span>
                            </div>
                            <input
                                type="checkbox"
                                v-model="form.payment_khqr_enabled"
                                class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                            />
                        </label>

                        <label class="p-4 rounded-2xl border border-[#E5E5EA] hover:border-[#111111] transition-colors flex items-center justify-between cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-[#111111] block">ABA PayWay</span>
                                <span class="text-[11px] text-[#86868B]">Credit cards, Visa, Mastercard, ABA mobile</span>
                            </div>
                            <input
                                type="checkbox"
                                v-model="form.payment_payway_enabled"
                                class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                            />
                        </label>

                        <label class="p-4 rounded-2xl border border-[#E5E5EA] hover:border-[#111111] transition-colors flex items-center justify-between cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-[#111111] block">Cash on Delivery (COD)</span>
                                <span class="text-[11px] text-[#86868B]">Courier collection in Phnom Penh & provinces</span>
                            </div>
                            <input
                                type="checkbox"
                                v-model="form.payment_cod_enabled"
                                class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                            />
                        </label>

                        <label class="p-4 rounded-2xl border border-[#E5E5EA] hover:border-[#111111] transition-colors flex items-center justify-between cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-[#111111] block">Direct Bank Transfer</span>
                                <span class="text-[11px] text-[#86868B]">Manual transfer verification with invoice</span>
                            </div>
                            <input
                                type="checkbox"
                                v-model="form.payment_bank_enabled"
                                class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                            />
                        </label>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <Button type="submit" variant="primary" :loading="isSubmitting" class="flex items-center gap-2">
                        <Save class="w-4 h-4" />
                        <span>Save All Settings</span>
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
