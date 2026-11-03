<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import { useTrans } from '@/Composables/useTrans';
import AccountNav from '@/Components/AccountNav.vue';
import { User, Lock, Check } from 'lucide-vue-next';

interface Props {
    user: {
        name: string;
        email?: string | null;
        phone: string;
    };
}

const props = defineProps<Props>();
const { t } = useTrans();

const form = reactive({
    name: props.user.name,
    email: props.user.email || '',
    phone: props.user.phone,
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const isUpdating = ref(false);
const errors = ref<Record<string, string>>({});

const updateProfile = () => {
    isUpdating.value = true;
    errors.value = {};

    router.put('/account/profile', form, {
        onError: (err) => {
            errors.value = err;
            isUpdating.value = false;
        },
        onFinish: () => {
            isUpdating.value = false;
        },
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Account Profile — D STORE" />

        <div class="max-w-4xl mx-auto px-4 sm:px-8 py-12">
            <h1 class="text-3xl font-black text-[#111111] tracking-tight mb-4">
                {{ t('account.profile') }}
            </h1>
            <AccountNav />

            <div class="bg-white rounded-3xl p-8 sm:p-10 border border-[#E5E5EA] shadow-sm space-y-8">
                <form @submit.prevent="updateProfile" class="space-y-6">
                    <div class="space-y-4">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                            <User class="w-4 h-4" />
                            Personal Details
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input
                                v-model="form.name"
                                label="Full Name"
                                :error="errors.name"
                                required
                            />
                            <Input
                                v-model="form.phone"
                                label="Phone Number"
                                :error="errors.phone"
                                required
                            />
                        </div>

                        <Input
                            v-model="form.email"
                            type="email"
                            label="Email Address"
                            :error="errors.email"
                        />
                    </div>

                    <div class="pt-6 border-t border-[#F5F5F7] space-y-4">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                            <Lock class="w-4 h-4" />
                            Change Password (Leave blank to keep current)
                        </h2>

                        <Input
                            v-model="form.current_password"
                            type="password"
                            label="Current Password"
                            placeholder="••••••••"
                            :error="errors.current_password"
                        />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input
                                v-model="form.new_password"
                                type="password"
                                label="New Password"
                                placeholder="••••••••"
                                :error="errors.new_password"
                            />
                            <Input
                                v-model="form.new_password_confirmation"
                                type="password"
                                label="Confirm New Password"
                                placeholder="••••••••"
                                :error="errors.new_password_confirmation"
                            />
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <Button type="submit" variant="primary" :loading="isUpdating">
                            Save Changes
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </CustomerLayout>
</template>
