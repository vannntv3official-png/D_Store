<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import AccountNav from '@/Components/AccountNav.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Select from '@/Components/Select.vue';
import Dialog from '@/Components/Dialog.vue';
import { useTrans } from '@/Composables/useTrans';
import { MapPin, Plus, Trash2, CheckCircle2 } from 'lucide-vue-next';

interface Address {
    id: number;
    recipient_name: string;
    phone: string;
    province: string;
    district: string;
    commune?: string | null;
    street_address: string;
    is_default: boolean;
}

interface Props {
    addresses: Address[];
}

defineProps<Props>();
const { t } = useTrans();

const showAddModal = ref(false);
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const provinces = [
    { value: 'Phnom Penh', label: 'Phnom Penh (រាជធានីភ្នំពេញ)' },
    { value: 'Kandal', label: 'Kandal (ខេត្តកណ្តាល)' },
    { value: 'Siem Reap', label: 'Siem Reap (ខេត្តសៀមរាប)' },
    { value: 'Battambang', label: 'Battambang (ខេត្តបាត់ដំបង)' },
    { value: 'Preah Sihanouk', label: 'Preah Sihanouk (ខេត្តព្រះសីហនុ)' },
    { value: 'Kampong Cham', label: 'Kampong Cham (ខេត្តកំពង់ចាម)' },
    { value: 'Kampong Chhnang', label: 'Kampong Chhnang (ខេត្តកំពង់ឆ្នាំង)' },
    { value: 'Kampong Speu', label: 'Kampong Speu (ខេត្តកំពង់ស្ពឺ)' },
    { value: 'Kampong Thom', label: 'Kampong Thom (ខេត្តកំពង់ធំ)' },
    { value: 'Kampot', label: 'Kampot (ខេត្តកំពត)' },
    { value: 'Kep', label: 'Kep (ខេត្តកែប)' },
    { value: 'Koh Kong', label: 'Koh Kong (ខេត្តកោះកុង)' },
    { value: 'Kratie', label: 'Kratie (ខេត្តក្រចេះ)' },
    { value: 'Mondulkiri', label: 'Mondulkiri (ខេត្តមណ្ឌលគិរី)' },
    { value: 'Oddar Meanchey', label: 'Oddar Meanchey (ខេត្តឧត្តរមានជ័យ)' },
    { value: 'Pailin', label: 'Pailin (ខេត្តប៉ៃលិន)' },
    { value: 'Preah Vihear', label: 'Preah Vihear (ខេត្តព្រះវិហារ)' },
    { value: 'Prey Veng', label: 'Prey Veng (ខេត្តព្រៃវែង)' },
    { value: 'Pursat', label: 'Pursat (ខេត្តពោធិ៍សាត់)' },
    { value: 'Ratanakiri', label: 'Ratanakiri (ខេត្តរតនគិរី)' },
    { value: 'Stung Treng', label: 'Stung Treng (ខេត្តស្ទឹងត្រែង)' },
    { value: 'Svay Rieng', label: 'Svay Rieng (ខេត្តស្វាយរៀង)' },
    { value: 'Takeo', label: 'Takeo (ខេត្តតាកែវ)' },
    { value: 'Tboung Khmum', label: 'Tboung Khmum (ខេត្តត្បូងឃ្មុំ)' },
];

const form = reactive({
    recipient_name: '',
    phone: '',
    province: 'Phnom Penh',
    district: '',
    commune: '',
    street_address: '',
    is_default: false,
});

const resetForm = () => {
    form.recipient_name = '';
    form.phone = '';
    form.province = 'Phnom Penh';
    form.district = '';
    form.commune = '';
    form.street_address = '';
    form.is_default = false;
    errors.value = {};
};

