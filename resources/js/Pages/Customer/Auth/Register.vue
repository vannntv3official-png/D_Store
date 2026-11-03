<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import { useTrans } from '@/Composables/useTrans';
import { Award, ArrowRight } from 'lucide-vue-next';

const { t } = useTrans();

const form = reactive({
    name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const submit = () => {
    isSubmitting.value = true;
    errors.value = {};

    router.post('/register', form, {
        onError: (err) => {
            errors.value = err;
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Create Account — D STORE" />

        <div class="min-h-[75vh] flex items-center justify-center px-4 py-16">
            <div class="w-full max-w-md bg-white rounded-3xl p-8 sm:p-10 border border-[#E5E5EA] shadow-xl space-y-6">
                <div class="text-center space-y-2">
                    <h1 class="text-2xl font-black text-[#111111] tracking-tight">
                        {{ t('nav.register') }}
                    </h1>
                    <p class="text-xs text-[#86868B]">
                        Join D STORE to enjoy members-only benefits and rewards.
                    </p>

                    <!-- Welcome Bonus Pill -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#DCFCE7] text-[#166534] rounded-full text-xs font-bold">
                        <Award class="w-3.5 h-3.5" />
                        <span>Instant 100 D-Rewards Points on Signup</span>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <Input
                        v-model="form.name"
                        label="Full Name"
                        placeholder="e.g. Sokha Chan"
                        :error="errors.name"
                        required
                    />

                    <Input
                        v-model="form.phone"
                        label="Phone Number"
                        placeholder="012 345 678"
                        :error="errors.phone"
                        required
                    />

                    <Input
                        v-model="form.email"
                        type="email"
                        label="Email Address (optional)"
                        placeholder="sokha@domain.com"
                        :error="errors.email"
                    />

                    <Input
                        v-model="form.password"
                        type="password"
                        label="Password"
                        placeholder="Minimum 8 characters"
                        :error="errors.password"
                        required
                    />

                    <Input
                        v-model="form.password_confirmation"
                        type="password"
                        label="Confirm Password"
                        placeholder="Repeat your password"
                        :error="errors.password_confirmation"
                        required
                    />

                    <Button
                        type="submit"
                        variant="primary"
                        size="lg"
                        class="w-full mt-2"
                        :loading="isSubmitting"
                    >
                        <span>Create Account</span>
                        <ArrowRight class="w-4 h-4 ml-1" />
                    </Button>
                </form>

                <div class="pt-4 border-t border-[#F5F5F7] text-center text-xs text-[#86868B]">
                    <span>Already have an account? </span>
                    <Link href="/login" class="font-bold text-[#111111] hover:underline">
                        {{ t('nav.login') }}
                    </Link>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
