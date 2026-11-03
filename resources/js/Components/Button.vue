<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'outline' | 'danger' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    type: 'button',
    disabled: false,
    loading: false,
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'primary':
            return 'bg-[#111111] text-white hover:bg-[#2A2A2E] active:bg-black focus-visible:ring-[#111111]';
        case 'secondary':
            return 'bg-white text-[#1D1D1F] border border-[#E5E5EA] hover:bg-[#F5F5F7] active:bg-[#EAEAEA] focus-visible:ring-[#86868B] shadow-sm';
        case 'outline':
            return 'bg-transparent text-[#111111] border border-[#111111] hover:bg-[#111111] hover:text-white active:bg-black focus-visible:ring-[#111111]';
        case 'danger':
            return 'bg-[#DC2626] text-white hover:bg-[#B91C1C] active:bg-[#991B1B] focus-visible:ring-[#DC2626]';
        case 'ghost':
            return 'bg-transparent text-[#1D1D1F] hover:bg-[#F5F5F7] active:bg-[#EAEAEA] focus-visible:ring-[#0071E3]';
        default:
            return '';
    }
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-3 py-1.5 text-xs font-medium rounded-lg gap-1.5';
        case 'lg':
            return 'px-6 py-3.5 text-base font-semibold rounded-2xl gap-2.5';
        case 'md':
        default:
            return 'px-4 py-2.5 text-sm font-medium rounded-xl gap-2';
    }
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center transition-[transform,background-color,border-color,color,box-shadow,opacity] duration-150 ease-out active:scale-[0.97] touch-manipulation select-none cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100"
        :class="[variantClasses, sizeClasses]"
    >
        <svg
            v-if="loading"
            class="animate-spin -ml-0.5 h-4 w-4 text-current"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <slot />
    </button>
</template>