const submitAddress = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.post('/account/addresses', form, {
        onSuccess: () => {
            showAddModal.value = false;
            resetForm();
        },
        onError: (err) => {
            errors.value = err;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const deleteAddress = (id: number) => {
    if (confirm('Are you sure you want to delete this delivery address?')) {
        router.delete(`/account/addresses/${id}`);
    }
};
</script>

<template>
    <CustomerLayout>
        <Head title="Saved Addresses — D STORE" />

        <div class="max-w-5xl mx-auto px-4 sm:px-8 py-12">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div>
                    <h1 class="text-3xl font-black text-[#111111] tracking-tight">
                        {{ t('account.saved_addresses') }}
                    </h1>
                    <p class="text-xs text-[#86868B] mt-1">Manage delivery locations for swift, seamless checkout across Cambodia.</p>
                </div>
                <Button variant="primary" @click="showAddModal = true" class="flex items-center gap-2">
                    <Plus class="w-4 h-4" />
                    <span>Add New Address</span>
                </Button>
            </div>

            <AccountNav />

            <!-- Empty State -->
            <div v-if="addresses.length === 0" class="bg-white rounded-3xl p-12 text-center border border-[#E5E5EA] space-y-4">
                <MapPin class="w-12 h-12 text-[#86868B] mx-auto" />
                <h3 class="text-base font-bold text-[#111111]">No Saved Addresses</h3>
                <p class="text-xs text-[#86868B]">Add your primary delivery address in Cambodia for instant checkout.</p>
                <Button variant="primary" @click="showAddModal = true">
                    Add Your First Address
                </Button>
            </div>

            <!-- Address Cards Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="addr in addresses"
                    :key="addr.id"
                    :class="[
                        'bg-white rounded-2xl p-6 border transition-all duration-200 relative flex flex-col justify-between',
                        addr.is_default ? 'border-[#111111] shadow-md ring-1 ring-[#111111]' : 'border-[#E5E5EA] hover:border-[#111111]'
                    ]"
                >
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-[#111111]">{{ addr.recipient_name }}</h3>
                                <span
                                    v-if="addr.is_default"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#111111] text-white tracking-wider"
                                >
                                    <CheckCircle2 class="w-3 h-3" />
                                    DEFAULT
                                </span>
                            </div>
                            <button
                                @click="deleteAddress(addr.id)"
                                class="text-[#86868B] hover:text-[#DC2626] p-1.5 rounded-lg hover:bg-[#F5F5F7] transition-colors"
                                title="Delete address"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>

                        <p class="text-xs text-[#1D1D1F] font-medium">{{ addr.phone }}</p>
                        <p class="text-xs text-[#86868B] mt-2 leading-relaxed">
                            {{ addr.street_address }}<br />
                            <span v-if="addr.commune">{{ addr.commune }}, </span>{{ addr.district }}<br />
                            <strong class="text-[#111111]">{{ addr.province }}, Cambodia</strong>
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-[#F5F5F7] flex items-center justify-between">
                        <span class="text-[11px] text-[#86868B]">Verified Cambodia Address</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Address Dialog -->
        <Dialog :open="showAddModal" @close="showAddModal = false" title="Add Delivery Address">
            <form @submit.prevent="submitAddress" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Input
                        v-model="form.recipient_name"
                        label="Recipient Name"
                        placeholder="e.g. Sokha Chan"
                        :error="errors.recipient_name"
                        required
                    />
                    <Input
                        v-model="form.phone"
                        label="Phone Number"
                        placeholder="e.g. 012 345 678"
                        :error="errors.phone"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Select
                        v-model="form.province"
                        label="Province / City"
                        :options="provinces"
                        :error="errors.province"
                        required
                    />
                    <Input
                        v-model="form.district"
                        label="Khan / District"
                        placeholder="e.g. Chamkar Mon"
                        :error="errors.district"
                        required
                    />
                </div>

                <Input
                    v-model="form.commune"
                    label="Sangkat / Commune (Optional)"
                    placeholder="e.g. Tonle Bassac"
                    :error="errors.commune"
                />

                <Input
                    v-model="form.street_address"
                    label="Street Address / House / Building"
                    placeholder="e.g. #128, St. 51, Rose Condo"
                    :error="errors.street_address"
                    required
                />

                <label class="flex items-center gap-2 cursor-pointer pt-2">
                    <input
                        type="checkbox"
                        v-model="form.is_default"
                        class="w-4 h-4 rounded border-[#E5E5EA] text-[#111111] focus:ring-[#111111]"
                    />
                    <span class="text-xs text-[#1D1D1F]">Set as default delivery address</span>
                </label>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E5E5EA]">
                    <Button type="button" variant="secondary" @click="showAddModal = false">
                        Cancel
                    </Button>
                    <Button type="submit" variant="primary" :loading="isSubmitting">
                        Save Address
                    </Button>
                </div>
            </form>
        </Dialog>
    </CustomerLayout>
</template>
